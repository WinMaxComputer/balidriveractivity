@extends('layouts.default')

@section('meta')
    <title>Booking unavailable</title>
@endsection

@section('content')
<section class="section checkout-section">
    <div class="container">
        <h2>Booking unavailable</h2>
        <p>{{ $message }}</p>
        <a href="{{ route('tour_packages') }}">Browse tours</a>
    </div>
</section>
@endsection
