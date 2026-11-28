@php 
    $des = $destinationDetail[0]->deskripsi ?? '';
    $desk = explode("</p>", $des) ;
@endphp
@extends('layouts.default')
@section('meta')
    @php
        $destination = $destinationDetail[0];
        $seoTitle = ($destination->meta_title ?? '') ?: $destination->name.' | Bali Destinations - Bali Scenic';
        $seoDescription = ($destination->meta_description ?? '') ?: \Illuminate\Support\Str::limit(strip_tags($destination->deskripsi ?? ''), 160);
        $translationsByLanguage = ($destinationTranslations ?? collect())->keyBy('lang');
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    @if (!empty($destination->meta_keyword))
      <meta name="keywords" content="{{ $destination->meta_keyword }}">
    @endif
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('destinations.detail', ['slug' => $destination->slug]) }}">
    <link rel="canonical" href="{{ route('destinations.detail', ['slug' => $destination->slug]) }}">
    @foreach (['en', 'id'] as $language)
      @if (isset($translationsByLanguage[$language]))
        <link rel="alternate" hreflang="{{ $language }}" href="{{ route('destinations.detail', ['locale' => $language, 'slug' => $translationsByLanguage[$language]->slug]) }}">
      @endif
    @endforeach
@endsection
@section('content')

    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(/assets/img/travel/showcase-8.webp);">
      <div class="container position-relative">
        <h1>{{ $destinationDetail[0]->name }}</h1>
        <!-- <p>Esse dolorum voluptatum ullam est sint nemo et est ipsa porro placeat quibusdam quia assumenda numquam molestias.</p> -->
        <nav class="breadcrumbs">
          <ol>
            <li><a href="{{ route('home') }}">Home</a></li>
            <li class="current">Destination Details</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->
    

    <!-- ======= About Section ======= -->
    <section id="about" class="about">
        <div class="container" data-aos="fade-up">

            <!-- <div class="section-header"> -->
            <!-- <h2>About Us</h2> -->
            <!-- <p>Tour Detail </p> -->
            <!-- </div> -->
            <div class="row">
                <div class="col-lg-6 position-relative mt-0" data-aos="fade-up" data-aos-delay="150">
                    <p>{!! $desk[0] !!}</p>
                </div>
                <div class="col-lg-6 position-relative img-blog mt-0" data-aos="fade-up" data-aos-delay="150">
                    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                        <ol class="carousel-indicators">

                        @php $gmbra = explode(";",$destinationDetail[0]->foto) ; @endphp
                        @php $gmbr = array_slice($gmbra, 0, -1) ; @endphp
                        @foreach($gmbr as $value)
                        <li data-target=".carouselExampleCaptions" data-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}"></li>
                        @endforeach
                        <!-- <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
                        <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
                        <li data-target="#carouselExampleIndicators" data-slide-to="2"></li> -->
                        
                        </ol>
                        <div class="carousel-inner">
                        @foreach($gmbr as $key => $slider)
                        <!-- {{ $key }} -->
                        <div class="carousel-item {{$key == 0 ? 'active' : ''}}">
                            <img src="{{ asset('assets/img/destinasi/'. $slider) }}" class="d-block w-100" alt="{{ $destinationDetail[0]->name }}">
                        </div>
                        @endforeach
                        </div>
                        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                        </a>
                    </div>
                    
                </div>
                @php 
                $leng = count($desk) ;
                @endphp
                @for($i = 1;$i < $leng ;$i++)
                <div class="col-lg-12 position-relative mt-0" data-aos="fade-up" data-aos-delay="150">
                    <p>{!! $desk[$i] !!}</p>
                </div>
                @endfor
            </div>
            

        </div>
    </section><!-- End About Section -->

    

@stop