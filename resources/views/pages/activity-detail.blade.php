@extends('layouts.default')
@section('meta')
    @php
        $activity = $activityDetail[0];
        $seoTitle = ($activity->meta_title ?? '') ?: $activity->name.' | Bali Activities - Bali Scenic';
        $seoDescription = ($activity->meta_description ?? '') ?: \Illuminate\Support\Str::limit(strip_tags($activity->deskripsi ?? ''), 160);
        $translationsByLanguage = ($activityTranslations ?? collect())->keyBy('lang');
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    @if (!empty($activity->meta_keyword))
      <meta name="keywords" content="{{ $activity->meta_keyword }}">
    @endif
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('activities.detail', ['slug' => $activity->slug]) }}">
    <link rel="canonical" href="{{ route('activities.detail', ['slug' => $activity->slug]) }}">
    @foreach (['en', 'id'] as $language)
      @if (isset($translationsByLanguage[$language]))
        <link rel="alternate" hreflang="{{ $language }}" href="{{ route('activities.detail', ['locale' => $language, 'slug' => $translationsByLanguage[$language]->slug]) }}">
      @endif
    @endforeach
@endsection


@php 
$des = $activityDetail[0]->deskripsi ?? '';
$desk = explode("</p>", $des) ;
@endphp
@section('content')   
    <!-- Page Title -->
    <div class="page-title dark-background" data-aos="fade" style="background-image: url(/assets/img/travel/showcase-8.webp);">
      <div class="container position-relative">
        <h1>{{ $activityDetail[0]->name }}</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="{{ route('home') }}">Home</a></li>
            <li class="current">Activity Details</li>
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
                <div class="col-lg-12 position-relative mt-0" data-aos="fade-up" data-aos-delay="150">
                    <h4>{{ $activityDetail[0]->name }}</h4>
                </div>
                <div class="col-lg-6 position-relative mt-0" data-aos="fade-up" data-aos-delay="150">
                    <p>{!! $desk[0] !!}</p>
                </div>
                @php
                    $activityPhotos = [];
                    if (!empty($activityDetail[0]->foto)) {
                        $activityPhotos = array_values(array_filter(array_map('trim', explode(';', $activityDetail[0]->foto))));
                    }
                    if (empty($activityPhotos)) {
                        $activityPhotos = ['1.webp', '2.webp', '3.webp', '4.webp', '5.webp'];
                    }
                    $featuredActivityPhotos = array_slice($activityPhotos, 0, 5);
                @endphp

                <div class="col-lg-6 position-relative about-img mt-0" data-aos="fade-up" data-aos-delay="150">
                    <style>
                        .activity-gallery-showcase {
                            display: grid;
                            grid-template-columns: 1.8fr 1fr 1fr;
                            grid-template-rows: repeat(2, 150px);
                            gap: 12px;
                            max-width: 100%;
                            margin: 0 auto;
                        }

                        .activity-gallery-item {
                            position: relative;
                            overflow: hidden;
                            border-radius: 20px;
                            box-shadow: 0 18px 35px rgba(17, 24, 39, 0.12);
                            background: #f2f2f2;
                            cursor: pointer;
                        }

                        .activity-gallery-item img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                            display: block;
                            transition: transform 0.35s ease;
                        }

                        .activity-gallery-item:hover img {
                            transform: scale(1.05);
                        }

                        .activity-gallery-item.featured {
                            grid-row: span 2;
                        }

                        .activity-gallery-overlay {
                            position: absolute;
                            inset: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background: rgba(15, 23, 42, 0.45);
                            color: #fff;
                            font-size: 1.15rem;
                            font-weight: 700;
                            letter-spacing: 0.04em;
                        }

                        .activity-gallery-lightbox {
                            position: fixed;
                            inset: 0;
                            background: rgba(15, 23, 42, 0.85);
                            display: none;
                            align-items: center;
                            justify-content: center;
                            z-index: 9999;
                            padding: 24px;
                        }

                        .activity-gallery-lightbox.active {
                            display: flex;
                        }

                        .activity-gallery-lightbox img {
                            max-width: 90vw;
                            max-height: 85vh;
                            border-radius: 18px;
                            object-fit: contain;
                            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
                            background: #fff;
                        }

                        .activity-gallery-close {
                            position: absolute;
                            top: 18px;
                            right: 22px;
                            font-size: 2.2rem;
                            color: #fff;
                            cursor: pointer;
                            line-height: 1;
                        }

                        .activity-gallery-nav-btn {
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

                        .activity-gallery-nav-btn:hover {
                            background: rgba(255, 255, 255, 0.35);
                        }

                        .activity-gallery-nav-prev {
                            left: 24px;
                        }

                        .activity-gallery-nav-next {
                            right: 24px;
                        }

                        @media (max-width: 768px) {
                            .activity-gallery-showcase {
                                grid-template-columns: 1fr 1fr;
                                grid-template-rows: auto;
                            }

                            .activity-gallery-item.featured {
                                grid-column: span 2;
                                grid-row: auto;
                                min-height: 260px;
                            }

                            .activity-gallery-nav-btn {
                                width: 42px;
                                height: 42px;
                                font-size: 1.5rem;
                            }
                        }
                    </style>

                    <div class="activity-gallery-showcase" id="activityGalleryShowcase">
                        @foreach($featuredActivityPhotos as $index => $photo)
                            @php
                                $photoPath = asset('assets/img/activity/' . $photo);
                                $showOverlay = $index === 4 && count($activityPhotos) > 5;
                            @endphp

                            <div class="activity-gallery-item {{ $index === 0 ? 'featured' : '' }}" data-image="{{ $photoPath }}" data-index="{{ $index }}" aria-label="Open activity photo {{ $index + 1 }}">
                                <img src="{{ $photoPath }}" alt="Activity photo {{ $index + 1 }}">
                                @if ($showOverlay)
                                    <div class="activity-gallery-overlay">+{{ count($activityPhotos) - 5 }} Foto</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="activity-gallery-lightbox" id="activityGalleryLightbox" aria-label="Activity photo preview">
                        <span class="activity-gallery-close" id="activityGalleryClose" aria-label="Close gallery">&times;</span>
                        <button class="activity-gallery-nav-btn activity-gallery-nav-prev" id="activityGalleryPrevBtn" type="button" aria-label="Previous photo">&#8249;</button>
                        <img id="activityGalleryLightboxImage" src="" alt="Full size activity image">
                        <button class="activity-gallery-nav-btn activity-gallery-nav-next" id="activityGalleryNextBtn" type="button" aria-label="Next photo">&#8250;</button>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const modal = document.getElementById('activityGalleryLightbox');
                            const modalImage = document.getElementById('activityGalleryLightboxImage');
                            const closeBtn = document.getElementById('activityGalleryClose');
                            const prevBtn = document.getElementById('activityGalleryPrevBtn');
                            const nextBtn = document.getElementById('activityGalleryNextBtn');
                            const items = Array.from(document.querySelectorAll('.activity-gallery-item'));
                            let currentIndex = 0;

                            function openLightbox(index) {
                                if (!items.length) return;
                                currentIndex = index;
                                modalImage.src = items[currentIndex].getAttribute('data-image');
                                modal.classList.add('active');
                            }

                            function closeLightbox() {
                                modal.classList.remove('active');
                                modalImage.src = '';
                            }

                            function showNext() {
                                if (!items.length) return;
                                currentIndex = (currentIndex + 1) % items.length;
                                modalImage.src = items[currentIndex].getAttribute('data-image');
                            }

                            function showPrev() {
                                if (!items.length) return;
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
                </div>

                <style>
                    .activity-products {
                        margin-top: 32px;
                    }

                    .activity-product-card {
                        width: 100%;
                        height: 100%;
                        padding-bottom: 18px;
                        overflow: hidden;
                    }

                    .activity-product-card .member-img {
                        position: relative;
                        height: 180px;
                        overflow: hidden;
                    }

                    .activity-product-card .member-img img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }

                    .activity-product-card .member-info {
                        padding: 16px 18px 4px;
                    }

                    .activity-product-card .member-info h4 {
                        margin-bottom: 8px;
                        font-size: 1.05rem;
                    }

                    .activity-product-card .member-info p {
                        margin-bottom: 0;
                        font-size: 0.9rem;
                        line-height: 1.55;
                    }

                    .activity-product-card .price {
                        margin: 8px 18px 14px;
                    }

                    .activity-product-card .activity-booking {
                        width: auto;
                        margin: 0 18px;
                    }

                    .activity-product-card .activity-booking a {
                        display: inline-block;
                    }
                </style>

                <div class="row activity-products">
                @php $code = $activityDetail[0]->code ; @endphp

                @foreach($products as $prod)
                @php $pr = explode(";", $prod->parent_type); @endphp
                    @if(in_array($code, $pr))

                    <!-- Menu Item -->
                    <div class="col-xl-3 col-lg-4 col-md-6 d-flex align-items-stretch mb-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="chef-member activity-product-card">
                        <div class="member-img">
                            @php $gmbr = explode(";",$prod->product_foto) ; @endphp
                            @php $desa = explode("</p>", $prod->product_des) ; @endphp
                            <img src="{{ asset('assets/img/products/'.$gmbr[0]) }}" class="img-fluid" alt="">
                            @include('includes.product-recommendation-badge', ['product' => $prod])
                            <div class="social">
                            </div>
                        </div>
                        <div class="member-info">
                            <h4>{{ $prod->product_name }}</h4>
                            @if (!empty($prod->tour_duration))
                                <span class="duration"><i class="bi bi-clock"></i> {{ $prod->tour_duration }}</span>
                            @endif
                            <!-- <span>Cook</span> -->
                            <p>{!! cutText($desa[0], 300, 1) !!}</p>
                        </div>
                        <p class="price">USD {{ number_format($prod->price, 2) }} / person</p>
                        <div class="form-group d-flex activity-booking">
                            <a href="{{ route('checkout.show', ['type' => 'product', 'id' => $prod->id]) }}" class="btn-book-a-table">Book Now</a>
                        </div>
                        </div>
                        
                    </div><!-- End Chefs Member -->                    
                    @endif
                
                @endforeach
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
   
        <div class="modal fade" id="trModal-booking" tabindex="-1000" aria-labelledby="trModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trModalLabel">Form Booking</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal()">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4><span id="judul_product"></span></h4>
                <form cautocomplete="off" >
                    <div class="row">
                        <div class="col-xl-6 form-group">
                            <input type="hidden" name="code" class="form-control" id="code" value="{{ $prod->code }}">
                        <input type="text" name="name_act" class="form-control" id="name_act" placeholder="Name" required>
                        </div>
                            <div class="col-xl-6 form-group">
                            <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-6 form-group">
                            <input type="number" class="form-control" name="mobile" id="mobile" placeholder="mobile" required>
                            <p><small>Include the + symbol, country code, and area code.</small></p>  
                        </div>
                        <div class="col-xl-6 form-group">
                        <input type="hidden" name="nationality" class="form-control" id="nationality" required>
                        <select id="country_name" class="form-control" onchange="getComboA(this)" required>
                            <option value="">Nationality</option>
                            @foreach($country as $count)
                            <option value="{{ $count->country_code }}">{{ $count->country_name }}</option>
                            @endforeach
                        </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        
                        <div class="col-xl-2 form-group">
                            Adult
                            <input type="number" name="adult" class="form-control" id="adult" placeholder="Adult" required>
                        </div>

                        <div class="col-xl-4 form-group">
                            Cek In 
                            <input class="form-control" name="datefilter" id="datefilter" required>
                            <input type="hidden" class="form-control" name="tgl_reservasi" id="tgl_reservasi" required>
                            <input type="hidden" name="room_no" id="room_no" >
                            <input type="hidden" name="rate_dolar" id="rate_dolar" required>
                        </div>
                        <div class="col-xl-6 form-group">
                            Payment Type
                            <select name="tipe_bayar" id="tipe_bayar" class="form-control" onchange="getOption()">
                                <option value="deposit">Deposit</option>
                                <option value="full">Full Payment</option>
                            </select>
                        </div>
                    </div>
                </form>

                
            </div>
            <!-- <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div> -->
            </div>
        </div>
        </div>
    

    @section('scripts')
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded",  
        
        function () { 
            console.log('page loaded');
            // Code to be executed when the DOM is ready
            // document.getElementById('adult').max = 2;
            document.getElementById('tgl_reservasi').value = moment().format('YYYY-MM-DD h:mm:ss'); // new Date(); 
            const tipe = document.getElementById('tipe_bayar').value ;

            var guestData = JSON.parse(localStorage.getItem('guest'));
            if(guestData.name !== ""){
                document.getElementById('name_act').value =  guestData.name;
                document.getElementById('email').value =  guestData.email;
                document.getElementById('mobile').value =  guestData.phone;
                document.querySelector('#nationality').value = guestData.nationality;
                document.querySelector('#country_name').value = guestData.nationality;
            }
            
            // console.log(guestData.name);
        }, false);
        let id;
        function bookNow(selectObject){
            // id = $(this).data("id");
            // console.log(selectObject.id);
            $.ajax({
                type: "POST",
                url: "/get-product",
                data: { "code": selectObject.id },
                error: function (request, error) {
                    // console.log(arguments);
                },
                success: function (result) {
                    // console.log(result.data)
                    $("#judul_product").text(result.data.product_name);
                    $('#trModal-booking').modal('show');

                },
                // dataType: "json"
            });
            
        }
        function closeModal(){
            $('#trModal-booking').modal('hide');
        }
    
    </script>
    @endsection
@stop