@extends('layouts.default')

@section('meta')
  @php
    $isIndonesian = app()->getLocale() === 'id';
    $pageTitle = $isIndonesian ? 'Panduan Wisata Bali & Tips | Bali Scenic' : 'Bali Travel Guide & Tips | Bali Scenic';
    $pageDescription = $isIndonesian
      ? 'Baca panduan wisata Bali, tips perjalanan, dan rekomendasi lokal dari Bali Scenic.'
      : 'Read Bali travel guides, practical travel tips, and local recommendations from Bali Scenic.';
  @endphp
  <title>{{ $pageTitle }}</title>
  <meta name="description" content="{{ $pageDescription }}">
  <meta property="og:title" content="{{ $pageTitle }}">
  <meta property="og:description" content="{{ $pageDescription }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ route('travel_guides') }}">
  <link rel="canonical" href="{{ route('travel_guides') }}">
  <link rel="alternate" hreflang="en" href="{{ route('travel_guides', ['locale' => 'en']) }}">
  <link rel="alternate" hreflang="id" href="{{ route('travel_guides', ['locale' => 'id']) }}">
@endsection

@section('content')
  <div class="page-title dark-background" data-aos="fade" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
    <div class="container position-relative">
      <h1>{{ __('Travel Guide & Tips') }}</h1>
      <nav class="breadcrumbs" aria-label="Breadcrumb">
        <ol><li><a href="{{ route('home') }}">{{ app()->getLocale() === 'id' ? 'Beranda' : 'Home' }}</a></li><li class="current">{{ __('Travel Guide & Tips') }}</li></ol>
      </nav>
    </div>
  </div>
  <section class="section">
    <div class="container">
      <div class="row gy-4">
        @forelse ($guides as $guide)
          @php $guidePhotos = array_values(array_filter(explode(';', $guide->photos ?? ''), 'strlen')); @endphp
          <article class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm">
              @if ($guidePhotos)
                <img src="{{ asset('assets/img/travel-guide/'.$guidePhotos[0]) }}" class="card-img-top" alt="{{ $guide->title }}" loading="lazy">
              @endif
              <div class="card-body">
                <h2 class="h4"><a href="{{ route('travel_guides.detail', ['slug' => $guide->slug]) }}">{{ $guide->title }}</a></h2>
                <p>{{ $guide->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($guide->content), 150) }}</p>
                <a href="{{ route('travel_guides.detail', ['slug' => $guide->slug]) }}" class="read-more">
                  {{ app()->getLocale() === 'id' ? 'Baca artikel' : 'Read article' }}
                </a>
              </div>
            </div>
          </article>
        @empty
          <div class="col-12"><p>{{ app()->getLocale() === 'id' ? 'Belum ada artikel.' : 'No articles are available yet.' }}</p></div>
        @endforelse
      </div>
      <div class="mt-4">{{ $guides->links() }}</div>
    </div>
  </section>
@endsection
