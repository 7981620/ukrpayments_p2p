<?php

namespace Agenta\UkrpaymentsP2p\Requests;

use Illuminate\Foundation\Http\FormRequest;
use LVR\CreditCard\CardExpirationMonth;
use LVR\CreditCard\CardExpirationYear;
use LVR\CreditCard\CardNumber;

class p2pRequest extends FormRequest
{
    public function attributes()
    {
        return [
            'card_from' => 'картка відправника',
            'card_to' => 'картка отримувача',
            'month' => 'місяць',
            'year' => 'рік',
            'cvv' => 'код CVV',
            'phone' => 'номер телефону',
            'amount' => 'сума переказу',
            'agree' => 'я підтверджую, що ввів достовірні дані...'
        ];
    }

    public function rules()
    {

        if (app()->environment() === 'production') {

            return [
                'agree' => 'required|in:on',
                'card_from' => ['required', new CardNumber],
                'card_to' => ['required', new CardNumber],
                'amount' => 'required|numeric|between:5,5000',
                'month' => ['required', new CardExpirationMonth($this->get('year'))],
                'year' => ['required', new CardExpirationYear($this->get('month'))],
                'cvv' => 'required|numeric',
                'phone' => 'required|phone:mobile,UA',
            ];

        }

        //если не продакшен, то без валидации банковский карты
        return [
            'agree' => 'required|in:on',
            'card_from' => 'required',
            'card_to' => 'required',
            'amount' => 'required|numeric|between:5,5000',
            'month' => 'required',
            'year' => 'required',
            'cvv' => 'required|numeric',
            'phone' => 'required|phone:mobile,UA',
        ];


    }

    public function filters(): array
    {
        return [
            'card_from' => 'trim|escape',
            'card_to' => 'trim|escape',
        ];
    }
}
