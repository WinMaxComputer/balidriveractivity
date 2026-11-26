<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class PublicSitemap
{
    public static function generate(): Sitemap
    {
        $base = rtrim(config('app.url'), '/');
        if (!filter_var($base, FILTER_VALIDATE_URL) || !in_array(parse_url($base, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new \RuntimeException('APP_URL must be an absolute HTTP or HTTPS URL to generate the sitemap.');
        }

        $sitemap = Sitemap::create();
        $static = ['home', 'about-us', 'transfer', 'events', 'transport', 'tour_packages',
            'service', 'mix_packages', 'destinations', 'activities'];
        foreach (LocalizedRoutes::LOCALES as $locale) {
            foreach ($static as $name) {
                $sitemap->add(Url::create($base.route($name, ['locale' => $locale], false)));
            }
        }

        foreach (LocalizedRoutes::DETAILS as $name => $table) {
            $query = DB::table($table)->whereIn($table.'.lang', LocalizedRoutes::LOCALES)
                ->whereNotNull($table.'.slug')->where($table.'.slug', '!=', '');
            $photos = [
                'tour_packages' => 'tour_fotos',
                'activities' => 'activity_fotos',
                'destinations' => 'destination_fotos',
            ];
            if (isset($photos[$table])) {
                $query->whereExists(function ($query) use ($table, $photos) {
                    $query->selectRaw('1')->from($photos[$table])->whereColumn($photos[$table].'.code', $table.'.code');
                });
            }
            foreach ($query->get([$table.'.slug', $table.'.lang']) as $row) {
                $sitemap->add(Url::create($base.route($name, ['locale' => $row->lang, 'slug' => $row->slug], false)));
            }
        }

        return $sitemap;
    }
}
