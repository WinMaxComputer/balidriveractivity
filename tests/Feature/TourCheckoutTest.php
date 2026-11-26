<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;
use Tests\TestCase;

class TourCheckoutTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('product_name');
            $table->integer('price');
        });
        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('tour_name');
            $table->text('price');
            $table->decimal('price_usd', 10, 2)->nullable();
            $table->string('type')->nullable();
            $table->string('slug')->nullable();
            $table->string('area_tour')->nullable();
            $table->text('itinerary')->nullable();
            $table->text('destination')->nullable();
            $table->text('activity')->nullable();
            $table->string('lang')->nullable();
            $table->text('payment')->nullable();
            $table->text('note')->nullable();
            $table->text('pickup')->nullable();
            $table->timestamps();
        });
        Schema::create('tour_fotos', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('foto');
            $table->timestamps();
        });
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('no_reservasi')->unique();
            $table->string('checkout_token', 64)->nullable()->unique();
            $table->string('code_service');
            $table->dateTime('tgl_reservasi');
            $table->string('guest_email', 100);
            $table->string('guest_name');
            $table->string('payment_type');
            $table->string('adult', 5);
            $table->decimal('total', 13, 2);
            $table->decimal('guest_paid', 10, 2);
            $table->dateTime('cek_in');
            $table->dateTime('cek_out');
            $table->string('book_status');
            $table->string('status');
            $table->string('room_no');
            $table->timestamps();
        });
        DB::table('products')->insert(['id' => 1, 'code' => 'MIX1', 'product_name' => 'Mix', 'price' => 25]);
        DB::table('tour_packages')->insert(['id' => 1, 'code' => 'TOUR1', 'tour_name' => 'Tour', 'price' => '<p>Contact us</p>', 'price_usd' => 32.50]);

        $this->server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/checkout';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    private function checkoutData(string $type, string $price): array
    {
        $response = $this->get(route('checkout.show', [
            'type' => $type, 'id' => 1, 'travel_date' => now()->addDay()->toDateString(), 'travelers' => 3,
        ]))->assertOk()->assertSee('value="3"', false)
            ->assertSee($type === 'tour' ? 'USD 97.50' : 'USD 75.00');
        preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        return [
            'token' => $matches[1],
            'name' => 'Test Guest',
            'email' => 'guest@example.test',
            'travel_date' => now()->addDay()->toDateString(),
            'travelers' => 3,
            'unit_price' => $price,
        ];
    }

    public function test_product_checkout_stores_unpaid_reservation_once(): void
    {
        $data = $this->checkoutData('product', '25.00');
        $route = route('checkout.store', ['type' => 'product', 'id' => 1]);
        $response = $this->post($route, $data)->assertRedirect(route('checkout.confirmation'));
        $number = $response->getSession()->get('checkout_number');
        $this->assertMatchesRegularExpression('/^BOOK-[A-Z0-9]{8}$/', $number);
        $this->assertDatabaseHas('reservations', [
            'no_reservasi' => $number, 'code_service' => 'MIX1', 'total' => 75,
            'adult' => '3', 'guest_paid' => 0, 'payment_type' => 'Unpaid',
            'status' => 'PENDING', 'book_status' => 'New', 'room_no' => '',
            'cek_in' => $data['travel_date'].' 00:00:00', 'cek_out' => $data['travel_date'].' 00:00:00',
        ]);
        $this->get(route('checkout.confirmation'))->assertOk()->assertSee($number)->assertSee('unpaid')
            ->assertSee('id="navmenu"', false);
        $this->post($route, $data)->assertRedirect(route('checkout.confirmation'));
        $this->assertSame(1, DB::table('reservations')->count());
    }

    public function test_checkout_retries_a_duplicate_booking_number(): void
    {
        $first = $this->checkoutData('product', '25.00');
        $second = $this->checkoutData('product', '25.00');
        $route = route('checkout.store', ['type' => 'product', 'id' => 1]);
        $numbers = ['AAAAAAAA', 'AAAAAAAA', 'BBBBBBBB'];
        Str::createRandomStringsUsing(function (int $length) use (&$numbers): string {
            return $length === 8 ? array_shift($numbers) : str_repeat('z', $length);
        });

        try {
            $this->post($route, $first)->assertRedirect(route('checkout.confirmation'))
                ->assertSessionHas('checkout_number', 'BOOK-AAAAAAAA');
            $this->post($route, $second)->assertRedirect(route('checkout.confirmation'))
                ->assertSessionHas('checkout_number', 'BOOK-BBBBBBBB');
        } finally {
            Str::createRandomStringsNormally();
        }

        $this->assertSame(2, DB::table('reservations')->count());
    }

    public function test_checkout_includes_detail_navigation_with_working_language_links(): void
    {
        $_SERVER['REQUEST_URI'] = '/checkout/product/1';
        $response = $this->get(route('checkout.show', ['type' => 'product', 'id' => 1]))
            ->assertOk()->assertSee('id="navmenu"', false)
            ->assertSee('page-title dark-background', false)
            ->assertSee(asset('assets/img/travel/showcase-8.webp'), false)
            ->assertSee(route('lang', ['lang' => 'en']), false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="navmenu"'));
    }

    public function test_tour_uses_numeric_usd_price_not_text_description(): void
    {
        $data = $this->checkoutData('tour', '32.50');
        $this->post(route('checkout.store', ['type' => 'tour', 'id' => 1]), $data)
            ->assertRedirect(route('checkout.confirmation'));
        $this->assertDatabaseHas('reservations', ['code_service' => 'TOUR1', 'total' => 97.50]);
    }

    public function test_checkout_displays_selected_currency_but_stores_usd(): void
    {
        $this->withSession(['currency' => 'IDR', 'currency_rate' => 16020]);
        $response = $this->get(route('checkout.show', [
            'type' => 'tour', 'id' => 1, 'travelers' => 3,
        ]))->assertOk()->assertSee('IDR 521,000 per person')
            ->assertSee('IDR 1,563,000')
            ->assertSee('reservation total is recorded in USD')
            ->assertSee('name="unit_price" value="32.50"', false);
        preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $this->post(route('checkout.store', ['type' => 'tour', 'id' => 1]), [
            'token' => $matches[1], 'name' => 'Guest', 'email' => 'guest@example.test',
            'travel_date' => now()->addDay()->toDateString(), 'travelers' => 3, 'unit_price' => '32.50',
        ])->assertRedirect(route('checkout.confirmation'));
        $this->assertDatabaseHas('reservations', ['code_service' => 'TOUR1', 'total' => 97.50]);

        $this->get(route('checkout.show', ['type' => 'product', 'id' => 1]))
            ->assertOk()->assertSee('IDR 401,000 per person')->assertSee('IDR 401,000');
    }

    public function test_checkout_warns_if_the_selected_exchange_rate_is_unavailable(): void
    {
        $this->withSession(['currency' => 'IDR', 'currency_rate' => 0])
            ->get(route('checkout.show', ['type' => 'product', 'id' => 1]))
            ->assertOk()->assertSee('exchange rate is unavailable')
            ->assertSee('USD 25.00 per person');
    }

    public function test_currency_change_only_updates_session_after_a_valid_exchange_rate(): void
    {
        Http::fakeSequence()
            ->push(['conversion_rates' => ['IDR' => 16020]], 200)
            ->pushStatus(503);
        $this->from('/checkout/product/1')->get(route('currency', ['currency' => 'IDR']))
            ->assertRedirect('/checkout/product/1')
            ->assertSessionHas('currency', 'IDR')->assertSessionHas('currency_rate', 16020);

        $this->withSession(['currency' => 'USD', 'currency_rate' => 1])
            ->get(route('currency', ['currency' => 'IDR']))
            ->assertStatus(503)->assertSessionHas('currency', 'USD')->assertSessionHas('currency_rate', 1);
    }

    public function test_invalid_or_tampered_checkout_does_not_create_order(): void
    {
        $data = $this->checkoutData('tour', '32.50');
        $route = route('checkout.store', ['type' => 'tour', 'id' => 1]);
        $this->post($route, array_merge($data, ['unit_price' => '1']))->assertSessionHasErrors('unit_price');
        $this->post($route, array_merge($data, ['travel_date' => now()->subDay()->toDateString(), 'travelers' => 0]))
            ->assertSessionHasErrors(['travel_date', 'travelers']);
        $this->post($route, array_merge($data, ['token' => str_repeat('a', 64)]))->assertForbidden();
        DB::table('tour_packages')->where('id', 1)->update(['price_usd' => 40]);
        $this->post($route, $data)->assertSessionHasErrors('unit_price');
        DB::table('tour_packages')->where('id', 1)->update(['price_usd' => null]);
        $this->get(route('checkout.show', ['type' => 'tour', 'id' => 1]))->assertStatus(422)->assertSee('not currently available');
        $this->assertSame(0, DB::table('reservations')->count());
    }

    public function test_admin_can_save_a_numeric_tour_price(): void
    {
        $user = new User();
        $user->id = 1;
        $user->role = 'admin';
        $data = [
            'id' => 1, 'code' => 'TOUR1', 'tour_name' => 'Tour', 'type' => 'Tour',
            'slug' => 'tour', 'area_tour' => '1', 'itinerary' => 'Itinerary',
            'price' => 'Contact us', 'price_usd' => '100', 'lang' => 'en',
            'payment' => 'Payment', 'note' => 'Note', 'pickup' => 'Pickup',
        ];
        $this->actingAs($user)->post(route('tour.store'), $data)->assertRedirect(route('pages.tour'));
        $this->assertDatabaseHas('tour_packages', ['id' => 1, 'price' => 'Contact us', 'price_usd' => 100]);
        $this->post(route('tour.store'), array_merge($data, ['price_usd' => '1.999']))
            ->assertSessionHasErrors('price_usd');
        $this->assertDatabaseHas('tour_packages', ['id' => 1, 'price_usd' => 100]);
    }

    public function test_usd_product_display_does_not_apply_an_exchange_rate(): void
    {
        session(['currency_rate' => 16000]);
        $this->assertSame(25.0, convertPrice(25, 'USD'));
    }
}
