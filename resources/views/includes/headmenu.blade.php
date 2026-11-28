<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="{{ route('home') }}" class="logo d-flex align-items-center me-auto me-xl-0">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <img src="{{ asset('assets/img/logo.webp') }}" alt="">
        <h1 class="sitename">BALI SCENIC</h1>
      </a>
      
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="{{ route('home') }}" class="active">Home</a></li>
          <li><a href="{{ route('travel_guides') }}">{{ __('Travel Guide & Tips') }}</a></li>
          <!-- <li><a href="/activities">{{ __('headmenu.tour_activity') }}</a></li>
          <li><a href="/destinations">{{ __('headmenu.destinations') }}</a></li> -->
          <li><a href="{{ route('transfer') }}">BOOK AIRPORT TRANSFER</a></li>
          <li><a href="{{ route('tracking.form') }}">Track Booking</a></li>
          <li class="dropdown">
            <a href="#"><span>{{ session('currency', 'USD') }}</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="{!! route('currency', ['currency'=>'USD']) !!}">USD</a></li>
              <li><a href="{!! route('currency', ['currency'=>'IDR']) !!}">IDR</a></li>
            </ul>
          </li>
          <li class="dropdown">
            <a href="#"><span>{{ strtoupper(Lang::locale()) }}</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="{{ \App\Support\LocalizedRoutes::languageUrl(request(), 'en') }}">English</a></li>
              <li><a href="{{ \App\Support\LocalizedRoutes::languageUrl(request(), 'id') }}">Indonesian</a></li>
            </ul>
          </li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>


    </div>
  </header>
  <!-- Travel Hero Section -->
  <section id="travel-hero" class="travel-hero section dark-background">

    <div class="hero-background">
      <video autoplay="" muted="" loop="">
        <source src="/assets/img/travel/video-2.mp4" type="video/mp4">
      </video>
      <div class="hero-overlay"></div>
    </div>

    <div class="container position-relative">
      <div class="row align-items-center">
        <div class="col-lg-7">
          <div class="hero-text" data-aos="fade-up" data-aos-delay="100">
            <h1 class="hero-title">{{ __('headmenu.welcome_title') }}</h1>
            <p class="hero-subtitle">{{ __('headmenu.welcome_desc') }}</p>
            <div class="hero-buttons">
              <a href="{{ route('tour_packages') }}" class="btn btn-outline">Browse Tours</a>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="booking-form-wrapper" data-aos="fade-left" data-aos-delay="200">
            <div class="booking-form">
              <h3 class="form-title">{{ __('booking.title_planing') }}</h3>
              <form action="{{ route('tour_packages') }}" method="GET">
                <div class="form-group mb-3 destination-search">
                  <input type="text" name="destination" id="destination" class="form-control" placeholder="Search destinations, activities, mixes or products" autocomplete="off" aria-controls="popular-destinations" aria-expanded="false" required>
                  <div class="destination-suggestions" id="popular-destinations" hidden>
                    <p class="destination-suggestions-title">Popular searches</p>
                    <div class="destination-suggestion-groups">
                      <div class="destination-suggestion-group" data-suggestion-group>
                        <p class="destination-suggestion-category">Destinations</p>
                        <div class="destination-suggestions-list" role="listbox" aria-label="Popular destinations">
                          @foreach (($destination ?? collect())->take(5) as $item)
                            <button type="button" role="option">{{ $item->name }}</button>
                          @endforeach
                        </div>
                      </div>
                      <div class="destination-suggestion-group" data-suggestion-group>
                        <p class="destination-suggestion-category">Activities</p>
                        <div class="destination-suggestions-list" role="listbox" aria-label="Popular activities">
                          @foreach (($activities ?? collect())->take(5) as $item)
                            <button type="button" role="option">{{ $item->name }}</button>
                          @endforeach
                        </div>
                      </div>
                      <div class="destination-suggestion-group" data-suggestion-group>
                        <p class="destination-suggestion-category">Mix</p>
                        <div class="destination-suggestions-list" role="listbox" aria-label="Popular mix packages">
                          @foreach (($product ?? collect())->take(5) as $item)
                            <button type="button" role="option">{{ $item->product_name }}</button>
                          @endforeach
                        </div>
                      </div>
                      <div class="destination-suggestion-group" data-suggestion-group>
                        <p class="destination-suggestion-category">Products</p>
                        <div class="destination-suggestions-list" role="listbox" aria-label="Popular products">
                          @foreach (($product ?? collect())->take(5) as $item)
                            <button type="button" role="option">{{ $item->product_name }}</button>
                          @endforeach
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label for="checkin">Date</label>
                      <div class="date-picker-field">
                        <i class="bi bi-calendar-heart-fill date-picker-icon" aria-hidden="true"></i>
                        <input type="text" name="checkin" id="checkin" class="form-control" data-datepicker placeholder="Choose a date" autocomplete="off" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-3">
                      <label for="adults">Adults</label>
                      <select name="adults" id="adults" class="form-select" required="">
                        <option value="1">1 Adult</option>
                        <option value="2">2 Adults</option>
                        <option value="3">3 Adults</option>
                        <option value="4">4+ Adults</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="row">
                  
                  
                </div>


                <button type="submit" class="btn btn-primary w-100">{{ __('booking.submit') }}</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

  </section><!-- /Travel Hero Section -->