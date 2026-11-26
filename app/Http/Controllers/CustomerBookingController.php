<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CustomerBookingController extends Controller
{
    public function track()
    {
        return $this->privateView('pages.track-booking');
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'booking' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ]);
        $booking = DB::table('reservations')->where('no_reservasi', $data['booking'])
            ->whereRaw('LOWER(guest_email) = ?', [mb_strtolower($data['email'])])->first();

        if (!$booking) {
            return back()->withErrors(['booking' => 'No booking matches that number and email.'])
                ->withInput($request->only('booking', 'email'));
        }

        return $this->privateView('pages.track-booking', [
            'bookings' => collect([$booking]),
            'serviceNames' => $this->serviceNames(collect([$booking])),
        ]);
    }

    public function dashboard(Request $request)
    {
        abort_unless($request->user()->email_verified_at, 403, 'Verify your email before viewing your bookings.');

        $bookings = DB::table('reservations')
            ->whereRaw('LOWER(guest_email) = ?', [mb_strtolower($request->user()->email)])
            ->orderByDesc('id')->paginate(10);

        return $this->privateView('pages.customer-dashboard', [
            'bookings' => $bookings,
            'serviceNames' => $this->serviceNames($bookings->getCollection()),
        ]);
    }

    private function serviceNames(Collection $bookings): array
    {
        $codes = $bookings->pluck('code_service')->filter()->unique()->values();
        $names = [];
        foreach (DB::table('tour_packages')->whereIn('code', $codes)->get(['code', 'tour_name']) as $tour) {
            $names[$tour->code] = $names[$tour->code] ?? $tour->tour_name;
        }
        $tourCodes = array_fill_keys(array_keys($names), true);
        foreach (DB::table('products')->whereIn('code', $codes)->get(['code', 'product_name']) as $product) {
            $names[$product->code] = isset($tourCodes[$product->code])
                ? $product->code : ($names[$product->code] ?? $product->product_name);
        }
        return $names;
    }

    private function privateView(string $view, array $data = [])
    {
        return response()->view($view, $data)
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
