<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomerPasswordSetup;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class CustomerBookingTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('has_local_password')->default(true);
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('no_reservasi')->unique();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('code_service');
            $table->string('cek_in')->nullable();
            $table->string('adult')->nullable();
            $table->string('book_status')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total', 13, 2)->nullable();
            $table->decimal('guest_paid', 10, 2)->nullable();
            $table->string('checkout_token')->nullable();
        });
        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('tour_name');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('product_name');
        });
        DB::table('reservations')->insert([
            ['no_reservasi' => 'BOOK-ONE', 'guest_name' => 'First Guest', 'guest_email' => 'guest@example.test',
                'code_service' => 'TOUR1', 'status' => 'PENDING', 'book_status' => 'New', 'total' => 100, 'guest_paid' => 0],
            ['no_reservasi' => 'BOOK-TWO', 'guest_name' => 'Other Guest', 'guest_email' => 'other@example.test',
                'code_service' => 'PRODUCT1', 'status' => 'PAID', 'book_status' => 'Confirmed', 'total' => 50, 'guest_paid' => 50],
        ]);
        DB::table('tour_packages')->insert(['code' => 'TOUR1', 'tour_name' => 'Bali Tour']);
        DB::table('products')->insert(['code' => 'PRODUCT1', 'product_name' => 'Other Product']);

        $this->server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/track_booking';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    public function test_tracking_requires_a_matching_booking_number_and_email(): void
    {
        $this->get(route('tracking.form'))->assertOk()->assertSee('Track Your Booking')
            ->assertHeader('Cache-Control', 'no-store, private')->assertDontSee('googletagmanager', false);
        $this->from(route('tracking.form'))->post(route('tracking.lookup'), [
            'booking' => 'BOOK-ONE', 'email' => 'other@example.test',
        ])->assertRedirect(route('tracking.form'))->assertSessionHasErrors('booking');
        $this->post(route('tracking.lookup'), ['booking' => 'BOOK-ONE', 'email' => 'GUEST@example.test'])
            ->assertOk()->assertSee('Bali Tour')->assertSee('BOOK-ONE')->assertSee('PENDING')
            ->assertDontSee('BOOK-TWO')->assertDontSee('First Guest')
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_tracking_attempts_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('tracking.lookup'), ['booking' => 'UNKNOWN', 'email' => 'guest@example.test'])
                ->assertRedirect();
        }
        $this->post(route('tracking.lookup'), ['booking' => 'UNKNOWN', 'email' => 'guest@example.test'])
            ->assertTooManyRequests();
    }

    public function test_dashboard_requires_verified_email_and_only_shows_matching_bookings(): void
    {
        $this->get(route('customer.dashboard'))->assertRedirect(route('customer.login'));
        DB::table('reservations')->insert([
            'no_reservasi' => 'BOOK-THREE', 'guest_name' => 'First Guest', 'guest_email' => 'GUEST@example.test',
            'code_service' => 'TOUR1', 'status' => 'PAID', 'book_status' => 'Confirmed', 'total' => 80, 'guest_paid' => 80,
        ]);
        $user = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => Hash::make('secret123')]);
        $this->actingAs($user)->get(route('customer.dashboard'))->assertRedirect(route('customer.verify.notice'));
        $user->email_verified_at = now();
        $user->save();
        $this->get(route('customer.dashboard'))->assertOk()->assertSee('BOOK-ONE')->assertSee('BOOK-THREE')->assertSee('Bali Tour')
            ->assertDontSee('BOOK-TWO')->assertDontSee('Other Product')->assertDontSee('googletagmanager', false)
            ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_verified_email_setup_and_login_use_a_one_time_link(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $this->post(route('customer.password.request'), ['email' => 'guest@example.test'])
            ->assertRedirect(route('customer.login'))->assertSessionHas('customer_message');
        $user = User::where('email', 'guest@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->has_local_password);
        $token = null;
        Notification::assertSentTo($user, CustomerPasswordSetup::class, function ($notification, $channels) use ($user, &$token) {
            $url = $notification->toMail($user)->actionUrl;
            $token = basename(parse_url($url, PHP_URL_PATH));
            return in_array('mail', $channels, true);
        });
        $this->assertNotNull($token);
        $this->get(route('customer.password.form', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertDontSee('googletagmanager', false)->assertHeader('Referrer-Policy', 'no-referrer');
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'password123', 'password_confirmation' => 'password123'];
        $this->post(route('customer.password.setup'), $data)->assertRedirect(route('customer.login'));
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->has_local_password);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->post(route('customer.password.setup'), $data)->assertSessionHasErrors('email');
        $this->post(route('customer.login.submit'), ['email' => 'GUEST@example.test', 'password' => 'password123'])
            ->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.dashboard'))->assertOk()->assertSee('BOOK-ONE')->assertDontSee('BOOK-TWO');
    }

    public function test_expired_password_setup_link_is_rejected_and_unverified_login_requires_verification(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $this->post(route('customer.password.request'), ['email' => 'guest@example.test']);
        $user = User::where('email', 'guest@example.test')->firstOrFail();
        $user->password = Hash::make('password123');
        $user->has_local_password = true;
        $user->save();
        $this->post(route('customer.login.submit'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('customer.dashboard'));
        $this->get(route('customer.dashboard'))->assertRedirect(route('customer.verify.notice'));
        $this->assertAuthenticatedAs($user);
        $token = null;
        Notification::assertSentTo($user, CustomerPasswordSetup::class, function ($notification) use ($user, &$token) {
            $token = basename(parse_url($notification->toMail($user)->actionUrl, PHP_URL_PATH));
            return true;
        });
        $this->travel(61)->minutes();
        $this->post(route('customer.password.setup'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('email');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_signed_in_unverified_user_can_verify_their_email(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = User::create(['name' => 'Admin', 'email' => 'guest@example.test', 'password' => Hash::make('password123')]);
        $user->role = 'admin';
        $user->save();
        $this->actingAs($user)->get(route('customer.verify.notice'))->assertOk()
            ->assertSee('Send verification email')->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertDontSee('googletagmanager', false);
        $this->post(route('customer.verify.send'))->assertRedirect(route('customer.verify.notice'))
            ->assertSessionHas('customer_message');
        $link = null;
        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user, &$link) {
            $link = $notification->toMail($user)->actionUrl;
            return true;
        });
        $this->assertNotNull($link);

        auth()->logout();
        $this->get($link)->assertRedirect(route('customer.login'))->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post(route('customer.login.submit'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect($link);

        $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => Hash::make('password123')]);
        $this->actingAs($other)->get($link)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->actingAs($user)->get($link.'&extra=1')->assertForbidden();
        $this->get($link)->assertRedirect(route('customer.dashboard'))
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('customer.dashboard'))->assertOk()->assertSee('BOOK-ONE')->assertDontSee('BOOK-TWO');
    }

    public function test_verification_requires_a_valid_unexpired_link_and_working_email(): void
    {
        config(['mail.default' => 'log']);
        Notification::fake();
        $user = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => Hash::make('password123')]);
        $this->actingAs($user)->get(route('customer.verify.notice'))->assertOk()
            ->assertSee('email delivery is not configured');
        $this->post(route('customer.verify.send'))->assertRedirect(route('customer.verify.notice'))
            ->assertSessionHasErrors('email');
        Notification::assertNothingSent();
        $this->post(route('guest.order'))->assertForbidden();

        config(['mail.default' => 'smtp']);
        $this->post(route('customer.verify.send'))->assertRedirect(route('customer.verify.notice'));
        $link = null;
        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user, &$link) {
            $link = $notification->toMail($user)->actionUrl;
            return true;
        });
        $this->travel(61)->minutes();
        $this->get($link)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_password_setup_does_not_send_without_a_booking_or_real_mail_transport(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);
        $this->get(route('customer.login'))->assertOk()->assertSee('Password setup by email is temporarily unavailable');
        $this->post(route('customer.password.request'), ['email' => 'guest@example.test'])
            ->assertRedirect(route('customer.login'))->assertSessionHasErrors('email');
        $this->from(route('password.request'))->post(route('password.email'), ['email' => 'guest@example.test'])
            ->assertRedirect(route('password.request'))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
        config(['mail.default' => 'smtp']);
        $this->post(route('customer.password.request'), ['email' => 'unknown@example.test'])
            ->assertRedirect(route('customer.login'))->assertSessionHas('customer_message');
        $this->assertDatabaseCount('users', 0);
        Notification::assertNothingSent();
    }

    public function test_password_changes_require_current_password_for_existing_accounts(): void
    {
        $user = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => Hash::make('password123')]);
        $user->email_verified_at = now();
        $user->save();
        $this->actingAs($user);
        $data = ['current_password' => 'wrong', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123'];
        $this->put(route('customer.password.change'), $data)->assertSessionHasErrors('current_password');
        $this->put(route('customer.password.change'), array_merge($data, ['current_password' => 'password123']))
            ->assertRedirect(route('customer.dashboard'));
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_customer_cannot_access_admin_or_another_guests_legacy_orders(): void
    {
        $this->post(route('guest.order'), ['email' => 'other@example.test'])
            ->assertRedirect(route('customer.login'));
        $user = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => Hash::make('password123')]);
        $user->email_verified_at = now();
        $user->save();
        $this->actingAs($user);
        $this->get(route('pages.products_add'))->assertForbidden();
        $this->post(route('products.store'), [])->assertForbidden();
        $this->get(route('pages.orders'))->assertForbidden();
        $this->get(route('user.index'))->assertForbidden();
        $this->get(route('register'))->assertForbidden();
        $this->post(route('guest.order'), ['email' => 'other@example.test'])
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.no_reservasi', 'BOOK-ONE');
    }

    public function test_customer_login_ignores_a_stored_admin_destination(): void
    {
        $user = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => Hash::make('password123')]);
        $user->email_verified_at = now();
        $user->save();

        $this->get(route('pages.products', 'all'))->assertRedirect(route('login'));
        $this->post(route('customer.login.submit'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionMissing('url.intended');
        $this->get(route('customer.dashboard'))->assertOk()->assertSee('BOOK-ONE');
        $this->get(route('pages.products', 'all'))->assertForbidden();
    }

    public function test_customer_login_preserves_a_signed_email_verification_destination(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => Hash::make('password123')]);
        $user->sendEmailVerificationNotification();
        $link = null;
        Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user, &$link) {
            $link = $notification->toMail($user)->actionUrl;
            return true;
        });

        $this->get($link)->assertRedirect(route('customer.login'));
        $this->post(route('customer.login.submit'), ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect($link);
        $this->get($link)->assertRedirect(route('customer.dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_google_login_cannot_take_over_an_administrator_account(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => Hash::make('password123')]);
        $user->role = 'admin';
        $user->save();
        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->once()->andReturn((new SocialiteUser())
            ->setRaw(['email_verified' => true])->map(['email' => $user->email, 'name' => 'Admin']));
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->get(route('auth.google.callback'))->assertRedirect(route('customer.login'))->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_customer_password_setup_cannot_reset_an_administrator_account(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = User::create(['name' => 'Admin', 'email' => 'guest@example.test', 'password' => Hash::make('password123')]);
        $user->role = 'admin';
        $user->save();
        $this->post(route('customer.password.request'), ['email' => $user->email])
            ->assertRedirect(route('customer.login'))->assertSessionHas('customer_message');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_google_login_requires_verified_email_and_allows_initial_password_setup(): void
    {
        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $googleUser = (new SocialiteUser())->setRaw(['email_verified' => false])->map([
            'email' => 'guest@example.test', 'name' => 'Guest',
        ]);
        $provider->shouldReceive('user')->twice()->andReturn($googleUser, (new SocialiteUser())
            ->setRaw(['email_verified' => true])->map(['email' => 'guest@example.test', 'name' => 'Guest']));
        Socialite::shouldReceive('driver')->with('google')->twice()->andReturn($provider);
        $this->get(route('auth.google.callback'))->assertRedirect(route('customer.login'))->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->get(route('auth.google.callback'))->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertFalse($user->has_local_password);
        $this->get(route('customer.dashboard'))->assertOk()->assertSee('BOOK-ONE')->assertDontSee('BOOK-TWO')
            ->assertSee('Set a password');
        $this->put(route('customer.password.change'), ['password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect(route('customer.dashboard'));
        $this->assertTrue($user->fresh()->has_local_password);
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }
}
