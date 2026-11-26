<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('no_reservasi');
            $table->string('tgl_reservasi')->nullable();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('code_service');
            $table->string('cek_in')->nullable();
            $table->string('cek_out')->nullable();
            $table->integer('adult')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->decimal('guest_paid', 12, 2)->nullable();
            $table->string('payment_type')->nullable();
            $table->string('status')->nullable();
            $table->string('book_status')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('reservation_room_detail', function (Blueprint $table) {
            $table->id();
            $table->string('no_reservasi');
        });
        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('slug');
            $table->string('tour_name');
            $table->string('lang');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('slug');
            $table->string('lang');
            $table->string('product_name')->nullable();
        });
        Schema::create('short_review_links', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->text('target_url');
            $table->dateTime('expires_at');
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

        $this->server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/orders';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'Test User';
        $user->role = $role;

        return $user;
    }

    public function test_orders_are_only_available_to_admins(): void
    {
        $this->get(route('pages.orders'))->assertRedirect(route('login'));
        $this->get(route('pages.orders.data'))->assertRedirect(route('login'));
        $this->actingAs($this->user('user'))->get(route('pages.orders'))->assertForbidden();
        $this->get(route('pages.orders.data'))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('pages.orders'))
            ->assertOk()->assertSee('No orders found.')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_orders_are_displayed_newest_first_and_paginated(): void
    {
        for ($number = 1; $number <= 21; $number++) {
            DB::table('reservations')->insert([
                'no_reservasi' => sprintf('BOOK-%02d', $number),
                'tgl_reservasi' => '2026-01-01',
                'guest_name' => '<Guest>',
                'guest_email' => 'guest@example.test',
                'code_service' => 'TOUR1',
                'status' => 'PENDING',
            ]);
        }

        $this->actingAs($this->user('admin'))->get(route('pages.orders'))
            ->assertOk()->assertSeeInOrder(['BOOK-21', 'BOOK-20', 'BOOK-02'])
            ->assertDontSee('BOOK-01')->assertSee('&lt;Guest&gt;', false)
            ->assertSee('guest@example.test')->assertSee('PENDING');

        $this->get(route('pages.orders', ['page' => 2]))
            ->assertOk()->assertSee('BOOK-01')->assertDontSee('BOOK-21');
    }

    public function test_datatable_searches_sorts_and_paginates_all_orders(): void
    {
        for ($number = 1; $number <= 25; $number++) {
            DB::table('reservations')->insert([
                'no_reservasi' => sprintf('BOOK-%02d', $number),
                'guest_name' => $number <= 3 ? 'Special Guest' : 'Other Guest',
                'guest_email' => 'guest@example.test',
                'code_service' => 'TOUR1',
                'status' => 'PENDING',
            ]);
        }

        $this->actingAs($this->user('admin'));
        $this->getJson(route('pages.orders.data', [
            'draw' => 3, 'start' => 20, 'length' => 5,
        ]))->assertOk()->assertJsonPath('draw', 3)
            ->assertJsonPath('recordsTotal', 25)->assertJsonPath('recordsFiltered', 25)
            ->assertJsonCount(5, 'data')->assertJsonPath('data.0.no_reservasi', 'BOOK-05')
            ->assertJsonPath('data.0.id', 5);

        $this->getJson(route('pages.orders.data', [
            'draw' => 4, 'start' => 1, 'length' => 2,
            'search' => ['value' => 'Special'],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]))->assertOk()->assertJsonPath('recordsTotal', 25)
            ->assertJsonPath('recordsFiltered', 3)->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.no_reservasi', 'BOOK-02')
            ->assertJsonPath('data.1.no_reservasi', 'BOOK-03');
    }

    public function test_datatable_rejects_invalid_paging_and_sorting(): void
    {
        $this->actingAs($this->user('admin'))
            ->getJson(route('pages.orders.data', [
                'draw' => 'bad', 'start' => -1, 'length' => 1000,
                'order' => [['column' => 99, 'dir' => 'invalid']],
            ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['draw', 'start', 'length', 'order.0.column', 'order.0.dir']);
    }

    public function test_order_detail_and_review_link_require_admin_and_match_the_booking(): void
    {
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.test', 'code_service' => 'TOUR1',
        ]);
        DB::table('tour_packages')->insert([
            'code' => 'TOUR1', 'slug' => 'bali-tour', 'tour_name' => 'Bali Tour', 'lang' => 'en',
        ]);

        $this->get(route('pages.orders.detail', 1))->assertRedirect(route('login'));
        $this->actingAs($this->user('user'))->get(route('pages.orders.detail', 1))->assertForbidden();
        $this->post(route('pages.orders.review-link', 1))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('pages.orders.detail', 1))
            ->assertOk()->assertSee('BOOK-01')->assertSee('Bali Tour')->assertSee('Create review link')
            ->assertDontSee('id="copy-review-link"', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $response = $this->post(route('pages.orders.review-link', 1))
            ->assertOk()->assertSee('id="copy-review-link"', false)
            ->assertSee('Bali Tour')->assertSee('Copy link')->assertSee('id="copy-review-link-status"', false);
        preg_match('/<textarea id="review-link"[^>]*>(.*?)<\\/textarea>/s', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);
        $shortLink = html_entity_decode($matches[1], ENT_QUOTES);
        $this->assertMatchesRegularExpression('~/r/[A-Za-z0-9]{22}$~', $shortLink);
        $this->assertStringNotContainsString('guest@example.test', $shortLink);
        $saved = DB::table('short_review_links')->first();
        $this->assertStringNotContainsString('guest@example.test', $saved->target_url);
        $redirect = $this->get($shortLink)->assertRedirect()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $link = $redirect->headers->get('Location');
        $this->assertTrue(URL::hasValidSignature(\Illuminate\Http\Request::create($link)));
        parse_str(parse_url($link, PHP_URL_QUERY), $query);
        $this->assertSame('BOOK-01', $query['booking']);
        $this->assertSame('guest@example.test', $query['email']);
        $this->get(route('review_links.open', str_repeat('Z', 22)))->assertNotFound();

        $this->travel(31)->days();
        $this->get($shortLink)->assertNotFound();
        $this->travelBack();

        DB::table('reservations')->where('id', 1)->update(['guest_email' => 'invalid']);
        $this->from(route('pages.orders.detail', 1))->post(route('pages.orders.review-link', 1))
            ->assertRedirect(route('pages.orders.detail', 1))->assertSessionHasErrors('email');
        DB::table('tour_packages')->delete();
        $this->get(route('pages.orders.detail', 1))->assertOk()->assertSee('Name unavailable')->assertSee('a review link is unavailable');
        $this->post(route('pages.orders.review-link', 1))->assertNotFound();
        $this->get(route('pages.orders.detail', 999))->assertNotFound();
    }

    public function test_order_detail_displays_product_name_and_warns_about_ambiguous_codes(): void
    {
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.test', 'code_service' => 'MIX1',
        ]);
        DB::table('products')->insert([
            ['code' => 'MIX1', 'slug' => '', 'lang' => 'en', 'product_name' => 'Bali <Adventure>'],
            ['code' => 'MIX1', 'slug' => 'petualangan', 'lang' => 'id', 'product_name' => 'Petualangan Bali'],
        ]);
        config(['app.locale' => 'en']);
        $this->actingAs($this->user('admin'))->get(route('pages.orders.detail', 1))
            ->assertOk()->assertSee('Product / tour')
            ->assertSee('Bali &lt;Adventure&gt;', false)->assertDontSee('Petualangan Bali');

        DB::table('tour_packages')->insert([
            'code' => 'MIX1', 'slug' => 'bali-tour', 'tour_name' => 'Bali Tour', 'lang' => 'en',
        ]);
        $this->get(route('pages.orders.detail', 1))->assertOk()
            ->assertSee('Ambiguous code')->assertSee('Bali Tour')->assertSee('Bali &lt;Adventure&gt;', false);
    }

    public function test_admin_can_update_payment_and_booking_details(): void
    {
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.test', 'code_service' => 'TOUR1',
            'payment_type' => 'Unpaid', 'status' => 'PENDING', 'book_status' => 'New',
            'total' => 100, 'guest_paid' => 0,
        ]);
        $route = route('pages.orders.update', 1);
        $data = ['payment_type' => 'Bank transfer', 'status' => 'Downpayment', 'guest_paid' => '25.50', 'book_status' => 'Confirmed'];

        $this->put($route, $data)->assertRedirect(route('login'));
        $this->actingAs($this->user('user'))->put($route, $data)->assertForbidden();
        $this->assertDatabaseHas('reservations', ['id' => 1, 'status' => 'PENDING', 'guest_paid' => 0]);

        $this->actingAs($this->user('admin'))->get(route('pages.orders.detail', 1))
            ->assertOk()->assertSee('Update payment and booking')->assertSee('name="guest_paid"', false);
        $this->put($route, $data)->assertRedirect(route('pages.orders.detail', 1))
            ->assertSessionHas('order_message');
        $this->assertDatabaseHas('reservations', [
            'id' => 1, 'payment_type' => 'Bank transfer', 'status' => 'Downpayment',
            'guest_paid' => 25.50, 'book_status' => 'Confirmed',
        ]);

        $this->put($route, array_merge($data, ['status' => 'PAID', 'guest_paid' => '1', 'book_status' => 'Completed']))
            ->assertRedirect(route('pages.orders.detail', 1));
        $this->assertDatabaseHas('reservations', ['id' => 1, 'status' => 'PAID', 'guest_paid' => 100, 'book_status' => 'Completed']);
        $this->put($route, array_merge($data, ['status' => 'PENDING', 'guest_paid' => '99']))
            ->assertRedirect(route('pages.orders.detail', 1));
        $this->assertDatabaseHas('reservations', ['id' => 1, 'status' => 'PENDING', 'guest_paid' => 0]);
        $this->put(route('pages.orders.update', 999), $data)->assertNotFound();
    }

    public function test_order_detail_displays_only_matching_reviews_and_admin_can_delete_one(): void
    {
        DB::table('reservations')->insert([
            ['no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest', 'guest_email' => 'guest@example.test', 'code_service' => 'TOUR1'],
            ['no_reservasi' => 'BOOK-02', 'guest_name' => 'Other', 'guest_email' => 'other@example.test', 'code_service' => 'TOUR1'],
        ]);
        DB::table('tour_packages')->insert([
            'code' => 'TOUR1', 'slug' => 'bali-tour', 'tour_name' => 'Bali Tour', 'lang' => 'en',
        ]);
        foreach ([
            ['BOOK-01', 'TOUR1', '<script>First review</script>', 'active'],
            ['BOOK-01', 'TOUR1', 'Second review', 'inactive'],
            ['BOOK-02', 'TOUR1', 'Other booking review', 'active'],
            ['BOOK-01', 'OTHER', 'Other service review', 'active'],
        ] as [$booking, $code, $comment, $status]) {
            DB::table('review_ratings')->insert([
                'booking_id' => $booking, 'product_code' => $code, 'guest_name' => 'Guest',
                'guest_email' => 'guest@example.test', 'comments' => $comment, 'star_rating' => 4,
                'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $delete = route('pages.orders.reviews.delete', ['order' => 1, 'review' => 1]);
        $this->delete($delete)->assertRedirect(route('login'));
        $this->actingAs($this->user('user'))->delete($delete)->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('pages.orders.detail', 1))
            ->assertOk()->assertSee('&lt;script&gt;First review&lt;/script&gt;', false)
            ->assertSee('Second review')->assertSee('Rating: 4 / 5')->assertSee('inactive')
            ->assertDontSee('Other booking review')->assertDontSee('Other service review')
            ->assertSee($delete);
        $this->post(route('pages.orders.review-link', 1))->assertOk()
            ->assertSee('Second review')->assertSee('Delete review');

        $this->delete(route('pages.orders.reviews.delete', ['order' => 1, 'review' => 3]))->assertNotFound();
        $this->delete(route('pages.orders.reviews.delete', ['order' => 1, 'review' => 4]))->assertNotFound();
        $this->delete(route('pages.orders.reviews.delete', ['order' => 999, 'review' => 1]))->assertNotFound();
        $this->delete($delete)->assertRedirect(route('pages.orders.detail', 1))->assertSessionHas('order_message');
        $this->assertDatabaseMissing('review_ratings', ['id' => 1]);
        $this->assertDatabaseHas('review_ratings', ['id' => 2]);
        $this->assertDatabaseHas('review_ratings', ['id' => 3]);
        $this->assertDatabaseHas('review_ratings', ['id' => 4]);
        $this->delete($delete)->assertNotFound();
    }

    public function test_review_deletion_rejects_a_shared_booking_number(): void
    {
        DB::table('reservations')->insert([
            ['no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest', 'guest_email' => 'guest@example.test', 'code_service' => 'TOUR1'],
            ['no_reservasi' => 'BOOK-01', 'guest_name' => 'Other', 'guest_email' => 'other@example.test', 'code_service' => 'TOUR1'],
        ]);
        DB::table('review_ratings')->insert([
            'booking_id' => 'BOOK-01', 'product_code' => 'TOUR1', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.test', 'comments' => 'Review', 'star_rating' => 5, 'status' => 'active',
        ]);

        $this->actingAs($this->user('admin'))->delete(route('pages.orders.reviews.delete', ['order' => 1, 'review' => 1]))
            ->assertStatus(409);
        $this->assertDatabaseHas('review_ratings', ['id' => 1]);
    }

    public function test_invalid_order_updates_do_not_change_the_reservation(): void
    {
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.test', 'code_service' => 'TOUR1',
            'payment_type' => 'Unpaid', 'status' => 'PENDING', 'book_status' => 'New',
            'total' => 100, 'guest_paid' => 0,
        ]);
        $this->actingAs($this->user('admin'));
        $route = route('pages.orders.update', 1);
        $data = ['payment_type' => 'Cash', 'status' => 'Downpayment', 'guest_paid' => '20', 'book_status' => 'Confirmed'];
        foreach (['0', '100', '101', '1.001', '-1', '999999999.99'] as $amount) {
            $this->put($route, array_merge($data, ['guest_paid' => $amount]))
                ->assertSessionHasErrors('guest_paid');
        }
        $this->put($route, array_merge($data, ['payment_type' => '', 'status' => 'invalid', 'book_status' => 'invalid']))
            ->assertSessionHasErrors(['payment_type', 'status', 'book_status']);
        $this->assertDatabaseHas('reservations', [
            'id' => 1, 'payment_type' => 'Unpaid', 'status' => 'PENDING',
            'guest_paid' => 0, 'book_status' => 'New',
        ]);

        DB::table('reservations')->where('id', 1)->update(['total' => 100000000]);
        $this->put($route, array_merge($data, ['status' => 'PAID']))
            ->assertSessionHasErrors('guest_paid');
        $this->assertDatabaseHas('reservations', ['id' => 1, 'status' => 'PENDING', 'guest_paid' => 0]);
    }

    public function test_existing_provider_status_can_be_preserved_but_not_assigned_to_other_orders(): void
    {
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.test', 'code_service' => 'TOUR1',
            'payment_type' => 'Card-Midtrans', 'status' => 'settlement', 'book_status' => 'New',
            'total' => 100, 'guest_paid' => 100,
        ]);
        $this->actingAs($this->user('admin'))->get(route('pages.orders.detail', 1))
            ->assertOk()->assertSee('settlement (current)');
        $this->put(route('pages.orders.update', 1), [
            'payment_type' => 'Card-Midtrans', 'status' => 'settlement',
            'guest_paid' => '100', 'book_status' => 'Confirmed',
        ])->assertRedirect(route('pages.orders.detail', 1));
        $this->assertDatabaseHas('reservations', ['id' => 1, 'status' => 'settlement', 'book_status' => 'Confirmed']);
        $this->put(route('pages.orders.update', 1), [
            'payment_type' => 'Card-Midtrans', 'status' => 'capture',
            'guest_paid' => '100', 'book_status' => 'Confirmed',
        ])->assertSessionHasErrors('status');
    }

    public function test_delete_removes_only_the_order_and_its_room_details(): void
    {
        DB::table('reservations')->insert([
            ['no_reservasi' => 'BOOK-01', 'guest_name' => 'Guest', 'guest_email' => 'guest@example.test', 'code_service' => 'TOUR1'],
            ['no_reservasi' => 'BOOK-02', 'guest_name' => 'Other', 'guest_email' => 'other@example.test', 'code_service' => 'TOUR2'],
        ]);
        DB::table('reservation_room_detail')->insert([
            ['no_reservasi' => 'BOOK-01'], ['no_reservasi' => 'BOOK-01'], ['no_reservasi' => 'BOOK-02'],
        ]);

        $this->delete(route('pages.orders.delete', 1))->assertRedirect(route('login'));
        $this->actingAs($this->user('user'))->delete(route('pages.orders.delete', 1))->assertForbidden();
        $this->assertDatabaseHas('reservations', ['id' => 1]);
        $this->actingAs($this->user('admin'))->delete(route('pages.orders.delete', 1))
            ->assertRedirect(route('pages.orders'))->assertSessionHas('order_message');
        $this->assertDatabaseMissing('reservations', ['id' => 1]);
        $this->assertDatabaseMissing('reservation_room_detail', ['no_reservasi' => 'BOOK-01']);
        $this->assertDatabaseHas('reservations', ['id' => 2]);
        $this->assertDatabaseHas('reservation_room_detail', ['no_reservasi' => 'BOOK-02']);
        $this->delete(route('pages.orders.delete', 1))->assertNotFound();
    }

    public function test_delete_rejects_a_shared_booking_number(): void
    {
        DB::table('reservations')->insert([
            ['no_reservasi' => 'BOOK-01', 'guest_name' => 'First', 'guest_email' => 'first@example.test', 'code_service' => 'TOUR1'],
            ['no_reservasi' => 'BOOK-01', 'guest_name' => 'Second', 'guest_email' => 'second@example.test', 'code_service' => 'TOUR2'],
        ]);
        DB::table('reservation_room_detail')->insert(['no_reservasi' => 'BOOK-01']);

        $this->actingAs($this->user('admin'))->delete(route('pages.orders.delete', 1))->assertStatus(409);
        $this->assertDatabaseCount('reservations', 2);
        $this->assertDatabaseCount('reservation_room_detail', 1);
    }
}
