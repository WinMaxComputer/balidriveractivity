@extends('layouts.default')

@section('meta')
    <title>Checkout - {{ $service->name }}</title>
@endsection

@section('content')
<div class="page-title dark-background" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
    <div class="container position-relative">
        <h1>Checkout</h1>
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <ol>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li class="current">Checkout</li>
            </ol>
        </nav>
    </div>
</div>
<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <h2>Checkout</h2>
                <p><strong>{{ $service->name }}</strong> — {{ $currency }} {{ number_format($displayPrice, $currency === 'USD' ? 2 : 0) }} per person</p>
                @if ($currencyUnavailable)
                    <div class="alert alert-warning" role="alert">The selected currency's exchange rate is unavailable. Prices are shown in USD; please select a currency again.</div>
                @elseif ($currency !== 'USD')
                    <p>Prices in {{ $currency }} are estimates. Your reservation total is recorded in USD.</p>
                @endif
                <p>Available for booking. No online payment is required; your booking will be marked unpaid.</p>
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('checkout.store', ['type' => $type, 'id' => $id]) }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ old('token', $token) }}">
                    <input type="hidden" name="unit_price" value="{{ old('unit_price', $service->price) }}">
                    <div class="mb-3">
                        <label for="checkout-name" class="form-label">Full name</label>
                        <input id="checkout-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="checkout-email" class="form-label">Email</label>
                        <input type="email" id="checkout-email" name="email" class="form-control" value="{{ old('email') }}" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="checkout-date" class="form-label">Travel date</label>
                        <input type="date" id="checkout-date" name="travel_date" class="form-control" min="{{ now()->toDateString() }}" value="{{ old('travel_date', $initial['travel_date'] ?? '') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="checkout-travelers" class="form-label">Number of travelers</label>
                        <input type="number" id="checkout-travelers" name="travelers" class="form-control" min="1" max="99999" value="{{ old('travelers', $initial['travelers'] ?? 1) }}" required>
                    </div>
                    <p>Total: <strong id="checkout-total">{{ $currency }} {{ number_format($displayPrice * (int) old('travelers', $initial['travelers'] ?? 1), $currency === 'USD' ? 2 : 0) }}</strong></p>
                    <button type="submit" class="btn btn-primary">Place Booking</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    const travelers = document.getElementById('checkout-travelers');
    const total = document.getElementById('checkout-total');
    const unitPrice = {{ $displayPrice }};
    const currency = @json($currency);
    const formatter = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: currency === 'USD' ? 2 : 0,
        maximumFractionDigits: currency === 'USD' ? 2 : 0,
    });
    function updateTotal() {
        const count = Number(travelers.value);
        total.textContent = Number.isInteger(count) && count >= 1 && count <= 99999
            ? currency + ' ' + formatter.format(unitPrice * count) : 'Enter a valid number of travelers';
    }
    travelers.addEventListener('input', updateTotal);
    updateTotal();
</script>
@endsection
