<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.locale' => 'en']);
        DB::purge('sqlite');

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('no_reservasi');
            $table->string('tgl_reservasi')->nullable();
            $table->string('guest_name');
            $table->string('code_service')->nullable();
            $table->string('status')->nullable();
            $table->string('book_status')->nullable();
        });
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('booking_type');
            $table->string('destination');
            $table->string('pickup_date');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('review_ratings', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name');
            $table->string('product_code');
            $table->text('comments');
            $table->integer('star_rating');
            $table->string('status');
            $table->timestamp('created_at')->nullable();
        });
        foreach (['products' => 'product_name', 'tour_packages' => 'tour_name'] as $name => $nameColumn) {
            Schema::create($name, function (Blueprint $table) use ($nameColumn) {
                $table->id();
                $table->string('code');
                $table->string('lang');
                $table->string($nameColumn);
            });
        }
    }

    private function user(string $role): User
    {
        $user = new User();
        $user->id = 1;
        $user->role = $role;
        $user->name = 'Test User';
        return $user;
    }

    public function test_dashboard_is_private_and_displays_empty_states(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->actingAs($this->user('user'))->get(route('dashboard'))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('dashboard'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('No bookings found.')->assertSee('No transfer orders found.')
            ->assertSee('No reviews found.')->assertSee('No qualifying bookings found.');
    }

    public function test_dashboard_shows_the_five_most_recent_records(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            DB::table('reservations')->insert([
                'no_reservasi' => 'BOOK-'.$i, 'guest_name' => '<Guest '.$i.'>', 'code_service' => 'P',
                'status' => 'PENDING', 'book_status' => 'New',
            ]);
            DB::table('transfer_orders')->insert([
                'name' => 'Transfer '.$i, 'booking_type' => 'arrival', 'destination' => 'Ubud',
                'pickup_date' => '2026-12-01',
            ]);
            DB::table('review_ratings')->insert([
                'guest_name' => 'Reviewer '.$i, 'product_code' => 'P', 'comments' => '<Review '.$i.'>',
                'star_rating' => 4, 'status' => 'active',
            ]);
        }

        $response = $this->actingAs($this->user('admin'))->get(route('dashboard'))->assertOk()
            ->assertSee('&lt;Guest 6&gt;', false)->assertSee('&lt;Review 6&gt;', false)
            ->assertSee(route('pages.orders.detail', 6), false);
        $this->assertSame(['BOOK-6', 'BOOK-5', 'BOOK-4', 'BOOK-3', 'BOOK-2'], $response->viewData('bookings')->pluck('no_reservasi')->all());
        $this->assertSame(['Transfer 6', 'Transfer 5', 'Transfer 4', 'Transfer 3', 'Transfer 2'], $response->viewData('transfers')->pluck('name')->all());
        $this->assertSame(['Reviewer 6', 'Reviewer 5', 'Reviewer 4', 'Reviewer 3', 'Reviewer 2'], $response->viewData('reviews')->pluck('guest_name')->all());
    }

    public function test_best_sellers_count_bookings_once_and_exclude_cancelled_and_ambiguous_codes(): void
    {
        DB::table('products')->insert([
            ['code' => 'P1', 'lang' => 'id', 'product_name' => 'Produk'],
            ['code' => 'P1', 'lang' => 'en', 'product_name' => 'Product'],
            ['code' => 'P2', 'lang' => 'en', 'product_name' => 'Other product'],
            ['code' => 'SHARED', 'lang' => 'en', 'product_name' => 'Shared product'],
        ]);
        DB::table('tour_packages')->insert([
            ['code' => 'T1', 'lang' => 'en', 'tour_name' => 'Tour'],
            ['code' => 'T1', 'lang' => 'id', 'tour_name' => 'Tur'],
            ['code' => 'SHARED', 'lang' => 'en', 'tour_name' => 'Shared tour'],
        ]);
        foreach ([
            ['P1', 'New'], ['P1', null], ['P1', 'Cancelled'], ['P2', 'Confirmed'],
            ['T1', 'Completed'], ['T1', ' canceled '], ['SHARED', 'New'], ['UNKNOWN', 'New'],
        ] as $i => [$code, $status]) {
            DB::table('reservations')->insert([
                'no_reservasi' => 'BOOK-'.$i, 'guest_name' => 'Guest', 'code_service' => $code,
                'book_status' => $status,
            ]);
        }

        $response = $this->actingAs($this->user('admin'))->get(route('dashboard'))->assertOk();
        $products = $response->viewData('topProducts');
        $tours = $response->viewData('topTours');
        $this->assertSame(['P1', 'P2'], $products->pluck('code_service')->all());
        $this->assertSame([2, 1], $products->pluck('booking_count')->all());
        $this->assertSame('Product', $products->first()->name);
        $this->assertSame(['T1'], $tours->pluck('code_service')->all());
        $this->assertSame(1, $tours->first()->booking_count);
        $this->assertSame('Tour', $tours->first()->name);
    }

    public function test_best_seller_list_is_limited_and_ties_are_sorted_by_code(): void
    {
        for ($i = 6; $i >= 1; $i--) {
            DB::table('products')->insert(['code' => 'P'.$i, 'lang' => 'en', 'product_name' => 'Product '.$i]);
            DB::table('reservations')->insert([
                'no_reservasi' => 'BOOK-'.$i, 'guest_name' => 'Guest', 'code_service' => 'P'.$i, 'book_status' => 'New',
            ]);
        }

        $response = $this->actingAs($this->user('admin'))->get(route('dashboard'))->assertOk();
        $this->assertSame(['P1', 'P2', 'P3', 'P4', 'P5'], $response->viewData('topProducts')->pluck('code_service')->all());
    }
}
