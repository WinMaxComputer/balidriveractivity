@extends('layouts.app', ['page' => __('Tour Review Link'), 'pageSlug' => 'tour'])

@section('content')
<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><h4>Review link: {{ $tour->tour_name }}</h4></div>
      <div class="card-body">
        <p>Enter the guest email and booking number for this tour. The link expires after 30 days.</p>
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <form method="POST" action="{{ route('tour_reviews.link.create', $tour->slug) }}">
          @csrf
          <div class="form-group">
            <label for="review-email">Guest email</label>
            <input id="review-email" type="email" name="email" class="form-control" value="{{ old('email', request('email')) }}" required>
          </div>
          <div class="form-group">
            <label for="review-booking">Booking number</label>
            <input id="review-booking" type="text" name="booking" class="form-control" value="{{ old('booking', request('booking')) }}" required>
          </div>
          <button type="submit" class="btn btn-primary">Create link</button>
        </form>
        @isset($reviewLink)
          <div class="form-group mt-3">
            <label for="review-link">Send this link only to the guest</label>
            <textarea id="review-link" class="form-control" rows="3" readonly>{{ $reviewLink }}</textarea>
          </div>
        @endisset
      </div>
    </div>
  </div>
</div>
@endsection
