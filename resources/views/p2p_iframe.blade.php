
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Головна сторінка</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Переказ на картку</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <h1>Переказ на картку</h1>
        </div>
    </div>
    <div class="row pay-form-page">
        <div class="col-md-4 order-12 order-sm-1 pt-3">

            <div class="mobile-hide">
                <p>Будь-ласка здійсніть переказ, заповнивши платіжну форму.</p>
            </div>

            <h5 class="mt-4">Про переказ:</h5>
            <ul>
                <li><strong>Телефон відправника:</strong> {{ substr_replace($payment->phone, str_repeat("*", 6), 5, 5) }}</li>
                <li><strong>Картка отримувача:</strong> {{ \Agenta\UkrpaymentsP2p\Services\BankCardService::maskCard($payment->a2c_card) }}</li>
                <li><strong>Cума зарахування:</strong> {{ \Agenta\UkrpaymentsP2p\Services\StringService::showInUah($payment->amount) }} грн.</li>
                <li><strong>Комісія з відправника:</strong> <span id="fee">{{ \Agenta\UkrpaymentsP2p\Services\StringService::showInUah($payment->a2c_fee) }} грн.</span></li>
            </ul>

        </div>

        <div class="col-md-7 order-1 order-sm-12">
            <div class="px-4 rounded shadow-lg pb-4" style="background: #FDFDFD;">
            <iframe src="{{ $payment->pay_url }}" frameborder="0" width="100%" height="620"></iframe>
            </div>
        </div>

    </div>

