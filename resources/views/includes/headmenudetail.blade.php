<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="/" class="logo d-flex align-items-center me-auto me-xl-0">
        <!-- Uncomment the line below if you also wish to use an image logo -->
        <img src="{{ asset('assets/img/logo.webp') }}" alt="">
        <h1 class="sitename">BALI SCENIC</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="/" class="active">Home</a></li>
          <!-- <li><a href="/activities">{{ __('headmenu.tour_activity') }}</a></li>
          <li><a href="/destinations">{{ __('headmenu.destinations') }}</a></li> -->
          <li><a href="/tour_packages">{{ __('headmenu.tour_package') }}</a></li>
          
          <li><a href="/transfer">BOOK AIRPORT TRANSFER</a></li>
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
              <li><a href="{!! route('lang', ['lang'=>'en', 'tabel'=>$tabel, 'slug' => $slug]) !!}">English</a></li>
              <li><a href="{!! route('lang', ['lang'=>'id', 'tabel'=>$tabel, 'slug' => $slug]) !!}">Indonesian</a></li>
            </ul>
          </li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>


    </div>
  </header>