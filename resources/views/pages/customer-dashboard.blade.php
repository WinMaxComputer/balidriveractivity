@extends('layouts.default')

@section('meta')
  <title>My Bookings - Bali Scenic</title>
@endsection

@section('content')
<div class="page-title dark-background" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
  <div class="container position-relative"><h1>My Bookings</h1></div>
</div>
<section class="section py-5">
  <div class="container">
    @if (session('customer_message'))
      <div class="alert alert-success" role="status">{{ session('customer_message') }}</div>
    @endif
    @if ($errors->any())
      <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="row g-4">
      <div class="col-lg-7">
        <h2>Booking history</h2>
        <p>Bookings for {{ auth()->user()->email }}</p>
        @include('includes.customer-booking-list')
        {{ $bookings->links() }}
      </div>
      <div class="col-lg-5">
        <h2 class="h4">{{ auth()->user()->has_local_password === false ? 'Set a password' : 'Change password' }}</h2>
        <form method="POST" action="{{ route('customer.password.change') }}">
          @csrf
          @method('PUT')
          @if (auth()->user()->has_local_password !== false)
            <div class="mb-3">
              <label for="current-password" class="form-label">Current password</label>
              <input id="current-password" type="password" name="current_password" class="form-control" autocomplete="current-password" required>
            </div>
          @endif
          <div class="mb-3">
            <label for="new-password" class="form-label">New password</label>
            <input id="new-password" type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
          </div>
          <div class="mb-3">
            <label for="new-password-confirmation" class="form-label">Confirm password</label>
            <input id="new-password-confirmation" type="password" name="password_confirmation" class="form-control" minlength="8" autocomplete="new-password" required>
          </div>
          <button type="submit" class="btn btn-primary">Save password</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
          @csrf
          <button type="submit" class="btn btn-outline-secondary">Sign out</button>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
