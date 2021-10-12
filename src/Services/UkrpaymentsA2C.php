<?php

namespace Agenta\UkrpaymentsP2p\Services;

use Agenta\UkrpaymentsP2p\Models\PaymentP2P;
use Illuminate\Support\Facades\Log;
use StringService;

// Шлюз UkrPayments A2C
class UkrpaymentsA2C extends Ukrpayments
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create Order
     *
     * @param string $orderId
     * @param int $amount
     * @param string $card
     * @param array $extra
     * @return array|false
     */
    public function createOrderA2C(
        string $orderId,
        int    $amount,
        string $card,
        array  $extra = []
    ): bool|array
    {

        $data = [
            "test" => $this->testMode,
            "reserve" => false, // одностадийный платеж - не нужен метод confirm
            "merchant_order_id" => $orderId,
            "amount" => $amount,
            "pan" => $card,
            "extra" => $extra,
//            "callback_url" => 'GET_URL:' . $this->credentials['site_url'] . "a2c?mid={merchant_terminal_id}&order={order_id}&status={order_status}&merchant_order={merchant_order_id}"
        ];

        if ($paymentData = $this->request($data, '/a2c/create')) {
            return $paymentData;
        }

        return false;

    }


    /**
     * Проверка статуса заказа
     *
     * @param string $orderId
     * @return mixed
     */
    public function status(string $orderId): mixed
    {
        $rrn = null;
        $appcode = null;
        $payment_error = null;

        $response = $this->request(["merchant_order_id" => $orderId], '/a2c/status');

        if ($response) {

            $orderStatus = $response['order_status'];
            if ($response['payment_error'] && $response['payment_error'] !== "") {
                $payment_error = $response['payment_error'];
            }

            //проверка статуса платежа
            $this->log('debug','статус платежа ' . $this->orderStatuses[$orderStatus]);
            if ($orderStatus === 0) {
                $status = 'new';
            }

            if ($orderStatus === 1) {

                $rrn = $response['payment_rrn'];
                $appcode = $response['approval_code'];
                $status = 'success';
            }

            if ($orderStatus === 2) {

                if (isset($response['payment_rrn'])) {
                    $rrn = $response['payment_rrn'];
                }
                if (isset($response['approval_code'])) {
                    $appcode = $response['approval_code'];
                }
                $status = 'fail';
            }

            if ($orderStatus === 5) {

                if (isset($response['payment_rrn'])) {
                    $rrn = $response['payment_rrn'];
                }
                if (isset($response['approval_code'])) {
                    $appcode = $response['approval_code'];
                }
                $status = 'inprogress';
            }


            if ($orderStatus === 10) {

                if (isset($response['payment_rrn'])) {
                    $rrn = $response['payment_rrn'];
                }
                if (isset($response['approval_code'])) {
                    $appcode = $response['approval_code'];
                }
                $status = 'inprogress';
            }

            if ($payment = PaymentP2P::where('a2c_uuid', $response['merchant_order_id'])->first()) {
                $payment->update([
                    'a2c_status' => $status,
                    'a2c_paygate_payment_status' => $response['payment_status'],
                    'a2c_paygate_order_status' => $response['order_status'],
                    'a2c_rrn' => $rrn,
                    'a2c_approval' => $appcode,
                    'a2c_paygate_description' => $payment_error,
                ]);

            } else {
                Log::warning('a2c status - не найден платеж ' . $response['merchant_order_id'] . ', статус: ' . $status, $response);
            }

/*
            if ($status === 'success') {
                StringService::telegramService()->send(config('ukrpayments_p2p.site_url') . ' - успешный перевод с карты ' . $payment->card . ' на карту ' . StringService::maskBankCard($payment->a2c_card) . ' в размере ' . StringService::showInUah($payment->a2c_amount) . ' грн., телефон отправителя ' . $payment->phone);
            }

            if ($status === 'fail') {
                StringService::telegramService()->send(config('ukrpayments_p2p.site_url') . ' - неуспешный перевод с карты (' . $payment->a2c_paygate_description . ') с карты ' . $payment->card . ' на карту ' . StringService::maskBankCard($payment->a2c_card) . ' в размере ' . StringService::showInUah($payment->a2c_amount) . ' грн., телефон отправителя ' . $payment->phone . ', ID операции A2C: ' . $payment->a2c_paygate_id) . ', необходимо сделать возврат средств отправителю.';
            }

            if ($status === 'inprogress') {
                StringService::telegramService()->send(config('ukrpayments_p2p.site_url') . ' - в процессе перевод с карты ' . $payment->card . ' на карту ' . StringService::maskBankCard($payment->a2c_card) . ' в размере ' . StringService::showInUah($payment->a2c_amount) . ' грн., телефон отправителя ' . $payment->phone . ', ID операции A2C: ' . $payment->a2c_paygate_id) . ', необходимо проверить статус позже.';
            }*/

            return $status;

        }

        Log::error('ошибка статуса платежа A2C ' . $orderId);
        return false;

    }


}
