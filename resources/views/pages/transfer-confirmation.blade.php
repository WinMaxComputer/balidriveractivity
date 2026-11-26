@extends('layouts.default')

@section('meta')
    <title>Transfer request received</title>
@endsection

@section('content')
<div class="page-title dark-background" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
    <div class="container position-relative">
        <h1>Airport Transfer</h1>
    </div>
</div>
<section class="section py-5">
    <div class="container">
        <h2>Transfer request received</h2>
        <p>Thank you. Your transfer request has been received and is awaiting confirmation. The final fare will be confirmed with you.</p>
        <a href="{{ route('transfer') }}">Return to Airport Transfer</a>
    </div>
</section>
@endsection
