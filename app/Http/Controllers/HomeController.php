<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $bookings = DB::table('reservations')->orderByDesc('id')->limit(5)
            ->get(['id', 'no_reservasi', 'tgl_reservasi', 'guest_name', 'code_service', 'status', 'book_status']);
        $transfers = DB::table('transfer_orders')->orderByDesc('id')->limit(5)
            ->get(['id', 'name', 'booking_type', 'destination', 'pickup_date', 'created_at']);
        $reviews = DB::table('review_ratings')->orderByDesc('id')->limit(5)
            ->get(['id', 'guest_name', 'product_code', 'comments', 'star_rating', 'status', 'created_at']);

        return response()->view('admin.home', [
            'bookings' => $bookings,
            'transfers' => $transfers,
            'reviews' => $reviews,
            'topProducts' => $this->topServices('products', 'product_name', 'tour_packages'),
            'topTours' => $this->topServices('tour_packages', 'tour_name', 'products'),
        ])->header('Cache-Control', 'private, no-store');
    }

    private function topServices(string $table, string $nameColumn, string $otherTable): Collection
    {
        $ranking = DB::table('reservations')
            ->whereNotNull('code_service')
            ->where('code_service', '!=', '')
            ->where(function ($query) {
                $query->whereNull('book_status')
                    ->orWhereRaw("LOWER(TRIM(book_status)) NOT IN ('cancelled', 'canceled')");
            })
            ->whereExists(function ($query) use ($table) {
                $query->selectRaw('1')->from($table)->whereColumn($table.'.code', 'reservations.code_service');
            })
            ->whereNotExists(function ($query) use ($otherTable) {
                $query->selectRaw('1')->from($otherTable)->whereColumn($otherTable.'.code', 'reservations.code_service');
            })
            ->selectRaw('code_service, COUNT(*) as booking_count')
            ->groupBy('code_service')
            ->orderByDesc('booking_count')->orderBy('code_service')->limit(5)->get();

        $names = DB::table($table)->whereIn('code', $ranking->pluck('code_service'))
            ->orderByRaw('CASE WHEN lang = ? THEN 0 ELSE 1 END', [config('app.locale')])
            ->orderBy('id')->get(['code', $nameColumn])->groupBy('code');

        return $ranking->map(function ($service) use ($names, $nameColumn) {
            $service->name = $names->get($service->code_service)?->first()?->{$nameColumn} ?: 'Name unavailable';
            return $service;
        });
    }
}
