<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sitemap:generate', function () {
    \App\Support\PublicSitemap::generate()->writeToFile(public_path('sitemap.xml'));
    $this->info('Sitemap written to public/sitemap.xml.');
})->purpose('Generate the localized public sitemap');
