@extends('layouts.default')

@section('meta')
  <title>Customer Login - Bali Scenic</title>
@endsection

@section('content')
<div class="page-title dark-background" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
  <div class="container position-relative"><h1>Customer Login</h1></div>
</div>
<section class="section py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <h2>Sign in</h2>
        @if (session('customer_message'))
          <div class="alert alert-success" role="status">{{ session('customer_message') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('customer.login.submit') }}">
          @csrf
          <div class="mb-3">
            <label for="customer-email" class="form-label">Email</label>
            <input id="customer-email" type="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
          </div>
          <div class="mb-3">
            <label for="customer-password" class="form-label">Password</label>
            <input id="customer-password" type="password" name="password" class="form-control" autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn btn-primary">Sign in</button>
          <a class="btn btn-outline-primary" href="{{ route('auth.google.redirect') }}">Sign in with Google</a>
        </form>
      </div>
      <div class="col-lg-6">
        <h2>First time or forgot your password?</h2>
        <p>Enter the email used for your booking. We will email you a link to verify your address and set a password.</p>
        @unless ($mailAvailable)
          <div class="alert alert-warning" role="status">Password setup by email is temporarily unavailable. Please contact us or sign in with Google.</div>
        @endunless
        <form method="POST" action="{{ route('customer.password.request') }}">
          @csrf
          <div class="mb-3">
            <label for="setup-email" class="form-label">Booking email</label>
            <input id="setup-email" type="email" name="email" class="form-control" maxlength="255" autocomplete="email" required>
          </div>
          <button type="submit" class="btn btn-outline-primary">Send password link</button>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
