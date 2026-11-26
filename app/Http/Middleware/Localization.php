<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use App\Support\LocalizedRoutes;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale');
        $locale = in_array($locale, LocalizedRoutes::LOCALES, true)
            ? $locale : $request->session()->get('locale', config('app.locale'));
        if (!in_array($locale, LocalizedRoutes::LOCALES, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        $route = $request->route();
        if ($route && $route->hasParameter('locale')) {
            $route->forgetParameter('locale');
            $name = $route->getName();
            if (isset(LocalizedRoutes::DETAILS[$name])) {
                $slug = $route->parameter('slug');
                $detail = LocalizedRoutes::resolveDetail($name, $slug, $locale);
                abort_unless($detail, 404);
                if ($detail->slug !== $slug || $detail->lang !== $locale) {
                    $url = route($name, ['locale' => $detail->lang, 'slug' => $detail->slug]);
                    if ($request->getQueryString()) {
                        $url .= '?'.$request->getQueryString();
                    }
                    $redirect = redirect()->to($url);
                    if ($detail->lang !== $locale) {
                        $redirect->with('locale_message', 'This page is not available in the selected language.');
                    }
                    return $redirect;
                }
            }
            $request->session()->put('locale', $locale);
        }
        return $next($request);
    }
}
