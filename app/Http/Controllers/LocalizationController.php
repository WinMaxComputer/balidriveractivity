<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class LocalizationController extends Controller
{
    //
    public function lang(Request $request)
    {
        $locale = $request->validate(['lang' => ['required', 'in:en,id']])['lang'];
        App::setLocale($locale);
        Session::put('locale', $locale);
        $previous = $request->headers->get('referer');
        $base = $request->getSchemeAndHttpHost();
        if (is_string($previous) && str_starts_with($previous, $base.'/')) {
            return redirect()->to($previous);
        }

        return redirect()->route('home', ['locale' => $locale]);
    }
    public function currency(Request $request)
    {
        $currency = $request->validate(['currency' => ['required', 'in:USD,IDR']])['currency'];
        $rate = 1;
        if ($currency !== 'USD') {
            $response = Http::get('https://v6.exchangerate-api.com/v6/bcb99ccd6a1020a3868d3632/latest/USD');
            abort_unless($response->successful(), 503, 'Exchange rate is currently unavailable.');
            $rate = $response->json('conversion_rates.'.$currency);
            abort_unless(is_numeric($rate) && is_finite((float) $rate) && (float) $rate > 0, 503, 'Exchange rate is currently unavailable.');
        }
        Session::put('currency', $currency);
        Session::put('currency_rate', $rate);
        return redirect()->back();
    }
}
