<?php

namespace Agenta\UkrpaymentsP2p\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use StringService;


/**
 * Базовые методы для работы со шлюзом Укрпейментс
 */
class Ukrpayments
{

    public $testMode;
    private $apiPrefix = '/v1';
    public $credentials = [];
    public $mcc;
    public int $minAmount;
    public int $maxAmount;
    public float $feeProcent;
    public int $feeUAH;

    protected $orderStatuses = [
        0 => 'new',
        1 => 'success',
        2 => 'failure',
        10 => 'inprogress'
    ];

    protected $actionCodes = [
        0 => 'NEW',
        2 => 'EXPIRED',
        3 => 'LIMITS',
        30 => 'HOLDED',
        40 => 'CAPTURED',
    ];


    public function __construct()
    {
        $this->testMode = config('ukrpayments_p2p.test_mode');
        $this->credentials['merchant_id'] = config('ukrpayments_p2p.merchant_id');
        $this->credentials['terminal_id'] = config('ukrpayments_p2p.terminal_id');
        $this->credentials['base_url'] = config('ukrpayments_p2p.base_url');
        $this->credentials['api_token'] = config('ukrpayments_p2p.api_token');
        $this->credentials['api_secret'] = config('ukrpayments_p2p.api_secret');

        //назначение комиссий из конфигурации
        $this->feeProcent = config('ukrpayments_p2p.p2_fee_procent');
        $this->feeUAH = config('ukrpayments_p2p.p2_fee_uah');
        $this->minAmount = config('ukrpayments_p2p.p2_min_amount');
        $this->maxAmount = config('ukrpayments_p2p.p2_max_amount');

        //меняем URL сайт в зависимости от среды разработки
        if (app()->environment() === 'local') {
            $this->credentials['site_url'] = config('ukrpayments_p2p.site_url_test');
        } else {
            $this->credentials['site_url'] = config('ukrpayments_p2p.site_url');
        }

        //добавляем в конце урла slash, если его нет
        $siteUrl = $this->credentials['site_url'];
        if (strcmp($siteUrl[-1], "/") !== 0) {
            $this->credentials['site_url'] = $siteUrl . '/';
        }

    }


    /**
     *
     * @return string[]
     */
    public function getOrderStatuses(): array
    {
        return $this->orderStatuses;
    }


    /**
     * Отправка запроса на сервер UkrPayments
     *
     * @param $data
     * @param string $path
     * @return array|false
     */
    public function request($data, string $path = ''): bool|array
    {
        $key = time();
        $requestData = [
            'auth' => [
                'tid' => $this->credentials['terminal_id'],
                'token' => $this->credentials['api_token'],
                'key' => $key,
                'hash' => md5($this->credentials['api_secret'] . $key . $this->credentials['api_token']),
            ],
            'request' => $data,
        ];
        $this->log('debug', 'request to upay -> ' . $path, $requestData);

        $response = Http::post($this->credentials['base_url'] . $this->apiPrefix . $path, $requestData);


        $this->log('debug', 'response from upay <-' . $path, $response->json());

        if ($response->status() === 200) {
            $response = $response->json();
            if ($this->validateResponse($requestData, $response)) {
                return $this->checkErrorCode($response);
            }
        }

        Log::error('upay method ' . $path . ' status ' . $response->status());
        return false;

    }


    /**
     * Запись в лог если режим теста
     *
     * @param string $level
     * @param string $message
     * @param array $array
     */
    public function log(string $level, string $message, array $array = []): void
    {
        if ($this->testMode) {
            Log::$level($message, $array);
        }
    }

    /**
     * Пинг сервиса
     *
     * @return array|false
     */
    public function ping(): bool|array
    {
        return $this->request([], $this->apiPrefix . '/ping');
    }

    /**
     * Баланс терминала
     */
    public function balance()
    {
        if ($paymentData = $this->request([], $this->apiPrefix . '/terminal/balance')) {
            return $paymentData['balance'] ?? false;
        }
        return false;
    }


    /**
     * Проверка что ответ успешный
     *
     * @param array $response
     * @return array|false
     */
    private function checkErrorCode(array $response): bool|array
    {

        if (!isset($response['error']['code']) | $response['error']['code'] !== 0) {
            $this->log('info', 'error code: ' . $response['error']['code'] . ', message: ' . $response['error']['message']);
            return false;
        }
        return $response['response'];
    }


    /**
     * Валидация ответа
     *
     * @param $request
     * @param $response
     * @return bool
     */
    private function validateResponse($request, $response): bool
    {

        if (isset($response['sign'])) {
            $responseSing = $response['sign'];
            $secret = $this->credentials['api_secret'];
            $key = $request['auth']['key'];
            unset($response['sign']);
            $response = json_encode($response);

            $check = md5($secret . $key . $response);

            if ($check === $responseSing) {
                return true;
            }

            Log::error('response sign check error');
            return false;
        }

        Log::error('in response no sign');
        return false;

    }

    /**
     * Расчет комиссии и суммы к оплате в копейках
     * по схеме: (сумма * ком_процент) + ком_копейках
     *
     * @param float $amount сумма для перевода
     * @return array
     */
    public function calcFee(float $amount): array
    {
        $feeProcent = round(($this->feeProcent * $amount) / 100, 2);
        $feeTotal = ($feeProcent + ($this->feeUAH / 100)) * 100;
        $toPay = $amount * 100 + $feeTotal;
        return [
            'fee' => (int)$feeTotal, // комиссия
            'amount_to_pay' => (int)$toPay // сумма к оплате
        ];
    }


}
