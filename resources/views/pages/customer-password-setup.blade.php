@extends('layouts.default')

@section('meta')
  <title>Set Password - Bali Scenic</title>
@endsection

@section('content')
<div class="page-title dark-background" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
  <div class="container position-relative"><h1>Set Your Password</h1></div>
</div>
<section class="section py-5">
  <div class="container">
    <div class="row justify-content-center"><div class="col-lg-6">
      @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
      @endif
      <form method="POST" action="{{ route('customer.password.setup') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
          <label for="setup-confirm-email" class="form-label">Email</label>
          <input id="setup-confirm-email" type="email" name="email" class="form-control" value="{{ old('email', $email) }}" maxlength="255" required>
        </div>
        <div class="mb-3">
          <label for="setup-password" class="form-label">New password</label>
          <input id="setup-password" type="password" name="password" class="form-control" minlength="8" autocomplete="new-password" required>
        </div>
        <div class="mb-3">
          <label for="setup-password-confirmation" class="form-label">Confirm password</label>
          <input id="setup-password-confirmation" type="password" name="password_confirmation" class="form-control" minlength="8" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary">Save password</button>
      </form>
    </div></div>
  </div>
</section>
@endsection
