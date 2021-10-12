<?php

namespace Agenta\UkrpaymentsP2p\Controllers;

use Agenta\UkrpaymentsP2p\Models\PaymentP2P;
use Agenta\UkrpaymentsP2p\Requests\p2pRequest;
use Agenta\UkrpaymentsP2p\Services\UkrpaymentsA2C;
use Agenta\UkrpaymentsP2p\Services\UkrpaymentsAcqPCI;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use StringService;

class p2pController extends Controller
{

    protected $p2p;
    protected $a2c;
    protected $siteUrl;

    public function __construct()
    {

        $this->p2p = new UkrpaymentsAcqPCI();
        $this->a2c = new UkrpaymentsA2C();

        if (app()->environment() === 'local') {
            $this->siteUrl = config('ukrpayments_p2p.site_url');
        } else {
            $this->siteUrl = config('ukrpayments_p2p.site_url_test');
        }

        //добавляем в конце урла slash, если его нет
        $siteUrl = $this->siteUrl;
        if (strcmp($siteUrl[-1], "/") !== 0) {
            $this->siteUrl = $siteUrl . '/';
        }

    }

    /**
     * Форма платежа
     *
     * @return Application|Factory|View
     */
    public function index(): View|Factory|Application
    {
        return view('ukrpayments_p2p::P2P_form');
    }


    /**
     * Редирект на форму 3DS
     *
     * @return Application|Factory|View
     */
    public function formRedirect(): View|Factory|Application
    {
        return view('ukrpayments_p2p::P2P_3ds');
    }

    /**
     * Создание платежа в шлюзе
     *
     * @param p2pRequest $request
     * @return Application|Factory|View|RedirectResponse
     * @throws \JsonException
     */
    public function store(p2pRequest $request): View|Factory|RedirectResponse|Application
    {
        $validated = $request->validated();

        $amount = (float)$validated['amount'];

        if (($amount * 100) >= $this->a2c->minAmount && ($amount * 100) <= $this->a2c->maxAmount) {

            $card_from = preg_replace('/\D/', '', $validated['card_from']);
            $card_to = preg_replace('/\D/', '', $validated['card_to']);
            $phone = StringService::phoneUaTransform(preg_replace('/\D/', '', $validated['phone']));

            $amounts = $this->a2c->calcFee($amount);
            $fee = $amounts['fee'];
            $paymentAmount = $amounts['amount_to_pay'];

            $orderId = Str::uuid()->toString();
            $acqPayment = $this->p2p->createOrder(
                $orderId,
                $paymentAmount,
            );

            if ($acqPayment) {

                //create ACQ in DB
                $paygateDescription = null;
                if (isset($acqPayment['action_description']) && $acqPayment['action_description']) {
                    $paygateDescription = $acqPayment['action_description'];
                }

                $paygateId = $acqPayment['order_id'];

                try {
                    $payment = PaymentP2P::create([
                        'uuid' => $orderId,
                        'card' => $card_from,
                        'a2c_uuid' => Str::uuid()->toString(),
                        'paygate_id' => $paygateId,
                        'amount' => $paymentAmount,
                        'a2c_amount' => StringService::toCoins($amount),
                        'a2c_fee' => $fee,
                        'a2c_card' => $card_to,
                        'status' => 'new',
                        'paygate_status' => $acqPayment['order_status'],
                        'paygate_description' => $paygateDescription,
                        'phone' => $phone,
                        'ip' => request()->getClientIp(),
                    ]);
                } catch (\Exception $exception) {
                    return Redirect::route('p2p.index')->with('error', 'Возникла ошибка в процессе создания платежа');
                }


                $paymentGate = $this->p2p->payment(
                    $paygateId,
                    $card_from,
                    $validated['month'],
                    $validated['year'],
                    $validated['cvv'],
                );

                if (isset($paymentGate['acsUrl'], $paymentGate['PaReq']) && $paymentGate) {
                    return view('ukrpayments_p2p::P2P_3ds', [
                        'acsUrl' => $paymentGate['acsUrl'],
                        'PaReq' => $paymentGate['PaReq'],
                        'orderId' => $orderId,
                        'siteUrl' => $this->siteUrl
                    ]);
                }

                $payment->delete();
                return Redirect::route('p2p.index')->with('error', 'Не удалось выполнить переход на страницу 3DS');
            }

            return Redirect::route('p2p.index')->with('error', 'Не удалось выполнить операцию создания платежа');

        }

        return Redirect::back()->with('error', 'Указана неправильная сумма платежа');


    }


    /**
     *
     * Обработка ответа шлюза
     *
     * @param Request $request
     * @return Application|Factory|View|void
     * @throws \JsonException
     */
    public function callback(Request $request)
    {


        if ($request->has('MD') && $request->has('PaRes') && $payment = PaymentP2P::whereUuid($request->get('MD'))->first()) {

            $msg = [
                'title' => 'Виникла помилка!',
                'message' => 'Нажаль, виникла помилка в процесі роботи системи...'
            ];

            if ($payment->status !== 'success' && $payment->status !== 'fail') {

                //выполняем подтверждение авторизации 3DS
                if ($mpiVerify = $this->p2p->MPIVerify(
                    $payment->paygate_id,
                    $request->get('PaRes'),
                )) {

                    Log::debug('MPI Verify Success');
                    return $this->createA2C($payment);

                }

                return view('ukrpayments_p2p::P2P_thanx', [
                    'title' => 'Операцію скасовано',
                    'message' => 'Операцію переказу на картку було скасовано (можливі причини: невірний CVV-код або код 3DSecure, недостатньо коштів на картці, картка відхилина банком емітентом з інших причин).'
                ]);
            }


            if ($payment->status === 'fail') {
                $msg = [
                    'title' => 'Помилка переказу',
                    'message' => 'Нажаль, але сталася помилка при оплаті з картки відправника.'
                ];
            }

            if ($payment->status === 'success' && $payment->a2c_status === 'success') {

                $msg = [
                    'title' => 'Переказ надіслано!',
                    'message' => 'Дякуємо, Ваш переказ успшіно відправлено.'
                ];
            }

            if ($payment->a2c_status === 'inprogress') {

                if ($newStatus = $this->a2c->status($payment->a2c_uuid)) {

                    if($newStatus === 'success') {
                        $msg = [
                            'title' => 'Переказ надіслано!',
                            'message' => 'Дякуємо, Ваш переказ успшіно відправлено.'
                        ];
                    }

                    if($newStatus === 'fail') {
                        $msg = [
                            'title' => 'Помилка переказу',
                            'message' => 'Нажаль, але сталася помилка при оплаті з картки відправника. Ми повернемо Вам повну суму на картку впродовж банківського дня.'
                        ];
                    }

                } else {

                    $msg = [
                        'title' => 'Зарахування в процесі',
                        'message' => 'Ваш переказ в процесі зарахування на картку платника.'
                    ];


                }

            }


            return view('ukrpayments_p2p::P2P_thanx', $msg);

        }

    }


    /**
     * Создание платежа A2C
     *
     * @param PaymentP2P $payment
     * @return Application|Factory|View|void
     * @throws \JsonException
     */
    private function createA2C(PaymentP2P $payment)
    {

        if (!$payment->a2c_status) {

            //делаем перевод A2C
            if ($p2p = $this->a2c->createOrderA2C(
                $payment->a2c_uuid,
                $payment->a2c_amount,
                $payment->a2c_card
            )) {

                $payment->update([
                    'a2c_paygate_id' => $p2p['order_id'],
                    'a2c_paygate_order_status' => $p2p['order_status'],
                    'a2c_paygate_payment_status' => $p2p['payment_status'],
                ]);

//                ожидаем 5 секунд и проверяем статус А2С заказа
//                sleep(5);
                return $this->getA2CStatus($payment);

            }

            return view('ukrpayments_p2p::P2P_thanx', [
                'title' => 'Помилка створення переказу',
                'message' => 'Нажаль, але сталася помилка створення переказу на картку ' . StringService::maskBankCard($payment->a2c_card) . '. Ми повернемо Вам повну суму на картку впродовж банківського дня.'
            ]);

        }

    }


    /**
     * Обработка статуса платежа A2C
     *
     * @param PaymentP2P $payment
     * @return Application|Factory|View
     * @throws \JsonException
     */
    public function getA2CStatus(PaymentP2P $payment): View|Factory|Application
    {

        if (!$payment->a2c_uuid) {
            return view('ukrpayments_p2p::P2P_thanx', [
                'title' => 'Переказ відмінено',
                'message' => 'Переказ було відмінено.'
            ]);
        }

        //уже имеет финальный статус в базе
        if ($payment->a2c_status === 'success' | $payment->a2c_status === 'fail') {
            return view('ukrpayments_p2p::P2P_thanx', [
                'title' => 'Переказ надіслано!',
                'message' => 'Дякуємо, Ваш переказ на картку ' . StringService::maskBankCard($payment->a2c_card) . ' успшіно відправлено та буде зараховано впродовж 10 хвилин.'
            ]);
        }

        $a2cStatus = $this->a2c->status($payment->a2c_uuid);

        if ($a2cStatus === 'success') {
            return view('ukrpayments_p2p::P2P_thanx', [
                'title' => 'Переказ надіслано!',
                'message' => 'Дякуємо, Ваш переказ на картку ' . StringService::maskBankCard($payment->a2c_card) . ' успшіно відправлено та буде зараховано впродовж 10 хвилин.'
            ]);
        }

        if ($a2cStatus === 'inprogress') {
            return view('ukrpayments_p2p::P2P_thanx', [
                'title' => 'Переказ в процесі відправлення',
                'message' => 'Ваш переказ на картку ' . StringService::maskBankCard($payment->a2c_card) . ' в процесі відправлення до банку отримувача та буде зарахован впродовж 10 хвилин.'
            ]);
        }

        if ($a2cStatus === 'fail') {
            return view('ukrpayments_p2p::P2P_thanx', [
                'title' => 'Помилка переказу',
                'message' => 'Нажаль, але сталася помилка зарахування на картку ' . StringService::maskBankCard($payment->a2c_card) . '. Ми повернемо Вам повну суму на картку впродовж банківського дня.'
            ]);
        }

        Log::warning('неизвестный статус a2c');

        return view('ukrpayments_p2p::P2P_thanx', [
            'title' => 'Переказ успішно надіслано',
            'message' => 'Дякуємо, Ваш переказ на картку ' . StringService::maskBankCard($payment->a2c_card) . ' успішно відправлено та буде зараховано впродовж 5-10 хвилин.'
        ]);

    }


}
