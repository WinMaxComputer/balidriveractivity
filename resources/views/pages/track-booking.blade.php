@extends('layouts.default')

@section('meta')
  <title>Track Booking - Bali Scenic</title>
@endsection

@section('content')
<div class="page-title dark-background" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
  <div class="container position-relative"><h1>Track Your Booking</h1></div>
</div>
<section class="section py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <h2>Find a booking</h2>
        <p>Enter your booking number and the email used when booking.</p>
        @if ($errors->any())
          <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('tracking.lookup') }}">
          @csrf
          <div class="mb-3">
            <label for="tracking-booking" class="form-label">Booking number</label>
            <input id="tracking-booking" name="booking" class="form-control" value="{{ old('booking') }}" maxlength="50" required>
          </div>
          <div class="mb-3">
            <label for="tracking-email" class="form-label">Booking email</label>
            <input id="tracking-email" type="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255" required>
          </div>
          <button type="submit" class="btn btn-primary">Find booking</button>
        </form>
        @isset($bookings)
          <h2 class="h4 mt-4">Booking details</h2>
          @include('includes.customer-booking-list')
        @endisset
      </div>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-body">
            <h2 class="h4">Your booking history</h2>
            <p>Sign in with the verified email used for your bookings to see your history.</p>
            @auth
              <a class="btn btn-outline-primary" href="{{ route('customer.dashboard') }}">View my bookings</a>
            @else
              <a class="btn btn-outline-primary" href="{{ route('customer.login') }}">Sign in with email</a>
              <a class="btn btn-outline-primary" href="{{ route('auth.google.redirect') }}">Sign in with Google</a>
            @endauth
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
