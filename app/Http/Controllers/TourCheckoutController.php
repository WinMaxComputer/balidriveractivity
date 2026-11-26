<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TourCheckoutController extends Controller
{
    private function service(string $type, int $id): object
    {
        abort_unless(in_array($type, ['product', 'tour'], true), 404);
        $service = $type === 'tour'
            ? DB::table('tour_packages')->where('id', $id)->first()
            : DB::table('products')->where('id', $id)->first();
        abort_unless($service, 404);

        $price = $type === 'tour' ? $service->price_usd : $service->price;
        if ($price === null || !is_numeric($price) || $price <= 0 || strlen($service->code) > 50 || trim($service->code) === '') {
            throw ValidationException::withMessages([
                'service' => 'This service is not currently available for online booking. Please contact us.',
            ]);
        }

        return (object) [
            'name' => $type === 'tour' ? $service->tour_name : $service->product_name,
            'code' => $service->code,
            'price' => number_format((float) $price, 2, '.', ''),
        ];
    }

    public function show(Request $request, string $type, int $id)
    {
        try {
            $service = $this->service($type, $id);
        } catch (ValidationException $exception) {
            return response()->view('pages.tour-checkout-unavailable', [
                'message' => $exception->errors()['service'][0],
            ], 422)->header('Cache-Control', 'private, no-store');
        }
        $initial = $request->validate([
            'travel_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'travelers' => ['nullable', 'integer', 'min:1', 'max:99999'],
        ]);
        $currency = $request->session()->get('currency', 'USD');
        $rate = $request->session()->get('currency_rate');
        $currencyUnavailable = $currency !== 'USD'
            && ($currency !== 'IDR' || !is_numeric($rate) || !is_finite((float) $rate) || (float) $rate <= 0);
        if ($currencyUnavailable) {
            $currency = 'USD';
        }
        $displayPrice = convertPrice((float) $service->price, $currency);
        $token = Str::random(64);
        $request->session()->put('checkout_tokens.'.$token, true);

        return response()->view('pages.tour-checkout', compact('service', 'type', 'id', 'initial', 'token', 'currency', 'currencyUnavailable', 'displayPrice'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, string $type, int $id)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64', 'alpha_num'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:100'],
            'travel_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'travelers' => ['required', 'integer', 'min:1', 'max:99999'],
            'unit_price' => ['required', 'numeric', 'gt:0'],
        ]);
        abort_unless($request->session()->has('checkout_tokens.'.$data['token']), 403);
        $token = hash('sha256', $data['token']);
        $existing = DB::table('reservations')->where('checkout_token', $token)->first();
        if ($existing) {
            return redirect()->route('checkout.confirmation')->with('checkout_number', $existing->no_reservasi);
        }

        $service = $this->service($type, $id);
        if ((float) $data['unit_price'] !== (float) $service->price) {
            throw ValidationException::withMessages([
                'unit_price' => 'The price has changed. Please reload the checkout page before booking.',
            ]);
        }
        $cents = (int) round((float) $service->price * 100) * (int) $data['travelers'];
        if ($cents > 9999999999999) {
            throw ValidationException::withMessages(['travelers' => 'The total exceeds the allowed amount.']);
        }
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $number = 'BOOK-'.Str::upper(Str::random(8));

            try {
                DB::table('reservations')->insert([
                    'no_reservasi' => $number,
                    'checkout_token' => $token,
                    'code_service' => $service->code,
                    'tgl_reservasi' => now(),
                    'guest_email' => $data['email'],
                    'guest_name' => $data['name'],
                    'payment_type' => 'Unpaid',
                    'adult' => $data['travelers'],
                    'total' => sprintf('%d.%02d', intdiv($cents, 100), $cents % 100),
                    'guest_paid' => 0,
                    'cek_in' => $data['travel_date'].' 00:00:00',
                    'cek_out' => $data['travel_date'].' 00:00:00',
                    'book_status' => 'New',
                    'status' => 'PENDING',
                    'room_no' => '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                break;
            } catch (UniqueConstraintViolationException $exception) {
                $existing = DB::table('reservations')->where('checkout_token', $token)->first();
                if ($existing) {
                    $number = $existing->no_reservasi;
                    break;
                }
                if ($attempt === 4 || !DB::table('reservations')->where('no_reservasi', $number)->exists()) {
                    throw $exception;
                }
            }
        }

        return redirect()->route('checkout.confirmation')->with('checkout_number', $number);
    }

    public function confirmation(Request $request)
    {
        abort_unless($request->session()->has('checkout_number'), 404);

        return response()->view('pages.tour-checkout-confirmation')
            ->header('Cache-Control', 'private, no-store');
    }
}
