@extends('layouts.default')
@section('meta')
    <title>{{ $title ?? 'Airport Transfer' }} - {{ config('app.name') }}</title>
    <meta content="package tour bali withh private service, best package tour in bali" name="description">
    <meta content="tour bali, package tour, bali driver, private driver" name="keywords">
@endsection
@section('content')

    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(assets/img/banner/{{ $banner ?? 'act.webp' }});">
      <div class="container position-relative">
        <h1>AIRPORT TRANSFER</h1>
        <p>Esse dolorum voluptatum ullam est sint nemo et est ipsa porro placeat quibusdam quia assumenda numquam molestias.</p>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="/">Home</a></li>
            <li class="current">Airport Transfer</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->
    
    <!-- Travel Tours Section -->
    <section id="travel-tours" class="travel-tours section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row" data-aos="fade-up" data-aos-delay="200">
          <div class="col-12">
            <div class="section-title text-center">
              <h2>Airport Transfer</h2>
              <p>We provide a range of airport transfer services to ensure that your journey to and from the airport is as smooth and stress-free as possible. Our professional drivers will meet you at the airport and take you to your destination in comfort and style.</p>
            </div>
          </div>
        </div>
        
        <div class="row" data-aos="fade-up" data-aos-delay="300">
          <div class="col-lg-7 mb-4 mb-lg-0">
            <div class="table-responsive">
              <table class="table table-striped table-bordered align-middle">
                <caption class="caption-top">Airport transfer prices (IDR per vehicle)</caption>
                <thead class="table-light">
                  <tr>
                    <th scope="col">Destination</th>
                    <th scope="col">One-way price</th>
                    <th scope="col">Return price</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>Kuta</td><td>IDR 150,000</td><td>Please enquire</td></tr>
                  <tr><td>Seminyak</td><td>IDR 200,000</td><td>Please enquire</td></tr>
                  <tr><td>Canggu</td><td>IDR 250,000</td><td>Please enquire</td></tr>
                  <tr><td>Sanur</td><td>IDR 200,000</td><td>Please enquire</td></tr>
                  <tr><td>Nusa Dua</td><td>IDR 200,000</td><td>Please enquire</td></tr>
                  <tr><td>Ubud</td><td>IDR 350,000</td><td>Please enquire</td></tr>
                </tbody>
              </table>
            </div>
            <div class="alert alert-warning small mb-0" role="note"><strong>Remark:</strong> Prices are per vehicle and may vary by pickup time, exact destination, and passenger or luggage requirements. Please enquire for return-trip pricing and confirm the final fare when booking.</div>
          </div>

          <div class="col-lg-5">
            <div class="card h-100">
              <div class="card-body">
                <h3 class="card-title mb-4">Book Your Transfer</h3>
                <form>
                  <div class="mb-3">
                    <label for="transfer-name" class="form-label">Full name</label>
                    <input type="text" class="form-control" id="transfer-name" name="name" autocomplete="name" required>
                  </div>
                  <div class="mb-3">
                    <label for="transfer-email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="transfer-email" name="email" autocomplete="email" required>
                  </div>
                  <div class="mb-3">
                    <label for="transfer-mobile" class="form-label">Mobile number</label>
                    <input type="tel" class="form-control" id="transfer-mobile" name="mobile" autocomplete="tel" required>
                  </div>
                  <div class="mb-3">
                    <label for="transfer-booking-type" class="form-label">Booking type</label>
                    <select class="form-select" id="transfer-booking-type" name="booking_type" required>
                      <option value="" selected disabled>Select a booking type</option>
                      <option value="arrival">Airport arrival (pickup)</option>
                      <option value="departure">Airport departure (drop-off)</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label for="transfer-destination" class="form-label">Destination</label>
                    <input type="text" class="form-control" id="transfer-destination" name="destination" placeholder="e.g. Kuta, Seminyak, Ubud" autocomplete="off" required>
                  </div>
                  <div class="mb-3">
                    <label for="transfer-date" class="form-label">Pickup date</label>
                    <input type="date" class="form-control" id="transfer-date" name="pickup_date" required>
                  </div>
                  <div class="mb-3">
                    <label for="transfer-flight-number" class="form-label">Flight number</label>
                    <input type="text" class="form-control" id="transfer-flight-number" name="flight_number" placeholder="e.g. GA410">
                  </div>
                  <div class="mb-3">
                    <label for="transfer-arrival-time" class="form-label">Arrival time</label>
                    <input type="time" class="form-control" id="transfer-arrival-time" name="arrival_time">
                  </div>
                  <div class="mb-3">
                    <label for="transfer-passengers" class="form-label">Passengers</label>
                    <input type="number" class="form-control" id="transfer-passengers" name="passengers" min="1" value="1" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100">Request Booking</button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- CTA Section -->
        <div class="row" data-aos="fade-up" data-aos-delay="700">
          <div class="col-12">
            <div class="cta-section text-center">
              <h3>Not Sure What to Choose?</h3>
              <p>Our travel experts are here to help you find the perfect tour based on your preferences and budget.</p>
              <div class="cta-buttons">
                <a href="#" class="btn btn-primary me-3">Contact Our Experts</a>
                <a href="#" class="btn btn-outline-primary">Take Our Travel Quiz</a>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Travel Tours Section -->

    @if (config('services.google_maps.key'))
        <style>
            #transfer-destination {
                display: block;
                width: 100%;
                min-height: 38px;
                box-sizing: border-box;
                border: 1px solid color-mix(in srgb, var(--default-color), transparent 82%);
                border-radius: var(--bs-border-radius, 0.375rem);
                background-color: var(--surface-color);
                color: var(--default-color);
                font-family: var(--default-font);
                font-size: 1rem;
                line-height: 1.5;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            #transfer-destination:focus-within {
                border-color: var(--accent-color);
                box-shadow: 0 0 0 0.25rem color-mix(in srgb, var(--accent-color), transparent 80%);
            }

            #transfer-destination::part(input) {
                box-sizing: border-box;
                min-height: 36px;
                padding: 0.375rem 0.75rem;
                border: 0;
                outline: 0;
                background: transparent;
                color: inherit;
                font: inherit;
            }

            #transfer-destination::part(prediction-list) {
                overflow: hidden;
                border: 1px solid color-mix(in srgb, var(--default-color), transparent 85%);
                border-radius: 0.5rem;
                background-color: var(--surface-color);
                box-shadow: 0 0.5rem 1rem rgba(5, 30, 35, 0.15);
                color: var(--default-color);
                font-family: var(--default-font);
            }

            #transfer-destination::part(prediction-item):hover,
            #transfer-destination::part(prediction-item-selected) {
                background-color: color-mix(in srgb, var(--accent-color), transparent 90%);
            }

            #transfer-destination::part(prediction-item-match) {
                color: var(--accent-color);
            }
        </style>
        <script>
            async function initTransferDestinationAutocomplete() {
                const { PlaceAutocompleteElement } = await google.maps.importLibrary('places');
                const destinationInput = document.getElementById('transfer-destination');
                const autocomplete = new PlaceAutocompleteElement({
                    includedRegionCodes: ['id'],
                });

                autocomplete.id = destinationInput.id;
                autocomplete.name = destinationInput.name;
                autocomplete.className = destinationInput.className;
                autocomplete.placeholder = destinationInput.placeholder;
                autocomplete.value = destinationInput.value;
                autocomplete.style.width = '100%';
                autocomplete.setAttribute('aria-required', 'true');
                autocomplete.addEventListener('gmp-select', async function (event) {
                    const place = event.placePrediction.toPlace();
                    await place.fetchFields({ fields: ['formattedAddress', 'displayName'] });
                    autocomplete.value = place.formattedAddress || place.displayName || autocomplete.value;
                    autocomplete.removeAttribute('aria-invalid');
                });

                autocomplete.addEventListener('gmp-error', function (event) {
                    console.error('Google Places autocomplete request failed.', event);
                });

                destinationInput.closest('form').addEventListener('submit', function (event) {
                    if (!autocomplete.value.trim()) {
                        event.preventDefault();
                        autocomplete.setAttribute('aria-invalid', 'true');
                        autocomplete.focus();
                    }
                });

                destinationInput.replaceWith(autocomplete);
            }
        </script>
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google_maps.key')) }}&loading=async&callback=initTransferDestinationAutocomplete">
        </script>
    @endif

@stop