<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Transport;
use App\Models\Destination;
use App\Models\TourPackage;
use App\Models\Package;
use App\Models\Artikel;
use App\Models\Gallery;
use App\Models\Rate;
use App\Models\Review;
use App\Support\ProductItinerary;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class bookingController extends Controller
{
    //

    public function tour(Request $request){
        $perPage = $request->query('per_page', 100);
        $defaultLocale = app()->getLocale();
        $searchTerm = trim((string) $request->query('destination', ''));
        $destinationCodes = collect();
        $activityCodes = collect();

        if ($searchTerm !== '') {
            $destinationCodes = DB::table('destinations')
                ->where('lang', $defaultLocale)
                ->where('name', 'like', '%' . $searchTerm . '%')
                ->pluck('code');
            $activityCodes = DB::table('activities')
                ->where('lang', $defaultLocale)
                ->where('name', 'like', '%' . $searchTerm . '%')
                ->pluck('code');
        }

        $matchesCodes = function ($query, $column, $codes) {
            foreach ($codes as $code) {
                $query->orWhere($column, 'like', $code . ';%')
                    ->orWhere($column, 'like', '%;' . $code . ';%');
            }
        };

        $productQuery = DB::table('products')->where('lang', $defaultLocale);
        $tourQuery = TourPackage::join('tour_fotos', 'tour_fotos.code', 'tour_packages.code')
            ->where('tour_packages.lang', $defaultLocale)
            ->select('tour_packages.*', 'tour_fotos.foto');

        if ($searchTerm !== '') {
            $productQuery->where(function ($query) use ($searchTerm, $destinationCodes, $activityCodes, $matchesCodes) {
                $query->where('product_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('product_des', 'like', '%' . $searchTerm . '%')
                    ->orWhere(function ($codeQuery) use ($destinationCodes, $matchesCodes) {
                        $matchesCodes($codeQuery, 'dst_type', $destinationCodes);
                    })
                    ->orWhere(function ($codeQuery) use ($activityCodes, $matchesCodes) {
                        $matchesCodes($codeQuery, 'parent_type', $activityCodes);
                    });
            });

            $tourQuery->where(function ($query) use ($searchTerm, $destinationCodes, $activityCodes, $matchesCodes) {
                $query->where('tour_packages.tour_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('tour_packages.itinerary', 'like', '%' . $searchTerm . '%')
                    ->orWhere(function ($codeQuery) use ($destinationCodes, $matchesCodes) {
                        $matchesCodes($codeQuery, 'tour_packages.destination', $destinationCodes);
                    })
                    ->orWhere(function ($codeQuery) use ($activityCodes, $matchesCodes) {
                        $matchesCodes($codeQuery, 'tour_packages.activity', $activityCodes);
                    });
            });
        }

        $products = $productQuery->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        $tur = $tourQuery->get();
        $activities = DB::table('activities')->where('lang', $defaultLocale)
                    ->join('activity_fotos', 'activity_fotos.code', 'activities.code')
                    ->select('activities.*', 'activity_fotos.foto')
                    ->get();
        $ratings = Review::ratingSummaries($products->getCollection()->pluck('code')->merge($tur->pluck('code')));
        // $products = DB::table('products')->orderBy('id', 'desc')->get();
        return view('pages.tour', [
            'tour' => $tur,
            'activities' => $activities,
            'product' => $products,
            'searchTerm' => $searchTerm,
            'ratings' => $ratings,
        ]);
    }
    public function mix(Request $request){
        $perPage = $request->query('per_page', 6);
        $defaultLocale = app()->getLocale();
        $products = DB::table('products')->where('lang', $defaultLocale)->orderBy('id', 'desc')->paginate($perPage);
        $tur = TourPackage::join('tour_fotos', 'tour_fotos.code', 'tour_packages.code')
                    ->where('tour_packages.lang', $defaultLocale)
                    ->select('tour_packages.*', 'tour_fotos.foto')
                    ->get();
        $activities = DB::table('activities')->where('lang', $defaultLocale)
                    ->join('activity_fotos', 'activity_fotos.code', 'activities.code')
                    ->select('activities.*', 'activity_fotos.foto')
                    ->get();
        // $products = DB::table('products')->orderBy('id', 'desc')->get();
        return view('pages.mix',['tour' => $tur, 'activities' => $activities, 'product' => $products] );
    }

    public function hotel(){
        $defaultLocale = app()->getLocale();
        $hotel = Booking::join('room_fotos', 'room_fotos.code', 'bookings.code')
                        ->where('bookings.lang', $defaultLocale)
                        ->select('bookings.*', 'room_fotos.foto')
                        ->get();
        $date = Carbon::now()->format('Y-m-d');
        $rate = DB::table('rates')->where('tgl', $date)->get();
        $fasilitas = Facility::all();
        return view('pages.hotel',['hotels' => $hotel, 'rate' => $rate, 'fasilitas' => $fasilitas] );
    }

    

    public function home(){

        $defaultLocale = app()->getLocale();
        $date = Carbon::now()->format('Y-m-d');
        $fasilitas = Facility::all();
        $transport = Transport::where('transports.lang', $defaultLocale)->get();
        $detinasi = Destination::where('destinations.lang', $defaultLocale)
                        ->join('destination_fotos', 'destination_fotos.code', 'destinations.code')
                        ->select('destinations.*', 'destination_fotos.foto')
                        ->take(3)
                        ->get();
        $tur = TourPackage::join('tour_fotos', 'tour_fotos.code', 'tour_packages.code')
                        ->where('tour_packages.lang', $defaultLocale)
                        ->select('tour_packages.*', 'tour_fotos.foto')
                        ->take(3)
                        ->get();
        $paket = Package::where('lang', $defaultLocale)->take(3)->get();
        $artikel = Artikel::where('lang', $defaultLocale)->get();
        $galeri = Gallery::where('lang', $defaultLocale)->take(3)->get();
        $product = DB::table('products')->where('lang', $defaultLocale)->orderBy('id', 'desc')->take(4)->get();
        $activities = DB::table('activities')->where('lang', $defaultLocale)->get();
        $ratings = Review::ratingSummaries($product->pluck('code')->merge($tur->pluck('code')));
        // var_dump($kamar[0]->foto);

        return view('pages.home',[
            'transport' => $transport,
            'destination' => $detinasi,
            'tour' => $tur,
            'paket' => $paket,
            'artikel' => $artikel,
            'galeri'  => $galeri,
            'fasilitas' => $fasilitas,
            'product' => $product,
            'activities' => $activities,
            'ratings' => $ratings,
            ] );
    }

    public function getLoc(Request $request){
        $ip = $request->ip(); // Dynamic IP address */
        // $ip = '162.159.24.227'; /* Static IP address */
        $currentUserInfo = Location::get($ip);
        // return view('pages.service', compact('currentUserInfo'));
        return $currentUserInfo;
    }

    public function destination()
    {
        $defaultLocale = app()->getLocale();
        $destination = DB::table('destinations')->where('lang', $defaultLocale)
                        ->join('destination_fotos', 'destination_fotos.code', 'destinations.code')
                        ->select('destinations.*', 'destination_fotos.foto')
                        ->get();
        return view('pages.destinations', compact('destination'));
    }

    public function destinationDetail($slug){
        $destinationDetail = DB::table('destinations')->where('destinations.slug', $slug)
                        ->where('destinations.lang', app()->getLocale())
                        ->join('destination_fotos', 'destination_fotos.code', 'destinations.code')
                        ->select('destinations.*', 'destination_fotos.foto')
                        ->get();
        abort_if($destinationDetail->isEmpty(), 404);
                        // dd($destinationDetail);
        return view('pages.destination-detail', ['destinationDetail' => $destinationDetail]);
    }

    public function activity()
    {
        $defaultLocale = app()->getLocale();
        $activity = DB::table('activities')->where('lang', $defaultLocale)
                    ->join('activity_fotos', 'activity_fotos.code', 'activities.code')
                    ->select('activities.*', 'activity_fotos.foto')
                    ->get();
        return view('pages.activity', compact('activity'));
    }

    public function activityDetail($slug){
        $activityDetail = DB::table('activities')->where('slug', $slug)
                    ->where('activities.lang', app()->getLocale())
                    ->join('activity_fotos', 'activity_fotos.code', 'activities.code')
                    ->select('activities.*', 'activity_fotos.foto')
                    ->get();
        abort_if($activityDetail->isEmpty(), 404);
        $product = DB::table('products')->get();
        $country = DB::table('countries')->get();
        return view('pages.activity-detail', ['activityDetail' => $activityDetail, 'products' => $product, 'country' => $country,]);
    }

    public function tourDetail(Request $request, $slug){

        $tur = DB::table('tour_packages')->where('tour_packages.slug', $slug)
                        ->when($request->routeIs('tour_packages.detail'), fn ($query) => $query->where('tour_packages.lang', app()->getLocale()))
                        ->join('tour_fotos', 'tour_fotos.code', 'tour_packages.code')
                        ->select('tour_packages.*', 'tour_fotos.foto')
                        ->get();
        abort_if($tur->isEmpty(), 404);
        $tour = $tur->first();
        $defaultLocale = $tour->lang;
        app()->setLocale($defaultLocale);
        $reviewReservation = null;
        if ($request->routeIs('tour_reviews.form')) {
            $reviewReservation = $this->reviewReservation($request->query(), $tour->code);
        }
        $destinasi = DB::table('destinations')->join('destination_fotos', 'destination_fotos.code', 'destinations.code')
                        ->where('lang', $defaultLocale)
                        ->select('destinations.*', 'destination_fotos.foto')
                        ->get();
        $activity = DB::table('activities')->where('lang', $defaultLocale)
                        ->join('activity_fotos', 'activity_fotos.code', 'activities.code')
                        ->select('activities.*', 'activity_fotos.foto')
                        ->get();
                        // dd($tur);
        $reviews = Review::where('product_code', $tour->code)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $response = response()->view('pages.tour-detail', [
            'tourDetail' => $tur,
            'destinasi' => $destinasi,
            'activities' => $activity,
            'reviews' => $reviews,
            'reviewReservation' => $reviewReservation,
            'ratings' => Review::ratingSummaries([$tour->code]),
        ]);
        if ($reviewReservation) {
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        return $response;
    }

    private function reviewReservation(array $input, $serviceCode)
    {
        $data = Validator::make($input, [
            'email' => ['required', 'string', 'email', 'max:255'],
            'booking' => ['required', 'string', 'max:255'],
        ])->validate();

        $reservation = DB::table('reservations')
            ->where('no_reservasi', $data['booking'])
            ->where('guest_email', $data['email'])
            ->where('code_service', $serviceCode)
            ->first();

        abort_unless($reservation, 403, 'The review link is not valid for this booking.');

        return $reservation;
    }

    public function tourReviewStore(Request $request, $slug)
    {
        $tour = DB::table('tour_packages')->where('slug', $slug)->first();
        abort_unless($tour, 404);
        return $this->storeVerifiedReview($request, $tour->code, 'tour_packages.detail', $slug);
    }

    public function productReviewStore(Request $request, $slug)
    {
        $product = DB::table('products')->where('slug', $slug)->first();
        abort_unless($product, 404);
        return $this->storeVerifiedReview($request, $product->code, 'mix_packages.detail', $slug);
    }

    private function storeVerifiedReview(Request $request, $serviceCode, $detailRoute, $slug)
    {
        $reservation = $this->reviewReservation($request->query(), $serviceCode);
        $validator = Validator::make($request->only('rating', 'comment'), [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            return redirect()->to($request->fullUrl() . '#tour-reviews')
                ->withErrors($validator)
                ->withInput($request->only('rating', 'comment'));
        }
        $data = $validator->validated();

        Review::create([
            'booking_id' => $reservation->no_reservasi,
            'product_code' => $serviceCode,
            'guest_name' => $reservation->guest_name,
            'guest_email' => $reservation->guest_email,
            'comments' => $data['comment'],
            'star_rating' => $data['rating'],
            'status' => 'active',
        ]);

        return redirect()->route($detailRoute, $slug)
            ->with('tour_review_success', 'Thank you for your review.');
    }

    public function tourReviewLinkForm(Request $request, $slug)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $tour = DB::table('tour_packages')->where('slug', $slug)->first();
        abort_unless($tour, 404);
        return response()->view('admin.pages.tour-review-link', ['tour' => $tour])
            ->header('Cache-Control', 'private, no-store');
    }

    public function tourReviewLinkCreate(Request $request, $slug)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $tour = DB::table('tour_packages')->where('slug', $slug)->first();
        abort_unless($tour, 404);
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'booking' => ['required', 'string', 'max:255'],
        ]);
        $reservation = DB::table('reservations')
            ->where('no_reservasi', $data['booking'])
            ->where('guest_email', $data['email'])
            ->where('code_service', $tour->code)
            ->exists();
        if (!$reservation) {
            throw ValidationException::withMessages([
                'booking' => 'No booking matches this email, booking number and tour.',
            ]);
        }

        $link = URL::temporarySignedRoute('tour_reviews.form', now()->addDays(30), [
            'slug' => $slug,
            'email' => $data['email'],
            'booking' => $data['booking'],
        ]);

        return response()->view('admin.pages.tour-review-link', ['tour' => $tour, 'reviewLink' => $link])
            ->header('Cache-Control', 'private, no-store');
    }

    public function mixDetail(Request $request, $slug){

        $defaultLocale = app()->getLocale();
        $tur = DB::table('tour_packages')->where('tour_packages.slug', $slug)
                        ->join('tour_fotos', 'tour_fotos.code', 'tour_packages.code')
                        ->select('tour_packages.*', 'tour_fotos.foto')
                        ->get();
        $destinasi = DB::table('destinations')->join('destination_fotos', 'destination_fotos.code', 'destinations.code')
                        ->where('lang', $defaultLocale)
                        ->select('destinations.*', 'destination_fotos.foto')
                        ->get();
        $activity = DB::table('activities')->where('lang', $defaultLocale)
                        ->join('activity_fotos', 'activity_fotos.code', 'activities.code')
                        ->select('activities.*', 'activity_fotos.foto')
                        ->get();
                        // dd($tur);
        $product = DB::table('products')->where('slug', $slug)
            ->when($request->routeIs('mix_packages.detail'), fn ($query) => $query->where('lang', app()->getLocale()))
            ->get();
        abort_if($product->isEmpty(), 404);
        $service = $product->first();
        app()->setLocale($service->lang ?: app()->getLocale());
        $reviewReservation = $request->routeIs('product_reviews.form')
            ? $this->reviewReservation($request->query(), $service->code)
            : null;
        $reviews = Review::where('product_code', $service->code)
            ->where('status', 'active')->orderByDesc('created_at')->limit(5)->get();
        $response = response()->view('pages.mix-detail',[
            'tourDetail' => $tur,
            'destinasi' => $destinasi,
            'activities' => $activity,
            'mixDetail' => $product,
            'itineraryItems' => ProductItinerary::resolve($service->itinerary),
            'reviews' => $reviews,
            'reviewReservation' => $reviewReservation,
            'ratings' => Review::ratingSummaries([$service->code]),
            ] );
        if ($reviewReservation) {
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        return $response;
    }

    public function hotelDetail($slug){

        $defaultLocale = app()->getLocale();
        $code = Booking::where('slug', $slug)->first();
        $hotel = Booking::where('bookings.code', $code->code)->where('bookings.lang', $defaultLocale)
                    ->join('room_fotos', 'room_fotos.code', 'bookings.code')
                    ->select('bookings.*', 'room_fotos.foto')
                    ->get();
        $review = DB::table('review_ratings')->where('product_code', $code->code)->get(); // Review::where('product_code', $code->code)->get();
        $country = DB::table('countries')->get();
        $fasilitas = Facility::all();
        $destinasi = DB::table('destinations')->where('lang', $defaultLocale)
                    ->join('destination_fotos', 'destination_fotos.code', 'destinations.code')
                    ->select('destinations.*', 'destination_fotos.foto')
                    ->inRandomOrder()->limit(6)->get();
        $activities = DB::table('activities')->where('lang', $defaultLocale)
                    ->join('activity_fotos', 'activity_fotos.code', 'activities.code')
                    ->select('activities.*', 'activity_fotos.foto')
                    ->inRandomOrder()->limit(6)->get();
        return view('pages.hotel-detail',[
            'hotelDetail' => $hotel,
            'country' => $country,
            'fasilitas' => $fasilitas,
            'destinasi' => $destinasi,
            'activities' => $activities,
            'review' => $review
            ] );
    }

    public function paketDetail($slug){


    }

    public function galeri(){
        $defaultLocale = app()->getLocale();
        $hotel = Booking::where('bookings.lang', $defaultLocale)->get();
        $galeri = Gallery::where('lang', $defaultLocale)->get();

        return view('pages.galeri',[
            'hotelDetail' => $hotel,
            'galeri' => $galeri
            ] );

    }
    public function transfer(){
        $defaultLocale = app()->getLocale();
        // $hotel = Booking::where('bookings.lang', $defaultLocale)->get();

        return view('pages.transfer',[
            // 'hotelDetail' => $hotel,
            ] );

    }

    public function event(){
        $defaultLocale = app()->getLocale();
        // $code = Gallery::where('slug', $slug)->first();
        $artikel = Artikel::where('lang', $defaultLocale)->get();

        return view('pages.event',[
            'artikel' => $artikel
            ] );
        
    }

    public function exchange($mata){

        $response = Http::get('https://v6.exchangerate-api.com/v6/bcb99ccd6a1020a3868d3632/latest/USD');
        $posts = $response->json();
        Session::put('currency_rate', $posts['conversion_rates'][$mata]);
        // return redirect()->back();
    }

    public function rate(Request $request){
        // $defaultLocale = app()->getLocale();
        // $code = Gallery::where('slug', $slug)->first();
        $cekin = $request->start;
        $cekout = $request->end ;
        $dateRange = CarbonPeriod::create($cekin, $cekout);

        $rate = Rate::where('tgl', $request->date)->where('kode_kamar', $request->code)->get();

        // $users = DB::select("SELECT *
        // FROM room_nomors
        // WHERE room_no NOT IN
        //     (SELECT no_room
        //      FROM reservation_room_detail WHERE status !='cekout' AND tgl BETWEEN '$request->start' AND '$request->end')
        // AND unit_code='$request->code';");
        $stok = $rate['0']->stok;

        $users = DB::table("room_nomors")->select('*')
        ->whereNOTIn('room_no',function($query) use ($request){
            $query->select('no_room')
                    ->from('reservation_room_detail')
                    ->where('tgl', $request->date)
                    // ->where('status', '!=', 'cekin')
                    ->where('status', '!=', 'cekout');
                    
        })
        ->where('unit_code', $request->code)
        ->get();
        $no_kamar = $users['0']->room_no ;

        $kamar = count($users);
        if($stok == $kamar){
            // echo "stok : ".$stok ;
        }else{
            // echo "kamar : ".$kamar ;
        }


        return response()->json([$rate, $users]);
        
    }

    // public function cekAllotment(Request $request){
    //     // $defaultLocale = app()->getLocale();
    //     // $code = Gallery::where('slug', $slug)->first();
    //     $stok = Rate::where('tgl', $request->date)->where('kode_kamar', $request->code)->get();

    //     // return view('pages.event',[
    //     //     'artikel' => $rate
    //     //     ] );
    //     return response()->json($stok);
        
    // }

    public function bookDetail($id){

        $detail = DB::table('reservations')->where('id', $id)->get();
        return view('pages.det_res',[
            'detail' => $detail
            ] );
    }

    public function reviewstore(Request $request){

        abort_if(DB::table('tour_packages')->where('code', $request->product_code)->exists()
            || DB::table('products')->where('code', $request->product_code)->exists(), 403);
        $post = Review::create([
            'booking_id' => $request->booking_id,
            'product_code' => $request->product_code,
            'comments' => $request->comment,
            'user_rating' => $request->rating,
            'guest_name' => $request->name,
            'guest_email' => $request->email,
            'star_rating' => $request->rating,
            'status' => 'active',
        ]);

        return redirect()->back()->with('flash_msg_success','Thank You, Your review has been submitted Successfully,');
    }

    public function getProduct(Request $request){
        $code = $request->code;
        $data = DB::table('products')->where('code', $code)->first();
        
        return response()->json(['data' => $data ]);
    }

}
