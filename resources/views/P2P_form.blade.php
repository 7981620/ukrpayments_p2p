@extends('ukrpayments_p2p::layout')
@section('content')
    <div class="row pay-form-page">
        <div class="col-md-4 order-12 order-sm-1">

            <img src="{{ asset('vendor/ukrpayments_p2p/img/logos-visa-mc.svg') }}" alt="" class="pay-form mb-5">
            <div class="mobile-hide">
                <p>Зробіть переказ на іншу картку швидко, безпечно і з мінімальною комісією. Ваш платіж буде опрацьовано в режимі онлайн настільки швидко, наскільки можливо. У разі виникнення будь-якої непередбаченої ситуації, наша цілодобова технічна допоможе її вирішити.</p>
            </div>

            <h5 class="mt-4">Тарифи та умови</h5>
            <ul>
                <li><strong>Cума переказу:</strong> від 5 до 5&nbsp;000,00 грн.</li>
                <li><strong>Комісія:</strong> <span id="fee">0,4% + 2,00 грн. від суми переказу, але не менше ніж 2 грн.</span></li>
            </ul>

        </div>
        <div class="col-md-6 order-1 order-sm-12">

            <div class="pay-form-block">

                <form id="pay-form" class="pay-form-phone" action="" method="POST" autocomplete="off">

                    {{ csrf_field() }}

                    <div class="row pb-2">

                        <div class="col-md-12">
                            <label for="phone" class="font-weight-bold">З картки</label>
                            <input value="4111 1111 1111 1111" autofocus data-inputmask="'mask': '9999 9999 9999 9999'" required class="form-control form-control-lg mt-2" name="card_from" type="text" placeholder="Картка відправника" value="{{ old('card_from') }}">
                        </div>

                        <div class="col-md-3 mt-2">
                            <label for="month">Місяць</label>
                            <select name="month" id="month" class="form-control mt-2">
                                @for($i = 1; $i < 13; $i++)
                                    <option @if((int)old('month') === $i) selected @endif value="@if($i <= 9){{ '0' }}@endif{{ $i }}">@if($i <= 9){{ '0' }}@endif{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3 mt-2">
                            <label for="month">Рік</label>
                            <select name="year" id="year" class="form-control mt-2">
                                @for($i = 2022; $i < 2052; $i++)
                                    <option @if((int)old('year') === $i) selected @endif value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3 mt-2">
                            <label for="month">CVV</label>
                            <input value="123" required class="form-control form-control-lg mt-2" name="cvv" type="password" maxlength="3" minlength="3" value="{{ old('cvv') }}">
                        </div>

                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <label for="phone" class="font-weight-bold">На картку</label>
                        </div>
                        <div class="col-md-12 mt-2">
                            <input value="4111 1111 1111 1111" data-inputmask="'mask': '9999 9999 9999 9999'" required class="form-control form-control-lg" name="card_to" type="text" placeholder="Картка отримувача" value="{{ old('card_to') }}">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <label for="amountToPay">Сума переказу, грн.</label>
                        </div>
                        <div class="col-md-6">
                            <input class="form-control form-control-lg"
                                   value="{{ old('amount', 5 ) }}"
                                   name="amount"
                                   step="0.01"
                                   type="number"
{{--                                   min="{{ 0.1 + round(0.01 * 0.4) + 2 }}"--}}
                                   min="5"
                                   max="29000"
                                   id="amountToPay"
                                   placeholder='0.00'
                                   data-decimal="2" oninput="limitDecimalInput(this)"
                                   required>
                        </div>
                    </div>


                    <div class="row mb-4" id="calcCom">
                        <div class="col-md-6">
                        </div>
                        <div class="col-md-6 mt-1">
                            <div class="small text-muted">
                                до сплати — <span id="toPay">0</span>&nbsp;грн.,<br/>враховуючи&nbsp;комісію
                                <span id="feeUAH">0</span>&nbsp;грн.
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-6">
                            <label for="clientId">Ваш телефон</label>
                        </div>
                        <div class="col-md-6">
                            <input required value="{{ old('phone', '380983941651') }}" class="form-control form-control-lg" id="phone" name="phone" type="tel">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"></div>
                        <div class="col-md-6 mt-1">
                            <small class="text-muted">для зв'язку у разі помилки платіжу</small>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agree" name="agree" checked>
                            <label class="form-check-label" for="agree">я підтверджую, що ввів достовірні дані і дозволяю подальшу їх обробку відповідно до <a href="/page/dogovor">Угоди користувача</a></label>
                        </div>
                    </div>
                    <div>
                        <button id="buttonSubmitP2P" type="submit" class="btn btn-lg bg-gr-breeze text-white">Сплатити<span id="total"></span></button>
                    </div>

                </form>

            </div>
        </div>
        <div class="col-md-2 mobile-hide order-last">
{{--            <img src="/img/groups/{{ $groupIcon ?? '' }}" alt="" style="width: 100%;">--}}
        </div>
    </div>

    <script>

        $(document).ready(function () {

            $(":input").inputmask();
            $("#phone").inputmask({"mask": "+99(999)999-99-99"});
            $('#amountToPay').inputmask({'mask': ["9{0,5}.9{0,2}", "999"]});

            let amount = Number($('#amountToPay').val());
            calcFee(amount);

            $('#amountToPay').keyup(function () {
                let amount = Number($('#amountToPay').val());
                let fee = ((amount * 0.04) / 100) + 2;
                let total = (fee + amount).toFixed(2);
                $('#feeUAH').html(fee.toFixed(2));
                $('#toPay').html((fee + amount).toFixed(2));

                // if(amount > 0) {
                //     $('#buttonSubmit').prop('disabled', false);
                // } else {
                //     $('#buttonSubmit').prop('disabled', true);
                // }
            });

            function limitDecimalInput(ele) {
                if ($(ele).data('decimal') != null) {
                    let decimal = parseInt($(ele).data('decimal')) || 0;
                    let val = $(ele).val();
                    if (decimal > 0) {
                        let splitVal = val.split('.');
                        if (splitVal.length == 2 && splitVal[1].length > decimal) {
                            $(ele).val(splitVal[0] + '.' + splitVal[1].substr(0, decimal));
                        }
                    } else if (decimal == 0) {
                        let splitVal = val.split('.');
                        if (splitVal.length > 1) {
                            $(ele).val(splitVal[0]);
                        }
                    }
                }
            }

            function calcFee(amount) {
                // let amount  = Number($('#amountToPay').val());
                let fee = ((amount * 0.04) / 100) + 2;
                let total = (fee + amount).toFixed(2);
                $('#feeUAH').html(fee.toFixed(2));
                $('#toPay').html((fee + amount).toFixed(2));
            }

        });

    </script>
@endsection
