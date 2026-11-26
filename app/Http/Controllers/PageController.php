<?php

namespace App\Http\Controllers;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageController extends Controller
{
    /**
     * Display icons page
     *
     * @return \Illuminate\View\View
     */
    public function icons()
    {
        return view('admin.pages.icons');
    }

    public function properti()
    {
        return view('admin.pages.properti');
    }

    /**
     * Display maps page
     *
     * @return \Illuminate\View\View
     */
    public function maps()
    {
        return view('admin.pages.maps');
    }

    /**
     * Display tables page
     *
     * @return \Illuminate\View\View
     */
    public function tables()
    {
        return view('admin.pages.tables');
    }

    /**
     * Display notifications page
     *
     * @return \Illuminate\View\View
     */
    public function notifications()
    {
        return view('admin.pages.notifications');
    }

    /**
     * Display rtl page
     *
     * @return \Illuminate\View\View
     */
    public function rtl()
    {
        return view('admin.pages.rtl');
    }

    /**
     * Display typography page
     *
     * @return \Illuminate\View\View
     */
    public function typography()
    {
        return view('admin.pages.typography');
    }

    public function rooms()
    {
        $defaultLocale = config('app.locale');
        $room = DB::table('bookings')->get();
        return view('admin.pages.rooms', ['room' => $room]);
    }
    public function roomAdd()
    {
        // $defaultLocale = config('app.locale');
        $fasilitas = DB::table('facilities')->get();
        return view('admin.pages.room_add', compact('fasilitas'));
    }

    public function rates()
    {
        return view('admin.pages.rates');
    }

    public function orders(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $orders = DB::table('reservations')->orderByDesc('id')->paginate(20);

        return response()->view('admin.pages.orders', ['orders' => $orders])
            ->header('Cache-Control', 'private, no-store');
    }

    public function ordersData(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'min:1', 'max:100'],
            'search.value' => ['nullable', 'string', 'max:200'],
            'order.0.column' => ['sometimes', 'integer', 'between:0,10'],
            'order.0.dir' => ['sometimes', 'in:asc,desc'],
        ]);

        $columns = [
            'no_reservasi', 'tgl_reservasi', 'guest_name', 'guest_email', 'cek_in',
            'adult', 'total', 'guest_paid', 'payment_type', 'status', 'book_status',
        ];
        $query = DB::table('reservations');
        $total = $query->count();
        $search = trim($data['search']['value'] ?? '');

        if ($search !== '') {
            $query->where(function ($query) use ($search, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        $filtered = $query->count();
        if (isset($data['order'][0]['column'], $data['order'][0]['dir'])) {
            $query->orderBy($columns[(int) $data['order'][0]['column']], $data['order'][0]['dir']);
        }
        $orders = $query->orderByDesc('id')
            ->offset((int) $data['start'])
            ->limit((int) $data['length'])
            ->get(array_merge(['id'], $columns));

        return response()->json([
            'draw' => (int) $data['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $orders,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function orderDetail(Request $request, $order)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $reservation = DB::table('reservations')->where('id', $order)->first();
        abort_unless($reservation, 404);
        $reviewService = $this->orderReviewService($reservation->code_service);

        return $this->orderDetailResponse($reservation, $reviewService);
    }

    public function orderUpdate(Request $request, $order)
    {
        abort_unless($request->user()->role === 'admin', 403);

        DB::transaction(function () use ($request, $order) {
            $reservation = DB::table('reservations')->where('id', $order)->lockForUpdate()->first();
            abort_unless($reservation, 404);
            $paymentStatuses = array_filter(['PENDING', 'PAID', 'Downpayment', $reservation->status]);
            $bookingStatuses = array_filter(['New', 'Confirmed', 'Completed', 'Cancelled', $reservation->book_status]);
            $data = $request->validate([
                'payment_type' => ['required', 'string', 'max:50'],
                'status' => ['required', 'string', Rule::in($paymentStatuses)],
                'book_status' => ['required', 'string', Rule::in($bookingStatuses)],
                'guest_paid' => ['exclude_if:status,PAID', 'exclude_if:status,PENDING', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            ]);

            if ($data['status'] === 'PAID') {
                if (!is_numeric($reservation->total) || $reservation->total < 0 || $reservation->total > 99999999.99) {
                    throw ValidationException::withMessages(['guest_paid' => 'The order total cannot be stored as the paid amount.']);
                }
                $paid = $reservation->total;
            } elseif ($data['status'] === 'PENDING') {
                $paid = 0;
            } else {
                if (!isset($data['guest_paid'])) {
                    throw ValidationException::withMessages(['guest_paid' => 'The paid amount is required.']);
                }
                $paid = $data['guest_paid'];
                if ($data['status'] === 'Downpayment'
                    && (!is_numeric($reservation->total) || $paid <= 0 || $paid >= $reservation->total)) {
                    throw ValidationException::withMessages(['guest_paid' => 'A downpayment must be greater than zero and less than the order total.']);
                }
            }

            DB::table('reservations')->where('id', $order)->update([
                'payment_type' => $data['payment_type'],
                'status' => $data['status'],
                'book_status' => $data['book_status'],
                'guest_paid' => $paid,
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('pages.orders.detail', $order)->with('order_message', 'Order payment and booking status updated.');
    }

    public function orderReviewLink(Request $request, $order)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $reservation = DB::table('reservations')->where('id', $order)->first();
        abort_unless($reservation, 404);
        $reviewService = $this->orderReviewService($reservation->code_service);
        abort_unless($reviewService, 404);
        abort_if($reviewService['ambiguous'], 409, 'The service code matches both a tour and a product.');

        $data = Validator::make([
            'email' => $reservation->guest_email,
            'booking' => $reservation->no_reservasi,
        ], [
            'email' => ['required', 'string', 'email', 'max:255'],
            'booking' => ['required', 'string', 'max:255'],
        ])->validate();

        $expiresAt = now()->addDays(30);
        $signedLink = URL::temporarySignedRoute($reviewService['route'], $expiresAt, [
            'slug' => $reviewService['slug'],
            'email' => $data['email'],
            'booking' => $data['booking'],
        ]);
        $token = Str::random(22);
        DB::table('short_review_links')->insert([
            'token_hash' => hash('sha256', $token),
            'target_url' => Crypt::encryptString($signedLink),
            'expires_at' => $expiresAt,
        ]);
        $link = route('review_links.open', $token);

        return $this->orderDetailResponse($reservation, $reviewService, $link);
    }

    public function orderReviewDelete(Request $request, $order, $review)
    {
        abort_unless($request->user()->role === 'admin', 403);

        DB::transaction(function () use ($order, $review) {
            $reservation = DB::table('reservations')->where('id', $order)->lockForUpdate()->first();
            abort_unless($reservation, 404);
            $query = $this->orderReviewsQuery($reservation);
            abort_unless($query, 409, 'Cannot identify reviews without a booking number and service code.');
            abort_if(DB::table('reservations')->where('no_reservasi', $reservation->no_reservasi)
                ->where('id', '!=', $reservation->id)->exists(), 409, 'Cannot delete a review for a shared booking number.');
            abort_unless($query->whereKey($review)->delete(), 404);
        });

        return redirect()->route('pages.orders.detail', $order)->with('order_message', 'Review deleted.');
    }

    private function orderDetailResponse($reservation, $reviewService, $reviewLink = null)
    {
        $query = $this->orderReviewsQuery($reservation);

        return response()->view('admin.pages.order-detail', [
            'order' => $reservation,
            'serviceName' => $this->orderServiceName($reservation->code_service),
            'reviewService' => $reviewService,
            'reviewLink' => $reviewLink,
            'orderReviews' => $query ? $query->orderByDesc('id')->get() : null,
        ])->header('Cache-Control', 'private, no-store');
    }

    private function orderServiceName($serviceCode): string
    {
        if (!is_string($serviceCode) || trim($serviceCode) === '') {
            return 'Name unavailable';
        }

        $tour = DB::table('tour_packages')->where('code', $serviceCode)
            ->orderByRaw('CASE WHEN lang = ? THEN 0 ELSE 1 END', [config('app.locale')])
            ->orderBy('id')->first(['tour_name']);
        $product = DB::table('products')->where('code', $serviceCode)
            ->orderByRaw('CASE WHEN lang = ? THEN 0 ELSE 1 END', [config('app.locale')])
            ->orderBy('id')->first(['product_name']);

        if ($tour && $product) {
            return 'Ambiguous code - Tour: '.($tour->tour_name ?: 'Name unavailable')
                .'; Product: '.($product->product_name ?: 'Name unavailable');
        }

        return ($tour->tour_name ?? null) ?: (($product->product_name ?? null) ?: 'Name unavailable');
    }

    private function orderReviewsQuery($reservation): ?Builder
    {
        if (!is_string($reservation->no_reservasi) || trim($reservation->no_reservasi) === ''
            || !is_string($reservation->code_service) || trim($reservation->code_service) === '') {
            return null;
        }

        return Review::query()->where('booking_id', $reservation->no_reservasi)
            ->where('product_code', $reservation->code_service);
    }

    public function openReviewLink(string $token)
    {
        $link = DB::table('short_review_links')
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();
        abort_unless($link, 404);

        return redirect()->away(Crypt::decryptString($link->target_url))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function orderDelete(Request $request, $order)
    {
        abort_unless($request->user()->role === 'admin', 403);

        DB::transaction(function () use ($order) {
            $reservation = DB::table('reservations')->where('id', $order)->lockForUpdate()->first();
            abort_unless($reservation, 404);
            abort_if(!is_string($reservation->no_reservasi) || trim($reservation->no_reservasi) === '', 409, 'Cannot delete an order without a booking number.');
            $duplicates = DB::table('reservations')
                ->where('no_reservasi', $reservation->no_reservasi)
                ->where('id', '!=', $reservation->id)
                ->exists();
            abort_if($duplicates, 409, 'Cannot delete an order with a shared booking number.');

            DB::table('reservation_room_detail')->where('no_reservasi', $reservation->no_reservasi)->delete();
            DB::table('reservations')->where('id', $reservation->id)->delete();
        });

        return redirect()->route('pages.orders')->with('order_message', 'Order deleted. Stock and payments were not changed.');
    }

    private function orderReviewService($serviceCode)
    {
        $tour = DB::table('tour_packages')
            ->where('code', $serviceCode)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderByRaw('CASE WHEN lang = ? THEN 0 ELSE 1 END', [config('app.locale')])
            ->orderBy('id')
            ->first();
        $product = DB::table('products')
            ->where('code', $serviceCode)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderByRaw('CASE WHEN lang = ? THEN 0 ELSE 1 END', [config('app.locale')])
            ->orderBy('id')
            ->first();
        if (($tour && DB::table('products')->where('code', $serviceCode)->exists())
            || ($product && DB::table('tour_packages')->where('code', $serviceCode)->exists())) {
            return ['ambiguous' => true];
        }
        if ($tour) {
            return ['ambiguous' => false, 'slug' => $tour->slug, 'route' => 'tour_reviews.form'];
        }
        if ($product) {
            return ['ambiguous' => false, 'slug' => $product->slug, 'route' => 'product_reviews.form'];
        }
        return null;
    }

    public function tour()
    {
        $defaultLocale = config('app.locale');
        $tur = DB::table('tour_packages')->get();
        return view('admin.pages.tour', ['tour' => $tur]);
    }
    public function tourAdd()
    {
        // $defaultLocale = config('app.locale');
        $destinasi = DB::table('destinations')->get();
        $travel_area = DB::table('travel_area')->get();
        $activities = DB::table('activities')->get();
        return view('admin.pages.tour_add', compact('destinasi', 'travel_area', 'activities'));
    }
    /**
     * Display upgrade page
     *
     * @return \Illuminate\View\View
     */
    public function destinasi()
    {
        $destinasi = DB::table('destinations')->paginate(10);
        return view('admin.pages.destinasi', ['destinasi' => $destinasi]);
    }
    public function destinasiAdd()
    {
        // $defaultLocale = config('app.locale');
        return view('admin.pages.destinasi_add');
    }
    public function activity(Request $request, $slug)
    {
        $search = $request->query('q');
        $area = $request->query('area');
        if($search == ''){
            $cr = 'all';
        }
        $perPage = $request->query('perPage', 12); // Default to 12 if not provided

        if ($search != null && $search != 'all') {
            $activities = DB::table('activities')->where('name', 'like', '%' . $search . '%')->orderBy('id', 'desc')->paginate($perPage);
        } elseif ($area != null) {
            // if area is numeric assume it's an ID, otherwise treat as slug
            if (is_numeric($area)) {
                $activities = DB::table('activities')->where('area_id', $area)->orderBy('id', 'desc')->paginate($perPage);
            } else {
                $cat = DB::table('travel_area')->where('code', $area)->first();
                if ($cat) {
                    $activities = DB::table('activities')->where('area_id', $cat->id)->orderBy('id', 'desc')->paginate($perPage);
                } else {
                    // fallback to all activities if area query is invalid
                    $activities = DB::table('activities')->orderBy('id', 'desc')->paginate($perPage);
                }
            }
        } elseif ($slug == 'all') {
            $activities = DB::table('activities')->orderBy('id', 'desc')->paginate($perPage);
        } elseif ($cr == 'all') {
            $activities = DB::table('activities')->orderBy('id', 'desc')->paginate($perPage);
        } else {
            $categories = DB::table('travel_area')->where('code', $category)->first();
            if($categories){
                $activities = DB::table('activities')->where('area_id', $categories->code_cat)->orderBy('id', 'desc')->paginate($perPage);
            } else {
                $activities = [];
            }
        }
        // $activities = DB::table('activities')->get();
        return view('admin.pages.activity', ['activity' => $activities]);
    }
    public function activityAdd()
    {
        // $defaultLocale = config('app.locale');
        return view('admin.pages.activity_add');
    }
    public function products(Request $request, $slug){
        $search = $request->query('q');
        if($search == ''){
            $cr = 'all';
        }
        $perPage = $request->query('perPage', 12); // Default to 12 if not provided

        if ($search != null && $search != 'all') {
            $products = DB::table('products')->where('product_name', 'like', '%' . $search . '%')->orderBy('id', 'desc')->paginate($perPage);
            
        } elseif ($slug == 'all') {
            $products = DB::table('products')->orderBy('id', 'desc')->paginate($perPage);
        } elseif ($cr == 'all') {
            $products = DB::table('products')->orderBy('id', 'desc')->paginate($perPage);
        } else {
            $products = [];
        }
        $activities = DB::table('activities')->get();
        $destinasi = DB::table('destinations')->get();
        return view('admin.pages.products', ['products' => $products, 'activities' => $activities, 'destinasi' => $destinasi]);
    }
    public function productsAdd(){

        $activities = DB::table('activities')->get();
        $destinasi = DB::table('destinations')->get();
        return view('admin.pages.products_add', ['activities' => $activities, 'destinasi' => $destinasi, 'itineraryItems' => []]);
    }
}
