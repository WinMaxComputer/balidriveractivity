<?php

namespace App\Http\Controllers;

use App\Support\LocalizedRoutes;
use Illuminate\Http\Request;

class LegacyPublicRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $name = substr($request->route()->getName(), strlen('legacy.'));
        abort_unless(in_array($name, LocalizedRoutes::PUBLIC_ROUTES, true), 404);
        $parameters = ['locale' => app()->getLocale()];
        if (isset(LocalizedRoutes::DETAILS[$name])) {
            $parameters['slug'] = $request->route('slug');
        }
        $url = route($name, $parameters);
        if ($request->getQueryString()) {
            $url .= '?'.$request->getQueryString();
        }

        return redirect()->to($url);
    }
}
