<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', '') }}</title>
{{--    <link href="{{ asset('vendor/ukrpayments_p2p/css/app.css') }}" rel="stylesheet">--}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.15/tailwind.min.css" integrity="sha512-braXHF1tCeb8MzPktmUHhrjZBSZknHvjmkUdkAbeqtIrWwCchhcpUeAf2Sq3yIq1Q1x5PlroafjceOUbIE3Q5g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.15/components.min.css" integrity="sha512-f6TS5CFJrH/EWmarcBwG54/kW9wwejYMcw+I7fRnGf33Vv4yCvy4BecCKTti3l8e8HnUiIbxx3V3CuUYGqR1uQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.15/utilities.min.css" integrity="sha512-Y8cJYgNbd3VacNNezxAdncUu75Uj7uR/Dtb4ffepQrtFww5/QlgYt2IwexMwB8/SZWCCVe6kOY20A2Q4zQf5vQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
    <script src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/3/jquery.inputmask.bundle.js"></script>

</head>
<body>
<div id="app">
    <div class="container-fluid">
        @include('ukrpayments_p2p::alert')
        @yield('content')
    </div>
</div>
</body>
</html>
