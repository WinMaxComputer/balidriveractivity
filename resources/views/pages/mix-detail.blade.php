@extends('layouts.default')
@section('meta')
    <title>Bali Best Activity - Bali Driver Activity</title>
    <meta content="bali destinations, visit bali interesting place" name="description">
    <meta content="{{ $mixDetail[0]->meta_keyword ?: 'bali good tour, car rental bali, ubud tour, bali tour '.$mixDetail[0]->slug }}" name="keywords">

@endsection
@section('content')
    
    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url('{{ asset('assets/img/travel/showcase-8.webp') }}');">
      <div class="container position-relative">
        <h1>{{ $mixDetail[0]->product_name }}</h1>
        <div class="product-detail-meta">
          @if (!empty($mixDetail[0]->tour_duration))
            <span class="product-detail-duration"><i class="bi bi-clock"></i> Estimated tour: {{ $mixDetail[0]->tour_duration }}</span>
          @endif
          @include('includes.product-recommendation-badge', ['product' => $mixDetail[0], 'inline' => true])
        </div>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="{{ route('home') }}">Home</a></li>
            <li class="current">Tour Details</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

      <!-- Gallery Slider Section -->
      <section id="about" class="about">
        <div class="container">


          @php
            $galleryPhotos = [];
            if (!empty($mixDetail[0]->product_foto)) {
                $galleryPhotos = array_values(array_filter(array_map('trim', explode(';', $mixDetail[0]->product_foto))));
            }
            if (empty($galleryPhotos)) {
                $galleryPhotos = ['1.webp', '2.webp', '3.webp', '4.webp', '5.webp'];
            }
            $featuredPhotos = array_slice($galleryPhotos, 0, 5);
          @endphp

          <style>
            .gallery-showcase {
              display: grid;
              grid-template-columns: 1.8fr 1fr 1fr;
              grid-template-rows: repeat(2, 160px);
              gap: 12px;
              max-width: 980px;
              margin: 0 auto 28px;
            }

            .gallery-showcase-item {
              position: relative;
              overflow: hidden;
              border-radius: 22px;
              box-shadow: 0 18px 35px rgba(17, 24, 39, 0.12);
              background: #f2f2f2;
              cursor: pointer;
            }

            .gallery-showcase-item img {
              width: 100%;
              height: 100%;
              object-fit: cover;
              display: block;
              transition: transform 0.35s ease;
            }

            .gallery-showcase-item:hover img {
              transform: scale(1.05);
            }

            .gallery-showcase-item.featured {
              grid-row: span 2;
            }

            .gallery-overlay {
              position: absolute;
              inset: 0;
              display: flex;
              align-items: center;
              justify-content: center;
              background: rgba(15, 23, 42, 0.45);
              color: #fff;
              font-size: 1.2rem;
              font-weight: 700;
              letter-spacing: 0.04em;
            }

            .gallery-lightbox {
              position: fixed;
              inset: 0;
              background: rgba(15, 23, 42, 0.85);
              display: none;
              align-items: center;
              justify-content: center;
              z-index: 9999;
              padding: 24px;
            }

            .gallery-lightbox.active {
              display: flex;
            }

            .gallery-lightbox img {
              max-width: 90vw;
              max-height: 85vh;
              border-radius: 18px;
              object-fit: contain;
              box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
              background: #fff;
            }

            .gallery-lightbox-close {
              position: absolute;
              top: 18px;
              right: 22px;
              font-size: 2.2rem;
              color: #fff;
              cursor: pointer;
              line-height: 1;
            }

            .gallery-nav-btn {
              position: absolute;
              top: 50%;
              transform: translateY(-50%);
              width: 48px;
              height: 48px;
              border: none;
              border-radius: 50%;
              background: rgba(255, 255, 255, 0.2);
              color: #fff;
              font-size: 2rem;
              display: flex;
              align-items: center;
              justify-content: center;
              cursor: pointer;
              backdrop-filter: blur(4px);
              transition: background 0.2s ease;
            }

            .gallery-nav-btn:hover {
              background: rgba(255, 255, 255, 0.35);
            }

            .gallery-nav-prev {
              left: 24px;
            }

            .gallery-nav-next {
              right: 24px;
            }

            @media (max-width: 768px) {
              .gallery-showcase {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: auto;
              }

              .gallery-showcase-item.featured {
                grid-column: span 2;
                grid-row: auto;
                min-height: 260px;
              }

              .gallery-nav-btn {
                width: 42px;
                height: 42px;
                font-size: 1.5rem;
              }
            }
          </style>

          <div class="gallery-showcase" id="galleryShowcase">
            @foreach ($featuredPhotos as $index => $photo)
              @php
                $photoPath = asset('assets/img/products/' . $photo);
                $isFeatured = $index === 0;
                $showOverlay = $index === 4 && count($galleryPhotos) > 5;
              @endphp

              <div class="gallery-showcase-item {{ $isFeatured ? 'featured' : '' }}" data-image="{{ $photoPath }}" data-index="{{ $index }}" aria-label="Open photo {{ $index + 1 }}">
                <img src="{{ $photoPath }}" alt="Tour photo {{ $index + 1 }}">
                @if ($showOverlay)
                  <div class="gallery-overlay">+{{ count($galleryPhotos) - 5 }} Foto</div>
                @endif
              </div>
            @endforeach
          </div>

          <div class="gallery-lightbox" id="galleryLightbox" aria-label="Photo preview">
            <span class="gallery-lightbox-close" id="galleryLightboxClose" aria-label="Close gallery">&times;</span>
            <button class="gallery-nav-btn gallery-nav-prev" id="galleryPrevBtn" type="button" aria-label="Previous photo">&#8249;</button>
            <img id="galleryLightboxImage" src="" alt="Full size gallery image">
            <button class="gallery-nav-btn gallery-nav-next" id="galleryNextBtn" type="button" aria-label="Next photo">&#8250;</button>
          </div>

          <script>
            document.addEventListener('DOMContentLoaded', function () {
              const modal = document.getElementById('galleryLightbox');
              const modalImage = document.getElementById('galleryLightboxImage');
              const closeBtn = document.getElementById('galleryLightboxClose');
              const prevBtn = document.getElementById('galleryPrevBtn');
              const nextBtn = document.getElementById('galleryNextBtn');
              const items = Array.from(document.querySelectorAll('.gallery-showcase-item'));
              let currentIndex = 0;

              function openLightbox(index) {
                currentIndex = index;
                const imageSrc = items[currentIndex].getAttribute('data-image');
                modalImage.src = imageSrc;
                modal.classList.add('active');
              }

              function closeLightbox() {
                modal.classList.remove('active');
                modalImage.src = '';
              }

              function showNext() {
                if (items.length === 0) return;
                currentIndex = (currentIndex + 1) % items.length;
                modalImage.src = items[currentIndex].getAttribute('data-image');
              }

              function showPrev() {
                if (items.length === 0) return;
                currentIndex = (currentIndex - 1 + items.length) % items.length;
                modalImage.src = items[currentIndex].getAttribute('data-image');
              }

              items.forEach(function (item, index) {
                item.addEventListener('click', function () {
                  openLightbox(index);
                });
              });

              closeBtn.addEventListener('click', closeLightbox);
              prevBtn.addEventListener('click', showPrev);
              nextBtn.addEventListener('click', showNext);

              modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                  closeLightbox();
                }
              });

              document.addEventListener('keydown', function (event) {
                if (!modal.classList.contains('active')) return;

                if (event.key === 'Escape') {
                  closeLightbox();
                }

                if (event.key === 'ArrowRight') {
                  showNext();
                }

                if (event.key === 'ArrowLeft') {
                  showPrev();
                }
              });
            });
          </script>
        </div>
      </section><!-- End Gallery Slider Section -->

    <!-- Travel Tour Details Section -->
    <section id="travel-tour-details" class="travel-tour-details section">

      <div class="container">

      



        <!-- Tour Overview -->
        <div class="tour-overview">
          <div class="row">


            <div class="col-lg-8">
              <h2>Tour Overview</h2>
              @include('includes.rating-summary', ['rating' => $ratings->get($mixDetail[0]->code)])
              {!! $mixDetail[0]->product_des !!}
              <div class="tour-pricing" data-aos="fade-up" data-aos-delay="500">
                  <div class="available-dates">
                    <h3></h3>
                    <div class="tour-benefits-grid">
                      <div class="date-option benefit-low-price">
                        <i class="bi bi-tags-fill" aria-hidden="true"></i>
                        <div class="date">always low price</div>
                        <div class="availability">Best value</div>
                      </div>
                      <div class="date-option benefit-local-guide">
                        <i class="bi bi-person-vcard-fill" aria-hidden="true"></i>
                        <div class="date">local guide</div>
                        <div class="availability">Local expertise</div>
                      </div>
                      <div class="date-option benefit-flexible">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                        <div class="date">free cancel anytime</div>
                        <div class="availability">Flexible booking</div>
                      </div>
                      <div class="date-option benefit-cancellation">
                        <i class="bi bi-shield-check-fill" aria-hidden="true"></i>
                        <div class="date">full refund</div>
                        <div class="availability">book with confidence</div>
                      </div>
                    </div>
                  </div>
              </div>
            </div>
            <div class="col-lg-4 booking-sidebar">
              <div class="tour-highlights ">
                <div class="booking-widget ">
                  <div class="booking-card-compact">
                    <div class="booking-card-heading">
                      <div>
                        <span class="booking-eyebrow">Plan your trip</span>
                        <h4>Quick Booking</h4>
                      </div>
                      <i class="bi bi-calendar2-check-fill booking-card-icon" aria-hidden="true"></i>
                    </div>

                    <div class="mobile-booking-price">
                      <span>Starting from</span>
                      <strong>{{ session('currency', 'USD') }} {{ number_format(convertPrice($mixDetail[0]->price, session('currency', 'USD')), 0) }}</strong>
                    </div>

                    <form class="booking-form-compact" id="mixBookingForm" method="GET" action="{{ route('checkout.show', ['type' => 'product', 'id' => $mixDetail[0]->id]) }}">
                      @if ($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                      @endif
                      <div class="form-group mb-3">
                        <label class="form-label small" for="mix-travel-date">Travel Date</label>
                        <div class="date-picker-field">
                          <i class="bi bi-calendar-heart-fill date-picker-icon" aria-hidden="true"></i>
                          <input type="text" name="travel_date" id="mix-travel-date" class="form-control form-control-sm" data-datepicker placeholder="Choose a date" autocomplete="off" required>
                        </div>
                      </div>
                      <div class="form-group mb-3">
                        <label class="form-label small" for="mix-travelers">Travelers</label>
                        <input type="number" id="mix-travelers" name="travelers" class="form-control form-control-sm" min="1" max="99999" value="1" required>
                      </div>
                      <div class="price-display mb-3">
                        <span>Starting from</span>
                        <strong>{{ session('currency', 'USD') }} {{ number_format(convertPrice($mixDetail[0]->price, session('currency', 'USD')), 0) }}</strong>
                      </div>
                      <button type="submit" class="btn booking-check-btn btn-sm w-100">
                        <i class="bi bi-search me-1" aria-hidden="true"></i> Check Availability
                      </button>
                    </form>

                    <button type="button" class="booking-mobile-toggle" id="bookingMobileToggle" aria-controls="mixBookingForm" aria-expanded="false">
                      <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>
                      Check Availability
                    </button>

                    <a href="https://api.whatsapp.com/send?phone=62818688114&text=Hello%20Bali%20Driver%20Activity" class="booking-whatsapp-btn" target="_blank" rel="noopener">
                      <i class="bi bi-whatsapp me-2" aria-hidden="true"></i>
                      Chat via WhatsApp
                    </a>
                  </div>
                </div>
                <div class="booking-card-heading">
                  <div>
                    <span class="booking-eyebrow">Plan your trip</span>
                    <h4>Things to do in Bali</h4>
                  </div>
                  <i class="bi bi-calendar2-check-fill booking-card-icon" aria-hidden="true"></i>
                </div>
                <!-- <h3 class="tour-keyword-title">Things to do in Bali</h3> -->
                <div class="tour-keyword-tags" aria-label="Things to do in Bali">
                  <span><i class="bi bi-speedometer2" aria-hidden="true"></i> ATV rides</span>
                  <span><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> Ubud</span>
                  <span><i class="bi bi-person-hiking" aria-hidden="true"></i> Trekking</span>
                  <span><i class="bi bi-sunrise-fill" aria-hidden="true"></i> Sunrise tours</span>
                  <span><i class="bi bi-water" aria-hidden="true"></i> Canggu</span>
                  <span><i class="bi bi-droplet-half" aria-hidden="true"></i> Waterfalls</span>
                </div>
              </div>

            </div>

            
            <div class="col-lg-8">
            @if(count($itineraryItems))
              <!-- Itinerary -->
              <div class="tour-itinerary itinerary-section" data-aos="fade-up" data-aos-delay="300">
                <h2>Itinerary</h2>
                <div class="itinerary-timeline">

                @foreach($itineraryItems as $key => $item)
                    <div class="itinerary-item">
                      <div class="day-number">{{ $key + 1 }}</div>
                      <div class="day-content">
                        @if ($item['reference'] !== '')
                          @if ($item['place'] && $item['place']->slug)
                            <h4><a href="{{ route($item['type'] === 'activity' ? 'activities.detail' : 'destinations.detail', $item['place']->slug) }}">{{ $item['place']->name }}</a></h4>
                          @elseif ($item['place'])
                            <h4>{{ $item['place']->name }} (detail unavailable)</h4>
                          @else
                            <h4>{{ ucfirst($item['type']) }} unavailable</h4>
                          @endif
                          @if ($item['photos'])
                            <div class="itinerary-gallery">
                              @foreach ($item['photos'] as $photo)
                                @php $photoUrl = asset('assets/img/'.($item['type'] === 'activity' ? 'activity' : 'destinasi').'/'.$photo); @endphp
                                <a href="{{ $photoUrl }}" class="glightbox" data-gallery="itinerary-{{ $key }}" aria-label="View photo {{ $loop->iteration }} of {{ $item['place']->name }}">
                                  <img src="{{ $photoUrl }}" alt="{{ $item['place']->name }}" loading="lazy">
                                </a>
                              @endforeach
                            </div>
                          @endif
                        @endif
                        @if ($item['note'] !== '')
                          @if ($item['legacy_html'])
                            <div>{!! $item['note'] !!}</div>
                          @else
                            <p>{!! nl2br(e($item['note'])) !!}</p>
                          @endif
                        @endif
                      </div>
                    </div>
                @endforeach
                  
                </div>
              </div>
            @endif
              <!-- Inclusions -->
              <div class="tour-inclusions" data-aos="fade-up" data-aos-delay="400">
                <div class="row">
                  <div class="col-lg-6">
                    <div class="included-section inclusion-panel">
                      <h3><i class="bi bi-check-circle-fill"></i> What's Included</h3>
                      <div class="inclusion-content">{!! $mixDetail[0]->product_included !!}</div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="excluded-section inclusion-panel">
                      <h3><i class="bi bi-x-circle-fill"></i> What's Not Included</h3>
                      <div class="inclusion-content">{!! $mixDetail[0]->product_excluded !!}</div>
                        
                    </div>
                  </div>
                </div>
              </div>

              <!-- Pricing -->
              
              
            </div>
            <div class="col-lg-4">
              <!-- Tour Facts -->
              
            </div>
          </div>
        </div>

        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const bookingSidebar = document.querySelector('.booking-sidebar');
            const bookingCard = bookingSidebar ? bookingSidebar.querySelector('.tour-highlights') : null;
            const detailSection = document.getElementById('travel-tour-details');
            const desktopBreakpoint = 992;
            const headerOffset = 96;

            if (!bookingSidebar || !bookingCard || !detailSection) return;

            function updateBookingPosition() {
              if (window.innerWidth < desktopBreakpoint) {
                bookingSidebar.classList.remove('booking-is-fixed', 'booking-is-bottom');
                bookingCard.style.width = '';
                bookingCard.style.left = '';
                return;
              }

              bookingSidebar.classList.remove('booking-is-fixed', 'booking-is-bottom');
              bookingCard.style.width = '';
              bookingCard.style.left = '';

              const sidebarRect = bookingSidebar.getBoundingClientRect();
              const sectionRect = detailSection.getBoundingClientRect();
              const cardHeight = bookingCard.offsetHeight;
              const shouldFix = sectionRect.top <= headerOffset && sectionRect.bottom > headerOffset + cardHeight;

              if (shouldFix) {
                bookingSidebar.classList.add('booking-is-fixed');
                bookingCard.style.width = `${sidebarRect.width}px`;
                bookingCard.style.left = `${sidebarRect.left}px`;
              } else if (sectionRect.bottom <= headerOffset + cardHeight) {
                bookingSidebar.classList.add('booking-is-bottom');
              }
            }

            window.addEventListener('scroll', updateBookingPosition, { passive: true });
            window.addEventListener('resize', updateBookingPosition);
            updateBookingPosition();
          });
        </script>

        <script>
          
          document.addEventListener('DOMContentLoaded', function () {
            const bookingToggle = document.getElementById('bookingMobileToggle');
            const bookingForm = document.getElementById('mixBookingForm');
            const bookingCard = bookingToggle ? bookingToggle.closest('.booking-card-compact') : null;

            if (!bookingToggle || !bookingForm || !bookingCard) return;

            function updateMobileBookingButton() {
              if (window.innerWidth >= 992 || !bookingForm.classList.contains('is-visible')) return;

              const formRect = bookingForm.getBoundingClientRect();
              const hasPassedForm = formRect.bottom < 80;
              bookingToggle.classList.toggle('is-hidden', !hasPassedForm);
            }

            bookingToggle.addEventListener('click', function () {
              bookingToggle.setAttribute('aria-expanded', 'true');
              bookingToggle.classList.add('is-hidden');
              bookingCard.classList.add('is-open');
              bookingForm.classList.add('is-visible');

              window.setTimeout(function () {
                const targetTop = bookingCard.getBoundingClientRect().top + window.scrollY - 110;
                window.scrollTo({ top: targetTop, behavior: 'smooth' });
                updateMobileBookingButton();
              }, 50);
            });

            window.addEventListener('scroll', updateMobileBookingButton, { passive: true });
            window.addEventListener('resize', updateMobileBookingButton);
            
          });
        </script>

        

        

        

      </div>

    </section><!-- /Travel Tour Details Section --><!-- Page Title -->

    @include('includes.service-reviews')

    

@stop