@if (session('error'))
    <div class="bg-danger text-white rounded" role="alert">
        <div class="mx-auto container pt-2 pb-2">
            <p class="font-weight-bold mb-0">Возникла ошибка</p>
            <p class="text-sm">{{ session('error') }}</p>
        </div>
    </div>
@endif
@if (session('success'))
    <div class="bg-success rounded" role="alert">
        <div class="mx-auto container pt-2 pb-2">
            <p class="font-bold mb-0">Успешная операция</p>
            <p class="text-sm">{{ session('success') }}</p>
        </div>
    </div>
@endif
@if (isset($errors) && $errors->any())
    <div class="bg-danger text-white rounded" role="alert">
        <div class="mx-auto container pt-2 pb-2">
            <div class="font-weight-bold pt-2"><i class="dripicons-warning"></i> Исправьте ошибки:</div>
            <ul class="list-disc">
            @foreach ($errors->all() as $error)
                    <li class="text-sm">
                @if($error === 'card')
                    такая карта не может быть принимать платежи
                @else
                    {{ $error }}
                @endif
                </li>
            @endforeach
            </ul>
        </div>
    </div>
@endif
