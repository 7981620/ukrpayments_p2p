@extends('ukrpayments_p2p::layout')
@section('content')
    <div class="row">
        <div class="col-md-12 mt-3">
            <h1>{{ $title }}</h1>
        </div>
    </div>
    <div class="row pay-form-page">
        <div class="col-md-12 order-12 order-sm-1 pt-3 pb-5">
            {{ $message }}
        </div>
    </div>
@endsection
