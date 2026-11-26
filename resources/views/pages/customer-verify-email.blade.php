@extends('layouts.default')

@section('meta')
  <title>Verify Email - Bali Scenic</title>
@endsection

@section('content')
<div class="page-title dark-background" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
  <div class="container position-relative"><h1>Verify Your Email</h1></div>
</div>
<section class="section py-5">
  <div class="container">
    <div class="row justify-content-center"><div class="col-lg-7">
      <h2>Verify your email before viewing your bookings</h2>
      <p>Your current account uses <strong>{{ auth()->user()->email }}</strong>. Verify this address to see bookings associated with it.</p>
      @if (session('customer_message'))
        <div class="alert alert-success" role="status">{{ session('customer_message') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
      @endif
      @if ($mailAvailable)
        <form method="POST" action="{{ route('customer.verify.send') }}">
          @csrf
          <button type="submit" class="btn btn-primary">Send verification email</button>
        </form>
      @else
        <div class="alert alert-warning" role="status">Email verification is temporarily unavailable because email delivery is not configured. Please contact us.</div>
      @endif
      <p class="mt-3">You can also track a booking using its number and booking email, or sign out and use a different verified account.</p>
      <a class="btn btn-outline-primary" href="{{ route('tracking.form') }}">Track a booking</a>
      <form method="POST" action="{{ route('logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-secondary">Sign out</button>
      </form>
    </div></div>
  </div>
</section>
@endsection
