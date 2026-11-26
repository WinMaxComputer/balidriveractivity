<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TourReviewTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('slug');
            $table->string('tour_name');
            $table->string('lang');
            $table->text('itinerary')->nullable();
            $table->text('price')->nullable();
            $table->text('pickup')->nullable();
            $table->text('payment')->nullable();
            $table->text('note')->nullable();
            $table->text('destination')->nullable();
            $table->text('activity')->nullable();
            $table->string('area_tour')->nullable();
        });
        Schema::create('tour_fotos', function (Blueprint $table) {
            $table->string('code');
            $table->text('foto');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('slug');
            $table->string('product_name');
            $table->string('lang');
            $table->text('product_foto')->nullable();
            $table->text('product_des')->nullable();
            $table->text('parent_type')->nullable();
            $table->text('dst_type')->nullable();
            $table->text('meta_keyword')->nullable();
            $table->text('meta_description')->nullable();
            $table->decimal('price', 10, 2);
            $table->text('itinerary')->nullable();
            $table->text('product_included')->nullable();
            $table->text('product_excluded')->nullable();
            $table->timestamps();
        });
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('lang');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
        });
        Schema::create('destination_fotos', function (Blueprint $table) {
            $table->string('code');
            $table->text('foto');
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('lang');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
        });
        Schema::create('activity_fotos', function (Blueprint $table) {
            $table->string('code');
            $table->text('foto');
        });
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('no_reservasi');
            $table->string('guest_email');
            $table->string('guest_name');
            $table->string('code_service');
            $table->string('tgl_reservasi')->nullable();
            $table->string('cek_in')->nullable();
            $table->string('cek_out')->nullable();
            $table->string('adult')->nullable();
            $table->decimal('total', 13, 2)->nullable();
            $table->decimal('guest_paid', 10, 2)->nullable();
            $table->string('payment_type')->nullable();
            $table->string('status')->nullable();
            $table->string('book_status')->nullable();
        });
        Schema::create('review_ratings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_id');
            $table->string('product_code');
            $table->string('guest_name');
            $table->string('guest_email');
            $table->text('comments');
            $table->integer('star_rating');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('short_review_links', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->text('target_url');
            $table->dateTime('expires_at');
        });
        foreach (['facilities', 'transports', 'packages', 'artikels', 'galleries'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('lang')->nullable();
            });
        }

        DB::table('tour_packages')->insert([
            'code' => 'TOUR1', 'slug' => 'bali-tour', 'tour_name' => 'Bali Tour',
            'lang' => 'en', 'destination' => '', 'activity' => '', 'area_tour' => '1',
        ]);
        DB::table('tour_fotos')->insert(['code' => 'TOUR1', 'foto' => 'tour.webp;']);
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK1', 'guest_email' => 'guest@example.test',
            'guest_name' => 'Guest', 'code_service' => 'TOUR1',
        ]);

        $this->server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/tour_packages/bali-tour';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    private function reviewLink(array $overrides = []): string
    {
        return URL::temporarySignedRoute('tour_reviews.form', now()->addDay(), array_merge([
            'slug' => 'bali-tour', 'email' => 'guest@example.test', 'booking' => 'BOOK1',
        ], $overrides));
    }

    public function test_localized_urls_redirect_legacy_paths_and_keep_the_selected_language(): void
    {
        $this->get('/?destination=Ubud')->assertRedirect(route('home', ['locale' => 'en']).'?destination=Ubud');
        $this->get('/id/tour_packages?destination=Ubud')->assertOk()
            ->assertSee(route('tour_packages', ['locale' => 'en']).'?destination=Ubud', false)
            ->assertSee(route('tour_packages', ['locale' => 'id']).'?destination=Ubud', false);
        $this->get('/tour_packages?destination=Ubud')
            ->assertRedirect(route('tour_packages', ['locale' => 'id']).'?destination=Ubud');
        $this->get('/fr/tour_packages')->assertNotFound();
        $this->get('/customer/bookings')->assertRedirect(route('customer.login'));
    }

    public function test_detail_language_switch_uses_translated_slug_or_returns_to_original(): void
    {
        DB::table('tour_packages')->insert([
            'code' => 'TOUR1', 'slug' => 'wisata-bali', 'tour_name' => 'Wisata Bali',
            'lang' => 'id', 'destination' => '', 'activity' => '', 'area_tour' => '1',
        ]);
        $this->get('/en/tour_packages/bali-tour?source=search')->assertOk()
            ->assertSee(route('tour_packages.detail', ['locale' => 'id', 'slug' => 'bali-tour']).'?source=search', false);
        $this->get('/id/tour_packages/bali-tour?source=search')
            ->assertRedirect(route('tour_packages.detail', ['locale' => 'id', 'slug' => 'wisata-bali']).'?source=search');
        $this->get('/id/tour_packages/wisata-bali')->assertOk()->assertSee('Wisata Bali');
        $this->get('/en/tour_packages/wisata-bali')
            ->assertRedirect(route('tour_packages.detail', ['locale' => 'en', 'slug' => 'bali-tour']));
        DB::table('tour_packages')->where('lang', 'id')->delete();
        $this->get('/id/tour_packages/bali-tour')
            ->assertRedirect(route('tour_packages.detail', ['locale' => 'en', 'slug' => 'bali-tour']))
            ->assertSessionHas('locale_message');
        $this->get('/en/tour_packages/bali-tour')->assertOk()
            ->assertSee('This page is not available in the selected language.');
        $this->get('/en/tour_packages/missing')->assertNotFound();
    }

    public function test_listing_uses_url_language_and_matching_slugs_can_exist_in_both_languages(): void
    {
        DB::table('tour_packages')->insert([
            'code' => 'TOUR1', 'slug' => 'bali-tour', 'tour_name' => 'Tur Bali Indonesia',
            'lang' => 'id', 'destination' => '', 'activity' => '', 'area_tour' => '1',
        ]);
        $this->get('/en/tour_packages')->assertOk()->assertSee('Bali Tour')->assertDontSee('Tur Bali Indonesia');
        $this->get('/id/tour_packages')->assertOk()->assertSee('Tur Bali Indonesia')
            ->assertViewHas('tour', fn ($tours) => $tours->count() === 1 && $tours->first()->lang === 'id');
        $this->get('/id/tour_packages/bali-tour')->assertOk()->assertSee('Tur Bali Indonesia');
        $this->get('/en/tour_packages/bali-tour')->assertOk()->assertSee('Bali Tour');
    }

    public function test_language_redirects_support_products_activities_and_destinations(): void
    {
        DB::table('products')->insert([
            ['code' => 'P1', 'slug' => 'adventure', 'product_name' => 'Adventure', 'lang' => 'en', 'price' => 25],
            ['code' => 'P1', 'slug' => 'petualangan', 'product_name' => 'Petualangan', 'lang' => 'id', 'price' => 25],
        ]);
        foreach (['activities' => 'activity_fotos', 'destinations' => 'destination_fotos'] as $table => $photos) {
            DB::table($table)->insert([
                ['code' => 'A1', 'slug' => 'english-place', 'name' => 'Place', 'lang' => 'en'],
                ['code' => 'A1', 'slug' => 'tempat-indonesia', 'name' => 'Tempat', 'lang' => 'id'],
            ]);
            DB::table($photos)->insert(['code' => 'A1', 'foto' => 'place.webp']);
        }
        $this->get('/id/mix-detail/adventure')->assertRedirect('/id/mix-detail/petualangan');
        $this->get('/id/activities/english-place')->assertRedirect('/id/activities/tempat-indonesia');
        $this->get('/id/destinations/english-place')->assertRedirect('/id/destinations/tempat-indonesia');
    }

    public function test_sitemap_contains_only_available_localized_public_urls(): void
    {
        config(['app.url' => 'https://example.test']);
        DB::table('tour_packages')->insert([
            ['code' => 'TOUR1', 'slug' => 'wisata-bali', 'tour_name' => 'Wisata Bali', 'lang' => 'id'],
            ['code' => 'NO_PHOTO', 'slug' => 'no-photo', 'tour_name' => 'No photo', 'lang' => 'en'],
        ]);
        DB::table('products')->insert([
            'code' => 'PRODUCT1', 'slug' => 'paket-bali', 'product_name' => 'Paket Bali',
            'lang' => 'id', 'price' => 25,
        ]);
        DB::table('activities')->insert(['code' => 'A1', 'slug' => 'rafting', 'name' => 'Rafting', 'lang' => 'en']);
        DB::table('activity_fotos')->insert(['code' => 'A1', 'foto' => 'rafting.webp']);
        DB::table('destinations')->insert(['code' => 'D1', 'slug' => 'ubud', 'name' => 'Ubud', 'lang' => 'id']);
        DB::table('destination_fotos')->insert(['code' => 'D1', 'foto' => 'ubud.webp']);
        $response = $this->get('/sitemap')->assertOk()->assertHeader('content-type', 'text/xml; charset=utf-8');
        $xml = $response->getContent();
        $document = simplexml_load_string($xml);
        $this->assertNotFalse($document);
        $urls = array_map('strval', iterator_to_array($document->xpath('//*[local-name()="loc"]')));
        $this->assertContains('https://example.test/en', $urls);
        $this->assertContains('https://example.test/id/tour_packages/wisata-bali', $urls);
        $this->assertContains('https://example.test/en/tour_packages/bali-tour', $urls);
        $this->assertContains('https://example.test/id/mix-detail/paket-bali', $urls);
        $this->assertContains('https://example.test/en/activities/rafting', $urls);
        $this->assertContains('https://example.test/id/destinations/ubud', $urls);
        $this->assertNotContains('https://example.test/en/tour_packages/no-photo', $urls);
        $this->assertNotContains('https://example.test/customer/bookings', $urls);
        $this->assertNotContains('https://example.test/checkout/product/1', $urls);
    }

    public function test_public_page_shows_only_active_tour_reviews_and_not_the_form(): void
    {
        foreach ([
            ['TOUR1', 'active', 'Visible review'],
            ['TOUR1', 'inactive', 'Hidden review'],
            ['OTHER', 'active', 'Other tour review'],
        ] as [$code, $status, $comment]) {
            DB::table('review_ratings')->insert([
                'booking_id' => 'BOOK1', 'product_code' => $code, 'guest_name' => 'Guest',
                'guest_email' => 'guest@example.test', 'comments' => $comment,
                'star_rating' => 5, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->get(route('tour_packages.detail', 'bali-tour'))
            ->assertOk()->assertSee('Visible review')
            ->assertDontSee('Hidden review')->assertDontSee('Other tour review')
            ->assertDontSee('Write a Review');
    }

    public function test_rating_summaries_use_all_valid_active_reviews_on_home_listing_and_details(): void
    {
        DB::table('products')->insert([
            'code' => 'PRODUCT1', 'slug' => 'bali-product', 'product_name' => 'Bali Product',
            'lang' => 'en', 'price' => 25, 'product_des' => 'Description',
            'product_foto' => 'product.webp;', 'parent_type' => '',
        ]);
        foreach ([
            ['TOUR1', 'active', 5], ['TOUR1', 'active', 5], ['TOUR1', 'active', 5],
            ['TOUR1', 'active', 5], ['TOUR1', 'active', 4], ['TOUR1', 'active', 4],
            ['TOUR1', 'active', 4], ['TOUR1', 'deactive', 1], ['TOUR1', 'active', 6],
            ['PRODUCT1', 'active', 5], ['PRODUCT1', 'active', 4],
        ] as [$code, $status, $rating]) {
            DB::table('review_ratings')->insert([
                'booking_id' => 'BOOK1', 'product_code' => $code, 'guest_name' => 'Guest',
                'guest_email' => 'guest@example.test', 'comments' => 'Review',
                'star_rating' => $rating, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->get(route('home'))->assertOk()->assertSee('4.6 (7)')->assertSee('4.5 (2)')
            ->assertDontSee('4.8 (95)');
        $this->get(route('tour_packages'))->assertOk()->assertSee('4.6 (7)')->assertSee('4.5 (2)')
            ->assertDontSee('4.8 (95)');
        $this->get(route('tour_packages.detail', 'bali-tour'))->assertOk()->assertSee('4.6 (7)')
            ->assertDontSee('4.5 (2)');
        $this->get(route('mix_packages.detail', 'bali-product'))->assertOk()->assertSee('4.5 (2)')
            ->assertDontSee('4.8 (95)')->assertSee('bi-star-half', false);
    }

    public function test_unreviewed_services_show_no_rating(): void
    {
        $this->get(route('tour_packages.detail', 'bali-tour'))->assertOk()
            ->assertSee('No reviews yet')->assertDontSee('Rated 0');
    }

    public function test_signed_link_is_required_and_must_match_the_reservation(): void
    {
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK2', 'guest_email' => 'guest@example.test',
            'guest_name' => 'Guest', 'code_service' => 'OTHER',
        ]);
        $expired = URL::temporarySignedRoute('tour_reviews.form', now()->subMinute(), [
            'slug' => 'bali-tour', 'email' => 'guest@example.test', 'booking' => 'BOOK1',
        ]);
        $this->get(route('tour_reviews.form', 'bali-tour'))->assertForbidden();
        $this->get($expired)->assertForbidden();
        $this->get($this->reviewLink(['email' => 'other@example.test']))->assertForbidden();
        $this->get($this->reviewLink(['booking' => 'OTHER']))->assertForbidden();
        $this->get($this->reviewLink(['booking' => 'BOOK2']))->assertForbidden();
        $this->get($this->reviewLink() . '&booking=OTHER')->assertForbidden();
        $this->get($this->reviewLink())
            ->assertOk()->assertSee('Write a Review')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertDontSee('googletagmanager', false);
    }

    public function test_review_submission_uses_verified_reservation_and_validates_input(): void
    {
        $link = $this->reviewLink();
        $this->post($link, ['rating' => 6, 'comment' => 'Invalid'])
            ->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('review_ratings', 0);

        $this->post($link, [
            'rating' => 5, 'comment' => 'Great tour', 'email' => 'other@example.test',
            'booking' => 'OTHER', 'product_code' => 'OTHER', 'name' => 'Someone else',
        ])->assertRedirect(route('tour_packages.detail', 'bali-tour'));

        $this->assertDatabaseHas('review_ratings', [
            'booking_id' => 'BOOK1', 'product_code' => 'TOUR1', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.test', 'comments' => 'Great tour', 'star_rating' => 5,
        ]);
        $this->post(route('review.store'), ['product_code' => 'TOUR1'])->assertForbidden();
    }

    public function test_only_admin_can_create_link_for_matching_booking(): void
    {
        $this->get(route('tour_reviews.link', 'bali-tour'))->assertRedirect(route('login'));
        $user = new User();
        $user->role = 'user';
        $this->actingAs($user)->get(route('tour_reviews.link', 'bali-tour'))->assertForbidden();
        $this->post(route('tour_reviews.link.create', 'bali-tour'), [
            'email' => 'guest@example.test', 'booking' => 'BOOK1',
        ])->assertForbidden();

        $user->role = 'admin';
        $this->actingAs($user)->get(route('tour_reviews.link', 'bali-tour'))->assertOk();
        $this->post(route('tour_reviews.link.create', 'bali-tour'), [
            'email' => 'guest@example.test', 'booking' => 'WRONG',
        ])->assertSessionHasErrors('booking');
        $this->post(route('tour_reviews.link.create', 'bali-tour'), [
            'email' => 'guest@example.test', 'booking' => 'BOOK1',
        ])->assertOk()->assertSee('signature=', false);
    }

    public function test_admin_can_create_a_signed_product_review_link_from_order(): void
    {
        DB::table('products')->insert([
        'code' => 'PRODUCT1', 'slug' => 'bali-product', 'product_name' => 'Bali Product',
        'lang' => 'en', 'price' => 25, 'product_des' => 'Product description',
        ]);
        DB::table('reservations')->insert([
        'no_reservasi' => 'BOOK2', 'guest_email' => 'product@example.test',
        'guest_name' => 'Product Guest', 'code_service' => 'PRODUCT1',
        ]);
        $admin = new User();
        $admin->id = 1;
        $admin->role = 'admin';
        $this->actingAs($admin)->get(route('pages.orders.detail', 2))
        ->assertOk()->assertSee('Create review link');
        $response = $this->post(route('pages.orders.review-link', 2))->assertOk();
        preg_match('/<textarea id="review-link"[^>]*>(.*?)<\\/textarea>/s', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $shortLink = html_entity_decode($matches[1], ENT_QUOTES);
        auth()->logout();
        $link = $this->get($shortLink)->assertRedirect()->headers->get('Location');
        $this->assertTrue(URL::hasValidSignature(\Illuminate\Http\Request::create($link)));
        $this->get(route('mix_packages.detail', 'bali-product'))->assertOk()->assertDontSee('Write a Review');
        $this->get(route('product_reviews.form', 'bali-product'))->assertForbidden();
        $this->get($link.'&booking=OTHER')->assertForbidden();
        $wrongBookingLink = URL::temporarySignedRoute('product_reviews.form', now()->addDay(), [
            'slug' => 'bali-product', 'email' => 'guest@example.test', 'booking' => 'BOOK1',
        ]);
        $this->get($wrongBookingLink)->assertForbidden();
        $expiredLink = URL::temporarySignedRoute('product_reviews.form', now()->subMinute(), [
            'slug' => 'bali-product', 'email' => 'product@example.test', 'booking' => 'BOOK2',
        ]);
        $this->get($expiredLink)->assertForbidden();
        $this->get($link)->assertOk()->assertSee('Write a Review')
        ->assertHeader('Referrer-Policy', 'no-referrer')->assertDontSee('googletagmanager', false);
        $this->post($link, ['rating' => 6, 'comment' => 'Invalid'])->assertSessionHasErrors('rating');
        $this->post($link, ['rating' => 5, 'comment' => 'Great product', 'name' => 'Forged'])
        ->assertRedirect(route('mix_packages.detail', 'bali-product'));
        $this->assertDatabaseHas('review_ratings', [
        'booking_id' => 'BOOK2', 'product_code' => 'PRODUCT1', 'guest_name' => 'Product Guest',
        'comments' => 'Great product', 'star_rating' => 5, 'status' => 'active',
        ]);
        $this->get(route('mix_packages.detail', 'bali-product'))->assertOk()->assertSee('Great product');
        $this->post(route('review.store'), ['product_code' => 'PRODUCT1'])->assertForbidden();
        DB::table('reservations')->where('no_reservasi', 'BOOK2')->delete();
        $this->get($link)->assertForbidden();
        $this->get($this->get($shortLink)->assertRedirect()->headers->get('Location'))->assertForbidden();
    }

    public function test_order_short_link_opens_the_tour_review_form_for_the_guest(): void
    {
        $admin = new User();
        $admin->id = 1;
        $admin->role = 'admin';
        $response = $this->actingAs($admin)->post(route('pages.orders.review-link', 1))->assertOk();
        preg_match('/<textarea id="review-link"[^>]*>(.*?)<\\/textarea>/s', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $shortLink = html_entity_decode($matches[1], ENT_QUOTES);

        auth()->logout();
        $signedLink = $this->get($shortLink)->assertRedirect()->headers->get('Location');
        $this->get($signedLink)->assertOk()->assertSee('Write a Review');
        $this->post($signedLink, ['rating' => 5, 'comment' => 'Great tour'])
            ->assertRedirect(route('tour_packages.detail', 'bali-tour'));
        $this->assertDatabaseHas('review_ratings', ['booking_id' => 'BOOK1', 'comments' => 'Great tour']);
        $this->get(route('tour_packages.detail', 'bali-tour'))->assertOk()
            ->assertSee('Great tour')->assertSee('5.0 (1)');

        $this->actingAs($admin)->get(route('pages.orders.detail', 1))
            ->assertOk()->assertSee('Great tour')->assertSee('Rating: 5 / 5');
        $this->delete(route('pages.orders.reviews.delete', ['order' => 1, 'review' => 1]))
            ->assertRedirect(route('pages.orders.detail', 1));
        $this->get(route('tour_packages.detail', 'bali-tour'))->assertOk()
            ->assertDontSee('Great tour')->assertSee('No reviews yet')->assertDontSee('5.0 (1)');
    }

    public function test_ambiguous_service_codes_cannot_generate_an_order_review_link(): void
    {
        DB::table('products')->insert([
        'code' => 'TOUR1', 'slug' => 'bali-product', 'product_name' => 'Bali Product',
        'lang' => 'en', 'price' => 25,
        ]);
        $admin = new User();
        $admin->id = 1;
        $admin->role = 'admin';
        $this->actingAs($admin)->get(route('pages.orders.detail', 1))
        ->assertOk()->assertSee('matches both a tour and a product')->assertDontSee('Create review link');
        $this->post(route('pages.orders.review-link', 1))->assertStatus(409);
    }

    public function test_product_itinerary_saves_references_notes_categories_and_photo_gallery(): void
    {
        DB::table('activities')->insert(['id' => 1, 'code' => 'ACT1', 'lang' => 'en', 'name' => 'Rafting', 'slug' => 'rafting']);
        DB::table('destinations')->insert(['id' => 2, 'code' => 'DST1', 'lang' => 'en', 'name' => 'Waterfall', 'slug' => 'waterfall']);
        DB::table('activity_fotos')->insert(['code' => 'ACT1', 'foto' => 'one.webp;two.webp;three.webp;four.webp;']);
        DB::table('destination_fotos')->insert(['code' => 'DST1', 'foto' => 'waterfall.webp;']);
        $admin = new User();
        $admin->id = 1;
        $admin->role = 'admin';
        $this->actingAs($admin)->post(route('products.store'), [
            'code' => 'PRODUCT1', 'product_name' => 'Bali Product', 'lang' => 'en', 'price' => 25,
            'parent_type' => ['EXISTING'], 'itinerary' => [
                ['reference' => 'activity:1', 'note' => 'Bring shoes; towel'],
                ['reference' => 'destination:2', 'note' => '<script>alert(1)</script>'],
            ],
        ])->assertRedirect(route('pages.products', 'all'));

        $product = DB::table('products')->where('code', 'PRODUCT1')->first();
        $items = json_decode($product->itinerary, true)['items'];
        $this->assertSame('activity:1', $items[0]['reference']);
        $this->assertSame('Bring shoes; towel', $items[0]['note']);
        $this->assertSame('destination:2', $items[1]['reference']);
        $this->assertSame('EXISTING;ACT1;', $product->parent_type);
        $this->assertSame('DST1;', $product->dst_type);
        $this->get(route('products.edit', $product->id))->assertOk()
            ->assertSee('activity:1', false)->assertSee('destination:2', false)
            ->assertSee('Bring shoes; towel');

        $response = $this->get(route('mix_packages.detail', 'bali-product'))->assertOk()
            ->assertSee(route('activities.detail', 'rafting'))
            ->assertSee(route('destinations.detail', 'waterfall'))
            ->assertSee(asset('assets/img/activity/one.webp'))
            ->assertSee(asset('assets/img/activity/three.webp'))
            ->assertDontSee(asset('assets/img/activity/four.webp'))
            ->assertSee(asset('assets/img/destinasi/waterfall.webp'))
            ->assertSee('Bring shoes; towel')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $activityPhotos = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " glightbox ") and @data-gallery="itinerary-0"]');
        $destinationPhotos = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " glightbox ") and @data-gallery="itinerary-1"]');
        $this->assertCount(3, $activityPhotos);
        $this->assertCount(1, $destinationPhotos);
        $this->assertSame(asset('assets/img/activity/one.webp'), $activityPhotos->item(0)->getAttribute('href'));
        $this->assertSame(asset('assets/img/destinasi/waterfall.webp'), $destinationPhotos->item(0)->getAttribute('href'));
    }

    public function test_legacy_itinerary_is_preserved_and_invalid_references_are_rejected(): void
    {
        DB::table('products')->insert([
            'id' => 1, 'code' => 'PRODUCT1', 'slug' => 'bali-product', 'product_name' => 'Bali Product',
            'lang' => 'en', 'price' => 25, 'itinerary' => 'Old <strong>stop</strong>;Pickup',
            'product_des' => 'Description', 'product_foto' => 'product.webp;', 'parent_type' => '', 'dst_type' => '',
        ]);
        DB::table('activities')->insert(['id' => 1, 'code' => 'ACT1', 'lang' => 'en', 'name' => 'Rafting', 'slug' => 'rafting']);
        $admin = new User();
        $admin->id = 1;
        $admin->role = 'admin';
        $this->actingAs($admin)->get(route('products.edit', 1))->assertOk()
            ->assertSee('Old &lt;strong&gt;stop&lt;/strong&gt;', false)->assertSee('Pickup');
        $data = [
            'id' => 1, 'code' => 'PRODUCT1', 'product_name' => 'Bali Product', 'lang' => 'en', 'price' => 25,
            'document' => ['product.webp'], 'itinerary' => [
                ['reference' => 'activity:1', 'note' => 'Old <strong>stop</strong>'],
                ['reference' => '', 'note' => 'Pickup'],
            ],
        ];
        $this->post(route('products.store'), $data)->assertRedirect(route('pages.products', 'all'));
        $this->get(route('mix_packages.detail', 'bali-product'))->assertOk()
            ->assertSee('Old <strong>stop</strong>', false)->assertSee('Pickup');
        $stored = DB::table('products')->where('id', 1)->value('itinerary');
        $data['itinerary'][0]['reference'] = 'activity:999';
        $this->from(route('products.edit', 1))->post(route('products.store'), $data)
            ->assertRedirect(route('products.edit', 1))->assertSessionHasErrors('itinerary.0.reference');
        $this->assertSame($stored, DB::table('products')->where('id', 1)->value('itinerary'));
    }
}
