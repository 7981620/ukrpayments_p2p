<?php

namespace Agenta\UkrpaymentsP2p\Services;

use Agenta\UkrpaymentsP2p\Models\PaymentP2P;
use Illuminate\Support\Facades\Log;
use StringService;

// Шлюз UkrPayments P2P
class UkrpaymentsAcqPCI extends Ukrpayments
{

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Create Order
     *
     * @param string $orderId UUID внутренний айди заказа
     * @param int $amount сумма к оплате в коп.
     * @return bool|array
     */
    public function createOrder(
        string $orderId,
        int    $amount
    ): bool|array
    {

        $data = [
            "test" => $this->testMode,
            "merchant_order_id" => $orderId,
            "amount" => $amount,
            "currency" => "UAH",
            "mcc" => $this->mcc,
            "language" => "ru",
            "ip" => \Request::getClientIp(),
            "timeout" => 600,
            "extra" => [],
            "customer_id" => $this->credentials['site_url'] . ' P2P',
        ];

        if ($paymentData = $this->request($data, '/internet-acquiring/create')) {
            return $paymentData;
        }

        return false;

    }


    /**
     *
     * Метод Payment
     *
     * @param string $orderId
     * @param string $card
     * @param string $month
     * @param string $year
     * @param string $cvv
     * @return array|false
     */
    public function payment(
        string $orderId,
        string $card,
        string $month,
        string $year,
        string $cvv
    )
    {

        $response = $this->request(
            [
                "test" => $this->testMode,
                "3ds_version" => null,
                "tokenize" => false,
                "hold" => false,
                "skip_enroll" => false,
                "full_3ds_only" => true,
                "order_id" => $orderId,
                "pan" => $card,
                "expire_year" => $year,
                "expire_month" => $month,
                "cvv" => $cvv,
                "name" => $this->credentials['site_url'] . ' P2P',
            ],
            '/internet-acquiring/payment'
        );

        if (isset($response['acsUrl'], $response['PaReq']) && $response) {
            return $response;
        }

        return false;

    }


    /**
     * Подтверждение авторизации платежа
     *
     * @param string $orderId
     * @param string $PaRes
     * @return bool
     */
    public function MPIVerify(
        string $orderId,
        string $PaRes
    ): bool
    {

        $response = $this->request(
            [
                "order_id" => $orderId,
                "PaRes" => $PaRes,
            ],
            '/internet-acquiring/mpi_verify'
        );

        if ($response && $response['order_status'] === 1) {

            if ($payment = PaymentP2P::where('paygate_id', $orderId)->first()) {
                $payment->update([
                    'status' => 'success',
                    'rrn' => $response['payment_rrn'],
                    'approval' => $response['payment_approval_code'],
                    'paygate_description' => $response['action_description'],
                    'paygate_status' => $response['order_status'],
                ]);
            }

            $this->log('debug', 'MPI Verify success', $response);
            return true;
        }

        $this->log('debug', 'MPI Verify Error');
        return false;

    }


    /**
     * Проверка статуса заказа
     *
     * @param string $orderId
     * @return array|false
     */
    public function status(string $orderId): bool|array
    {
        $card = null;
        $rrn = null;
        $appcode = null;
        $error_description = null;

        $response = $this->request(["merchant_order_id" => $orderId], '/internet-acquiring/status');

        if ($response) {
            $orderStatus = $response['order_status'];
            $actionCode = $response['action_code'];

            //проверка статуса платежа
            $this->log('debug', 'статус платежа ' . $this->orderStatuses[$orderStatus]);
            if ($orderStatus === 0) {
                $status = 'new';
            }

            if ($orderStatus === 1) {
                $card = $response['pan'];
                $rrn = $response['payment_rrn'];
                $appcode = $response['payment_approval_code'];
                if (isset($response['payment_info']) && isset($response['payment_info']['state'])) {
                    $error_description = $response['payment_info']['state'];
                }

                $status = 'success';
            }

            if ($orderStatus === 2) {
                if (isset($response['pan'])) {
                    $card = $response['pan'];
                }
                if (isset($response['payment_rrn'])) {
                    $rrn = $response['payment_rrn'];
                }
                if (isset($response['payment_approval_code'])) {
                    $appcode = $response['payment_approval_code'];
                }
                if (isset($response['payment_info'], $response['payment_info']['state'])) {
                    $error_description = $response['payment_info']['state'];
                }

                $status = 'fail';
            }

            if ($orderStatus === 10) {

                if (isset($response['pan'])) {
                    $card = $response['pan'];
                }
                if (isset($response['payment_rrn'])) {
                    $rrn = $response['payment_rrn'];
                }
                if (isset($response['payment_approval_code'])) {
                    $appcode = $response['payment_approval_code'];
                }
                if (isset($response['payment_info'], $response['payment_info']['state'])) {
                    $error_description = $response['payment_info']['state'];
                }

                $status = 'inprogress';

            }

            $update = [
                'pay_provider_status' => $orderStatus,
                'pay_status' => $status,
                'buyer_card' => $card,
                'rrn' => $rrn,
                'approval' => $appcode,
                'error_description' => $error_description,
            ];

            return $update;

        }

        Log::error('ошибка статуса платежа acq-pci ' . $orderId);
        return false;

    }


}
