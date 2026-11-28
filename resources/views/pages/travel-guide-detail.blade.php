@extends('layouts.default')

@section('meta')
  @php
    $seoTitle = $guide->meta_title ?: $guide->title.' | Travel Guide & Tips - Bali Scenic';
    $seoDescription = $guide->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($guide->content), 160);
    $translationByLanguage = $translations->keyBy('lang');
  @endphp
  <title>{{ $seoTitle }}</title>
  <meta name="description" content="{{ $seoDescription }}">
  <meta property="og:title" content="{{ $seoTitle }}">
  <meta property="og:description" content="{{ $seoDescription }}">
  <meta property="og:type" content="article">
  <meta property="og:url" content="{{ route('travel_guides.detail', ['slug' => $guide->slug]) }}">
  @if ($guide->meta_keywords)
    <meta name="keywords" content="{{ $guide->meta_keywords }}">
  @endif
  <link rel="canonical" href="{{ route('travel_guides.detail', ['slug' => $guide->slug]) }}">
  @foreach (['en', 'id'] as $language)
    @if (isset($translationByLanguage[$language]))
      <link rel="alternate" hreflang="{{ $language }}" href="{{ route('travel_guides.detail', ['locale' => $language, 'slug' => $translationByLanguage[$language]->slug]) }}">
    @endif
  @endforeach
@endsection

@section('content')
  @php $guidePhotos = array_values(array_filter(explode(';', $guide->photos ?? ''), 'strlen')); @endphp
  <div class="page-title dark-background" data-aos="fade" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
    <div class="container position-relative">
      <h1>{{ $guide->title }}</h1>
      <nav class="breadcrumbs" aria-label="Breadcrumb">
        <ol>
          <li><a href="{{ route('home') }}">{{ app()->getLocale() === 'id' ? 'Beranda' : 'Home' }}</a></li>
          <li><a href="{{ route('travel_guides') }}">{{ __('Travel Guide & Tips') }}</a></li>
          <li class="current">{{ $guide->title }}</li>
        </ol>
      </nav>
    </div>
  </div>
  <article class="section">
    <div class="container" style="max-width: 900px;">
      <h1 class="mb-4">{{ $guide->title }}</h1>
      @if ($guidePhotos)
        <div class="row g-3 mb-4">
          @foreach ($guidePhotos as $photo)
            <div class="col-md-6">
              <img src="{{ asset('assets/img/travel-guide/'.$photo) }}" alt="{{ $guide->title }}" class="img-fluid rounded" loading="lazy">
            </div>
          @endforeach
        </div>
      @endif
      <div class="travel-guide-content">{!! $guide->content !!}</div>
    </div>
  </article>
@endsection
