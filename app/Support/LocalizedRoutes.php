<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocalizedRoutes
{
    public const LOCALES = ['en', 'id'];

    public const DETAILS = [
        'tour_packages.detail' => 'tour_packages',
        'mix_packages.detail' => 'products',
        'destinations.detail' => 'destinations',
        'activities.detail' => 'activities',
        'travel_guides.detail' => 'travel_guides',
    ];

    public const PUBLIC_ROUTES = [
        'home', 'about-us', 'transfer', 'events', 'transport', 'tour_packages',
        'service', 'mix_packages', 'destinations', 'activities', 'travel_guides',
        'tour_packages.detail', 'mix_packages.detail', 'destinations.detail', 'activities.detail', 'travel_guides.detail',
    ];

    public static function languageUrl(Request $request, string $locale): string
    {
        $name = $request->route()?->getName();
        if (!in_array($name, self::PUBLIC_ROUTES, true)) {
            return route('lang', ['lang' => $locale]);
        }

        $parameters = ['locale' => $locale];
        if (isset(self::DETAILS[$name])) {
            $parameters['slug'] = $request->route('slug');
        }
        $url = route($name, $parameters);
        $query = $request->getQueryString();

        return $query ? $url.'?'.$query : $url;
    }

    public static function resolveDetail(string $name, string $slug, string $locale): ?object
    {
        $table = self::DETAILS[$name];
        $matching = DB::table($table)->where('slug', $slug)->where('lang', $locale)->first(['slug', 'lang', 'code']);
        if ($matching) {
            return $matching;
        }

        $original = DB::table($table)->where('slug', $slug)->whereIn('lang', self::LOCALES)
            ->orderBy('id')->first(['slug', 'lang', 'code']);
        if (!$original) {
            return null;
        }

        return DB::table($table)->where('code', $original->code)->where('lang', $locale)
            ->whereNotNull('slug')->where('slug', '!=', '')->orderBy('id')->first(['slug', 'lang', 'code'])
            ?? $original;
    }
}
