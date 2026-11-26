@extends('layouts.default')

@section('meta')
    <title>Booking received</title>
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
<section class="section checkout-section">
    <div class="container">
        <h2>Booking received</h2>
        <p>Your booking number is <strong>{{ session('checkout_number') }}</strong>.</p>
        <p>Your booking is pending and unpaid. Please keep this number for your records.</p>
        <a href="{{ route('tour_packages') }}">Browse tours</a>
    </div>
</section>
@endsection
