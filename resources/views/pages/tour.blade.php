@extends('layouts.default')
@section('meta')
    <title>Bali Packages Tour- BALI SCENIC</title>
    <meta content="package tour bali withh private service, best package tour in bali" name="description">
    <meta content="tour bali, package tour, bali driver, private driver" name="keywords">
@endsection
@section('content')

    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(/assets/img/banner/{{ $banner ?? 'act.webp' }});">
      <div class="container position-relative">
        <h1>Find Your Perfect Tour</h1>
        <p>Esse dolorum voluptatum ullam est sint nemo et est ipsa porro placeat quibusdam quia assumenda numquam molestias.</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="{{ route('home') }}">Home</a></li>
            <li class="current">Tours</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->
    
    <!-- Travel Tours Section -->
    <section id="travel-tours" class="travel-tours section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <!-- <div class="row">
          <div class="col-lg-8 mx-auto text-center mb-5">
            <h2>Find Your Perfect Tour</h2>
            <p>Discover unforgettable travel experiences with our curated collection of tours. Explore by destination, travel style, or date to find the adventure that's perfect for you.</p>
          </div>
        </div> -->

        <!-- Tour Filters -->
        <!-- <div class="row mb-5" data-aos="fade-up" data-aos-delay="200">
          <div class="col-12">
            <div class="tour-filters">
              <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                  <select class="form-select">
                    <option value="">Select Destination</option>
                    <option value="paris">Paris</option>
                    <option value="bali">Bali</option>
                    <option value="tokyo">Tokyo</option>
                    <option value="rome">Rome</option>
                    <option value="thailand">Thailand</option>
                  </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                  <select class="form-select">
                    <option value="">Tour Type</option>
                    <option value="adventure">Adventure</option>
                    <option value="luxury">Luxury</option>
                    <option value="family">Family</option>
                    <option value="cultural">Cultural</option>
                    <option value="beach">Beach</option>
                  </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                  <select class="form-select">
                    <option value="">Duration</option>
                    <option value="1-3">1-3 Days</option>
                    <option value="4-7">4-7 Days</option>
                    <option value="8-14">8-14 Days</option>
                    <option value="15+">15+ Days</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div> -->

        @if ($searchTerm !== '')
          <div class="alert alert-info" role="status">
            Search results for <strong>{{ $searchTerm }}</strong>
            @if ($product->isEmpty() && $tour->isEmpty())
              — no matching tours or packages found.
            @endif
          </div>
        @endif

        @if ($searchTerm === '')
        <!-- Tour Categories -->
        <div class="row mb-5" data-aos="fade-up" data-aos-delay="300">
          <div class="col-12">
            <h3 class="section-subtitle mb-4">Tour Categories</h3>
            <div class="tour-categories d-flex flex-wrap gap-3">
              <a href="#" class="tour-category">Adventure</a>
              <a href="#" class="tour-category">Cultural</a>
              <a href="#" class="tour-category">Luxury</a>
              <a href="#" class="tour-category">Family</a>
              <a href="#" class="tour-category">Beach</a>
              <a href="#" class="tour-category">Wildlife</a>
              <a href="#" class="tour-category">Cruise</a>
              <a href="#" class="tour-category">Hiking</a>
            </div>
          </div>
        </div>
        @endif

        <section class="home-tour-list">
          <h3 class="section-subtitle mb-4">{{ $searchTerm !== '' ? 'Matching Mix Tours and Products' : 'All Mix Tours' }}</h3>
          <div class="row gy-6">
              @foreach ($product as $prd)
              <div class="col-lg-3 col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="tour-card">
                  <div class="tour-image">
                    @php $gmbr = explode(";",$prd->product_foto) ; @endphp
                    <img src="{{ asset('assets/img/products/'.$gmbr[0]) }}" alt="{{ $prd->product_name }}" class="img-fluid" loading="lazy">
                    @include('includes.product-recommendation-badge', ['product' => $prd])
                    <div class="tour-price">{{ session('currency', 'USD') }} {{ number_format(convertPrice($prd->price, session('currency', 'USD')), 0) }} / Pax</div>
                  </div>
                  <div class="tour-content">
                    <h5>{{ $prd->product_name }}</h5>
                    <div class="tour-meta">
                      @if (!empty($prd->tour_duration))
                        <span class="duration"><i class="bi bi-clock"></i> {{ $prd->tour_duration }}</span>
                      @endif
                      <span class="group-size"><i class="bi bi-people"></i> Max 6</span>
                    </div>
                    <div class="tour-highlights">
                      @php $parentTypes = explode(';', $prd->parent_type); @endphp
                      @foreach ($activities as $activity)
                        @if (in_array($activity->code, $parentTypes))
                          <span>{{ $activity->name }}</span>
                        @endif
                      @endforeach
                    </div>
                    <div class="tour-action">
                      <a href="{{ route('mix_packages.detail', $prd->slug) }}" class="btn-book">View Tour</a>
                      @include('includes.rating-summary', ['rating' => $ratings->get($prd->code)])
                    </div>
                  </div>
                </div>
              </div>
              @endforeach
              @if ($product->isEmpty() && $searchTerm !== '')
                <p>No matching mix tours or products found.</p>
              @endif
          </div>
        </section>
        
        <!-- Pagination 2 Section -->
        <section >
          <div class="container">
              {{ $product->links('pagination::bootstrap-5') }}
          </div>
        </section><!-- /Pagination 2 Section -->

        @if ($searchTerm === '')
        <!-- Special Offers -->
        <div class="row mb-5" data-aos="fade-up" data-aos-delay="500">
          <div class="col-12">
            <div class="special-offers">
              <h3 class="section-subtitle mb-4">Last-Minute Deals</h3>
              <div class="row">
                <div class="col-lg-6 mb-4">
                  <div class="offer-banner">
                    <div class="offer-content">
                      <div class="discount-badge">30% OFF</div>
                      <h4>Mediterranean Cruise</h4>
                      <p>Explore Italy, Greece, and Spain with luxury accommodations and gourmet dining.</p>
                      <span class="urgency">Only 3 seats left!</span>
                      <a href="booking.html" class="btn btn-accent">Book Now</a>
                    </div>
                    <div class="offer-image">
                      <img src="/assets/img/travel/misc-12.webp" alt="Cruise" class="img-fluid">
                    </div>
                  </div>
                </div>
                <div class="col-lg-6 mb-4">
                  <div class="offer-banner">
                    <div class="offer-content">
                      <div class="discount-badge">25% OFF</div>
                      <h4>African Safari</h4>
                      <p>Witness the great migration and encounter magnificent wildlife in their natural habitat.</p>
                      <span class="urgency">Limited time offer!</span>
                      <a href="booking.html" class="btn btn-accent">Book Now</a>
                    </div>
                    <div class="offer-image">
                      <img src="/assets/img/travel/misc-8.webp" alt="Safari" class="img-fluid">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        

        @endif

      </div>

    </section><!-- /Travel Tours Section -->

    

@stop