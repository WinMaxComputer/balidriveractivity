<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TourListingTest extends TestCase
{
    public function test_tour_listing_links_to_pricing_for_text_based_tour_prices(): void
    {
        $tour = (object) [
            'tour_name' => 'Bali Tour',
            'code' => 'TOUR1',
            'slug' => 'bali-tour',
            'foto' => 'bali.webp',
            'price' => '<p>Contact us for pricing</p>',
            'activity' => '',
        ];

        $server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/tour_packages';

        try {
            $view = $this->view('pages.tour', [
                'product' => new LengthAwarePaginator([], 0, 100),
                'tour' => collect([$tour]),
                'activities' => collect(),
                'searchTerm' => '',
                'ratings' => collect(),
            ]);

            $view->assertSee('Bali Tour');
            $view->assertSee('View pricing');
            $view->assertSee(route('tour_packages.detail', 'bali-tour'));
        } finally {
            $_SERVER = $server;
        }
    }
}
