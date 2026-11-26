<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
@include('includes.head')
@yield('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@yield('media')
</head>

@unless (Route::is('tour_reviews.form') || Route::is('product_reviews.form') || Route::is('customer.*') || Route::is('tracking.*') || Route::is('transfer.confirmation'))
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-YGQ91MNY00"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-YGQ91MNY00');
</script>
@endunless
<body>

  <!-- ======= Header ======= -->
  @if (Route::is('home'))
    @include('includes.headmenu')
  @elseif (Route::is('blog') || Route::is('blog.detail'))
    @include('includes.headmenudetail', ['banner' => '1.webp' ?? null, 'judul' => 'Blog' ?? null])
  @elseif (Route::is('products') || Route::is('product.detail'))
    @include('includes.headmenudetail', ['banner' => '1.webp' ?? null, 'judul' => 'Our Products' ?? null])
  @elseif (Route::is('about-us'))
    @include('includes.headmenudetail', ['banner' => '2.webp' ?? null, 'judul' => 'About Us' ?? null])
  @elseif (Route::is('service'))
    @include('includes.headmenudetail', ['banner' => '3.webp' ?? null, 'judul' => 'Our Services' ?? null])
  @elseif (Route::is('destinations') || Route::is('destinations.detail'))
    @include('includes.headmenudetail', ['banner' => '1.webp' ?? null, 'judul' => 'Bali Destinations' ?? null])
  @elseif (Route::is('activities') || Route::is('activities.detail'))
    @include('includes.headmenudetail', ['banner' => '1.webp' ?? null, 'judul' => 'Bali Activities' ?? null])
  @elseif (Route::is('tour_packages') || Route::is('tour_packages.detail') || Route::is('tour_reviews.form') || Route::is('mix_packages') || Route::is('mix_packages.detail') || Route::is('product_reviews.form'))
    @include('includes.headmenudetail', ['banner' => '1.webp' ?? null, 'judul' => 'Mix Packages' ?? null])
  @elseif (Route::is('events') || Route::is('transport'))
    @include('includes.headmenudetail', ['banner' => '1.webp', 'judul' => Route::is('events') ? 'Events' : 'Transport'])
  @elseif (Route::is('transfer') || Route::is('transfer.*'))
    @include('includes.headmenudetail', ['banner' => '5.webp' ?? null, 'judul' => 'Airport Transfer' ?? null])
  @elseif (Route::is('checkout.*'))
    @include('includes.headmenudetail', ['banner' => '1.webp', 'judul' => 'Checkout', 'tabel' => '', 'slug' => ''])
  @elseif (Route::is('customer.*') || Route::is('tracking.*'))
    @include('includes.headmenudetail', ['banner' => '1.webp', 'judul' => 'Your Booking', 'tabel' => '', 'slug' => ''])
  @endif 


  <main id="main">
    @include('pages.notification')
    @if (session('locale_message'))
      <div class="container"><div class="alert alert-info" role="status">{{ session('locale_message') }}</div></div>
    @endif
    <!-- ======= About Section ======= -->
    @yield('content')
    @yield('scripts')
    <!-- End About Section -->
      <div class="popup-container" id="popupContainer">
          <a href="https://api.whatsapp.com/send?phone=62818688114&text=Hello&source=&data=" class="whatsApp" target="_blank">
          <img src="{{asset('assets/img/whatsapp.png')}}" alt="WhatsApp" />
          </a>
          <div class="popup-content" id="popupContent">
              <img src="{{asset('assets/img/wa.png')}}" alt="Scan Me" /><br />
              Scan Me
          </div>
      </div>
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer position-relative dark-background">
  @include('includes.footer')

  </footer><!-- End Footer -->
  <!-- End Footer -->

  @include('includes.footerjs')

</body>

</html>