<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferOrderTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_11_26_000004_create_transfer_orders_table.php'))->up();
        (require database_path('migrations/2026_11_26_000005_expand_transfer_destination.php'))->up();
        $this->server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/transfer';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    private function formData(): array
    {
        $response = $this->get(route('transfer'))->assertOk()
            ->assertSee('action="'.route('transfer.store').'"', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        return [
            'token' => $matches[1],
            'name' => '<Guest>',
            'email' => 'guest@example.test',
            'mobile' => '+6200000',
            'booking_type' => 'arrival',
            'destination' => 'Ubud hotel',
            'pickup_date' => now()->addDay()->toDateString(),
            'flight_number' => 'GA410',
            'arrival_time' => '09:30',
            'passengers' => 3,
        ];
    }

    private function user(string $role): User
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'Test User';
        $user->role = $role;

        return $user;
    }

    public function test_transfer_request_is_saved_once_and_receipt_does_not_claim_confirmation(): void
    {
        $data = $this->formData();
        $this->post(route('transfer.store'), $data)->assertRedirect(route('transfer.confirmation'));
        $this->assertDatabaseHas('transfer_orders', [
            'name' => '<Guest>', 'email' => $data['email'], 'mobile' => $data['mobile'],
            'booking_type' => 'arrival', 'destination' => $data['destination'],
            'pickup_date' => $data['pickup_date'], 'flight_number' => 'GA410',
            'arrival_time' => '09:30', 'passengers' => 3,
        ]);
        $this->get(route('transfer.confirmation'))->assertOk()
            ->assertSee('awaiting confirmation')->assertDontSee('guest@example.test')
            ->assertDontSee('googletagmanager', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->post(route('transfer.store'), $data)->assertRedirect(route('transfer.confirmation'));
        $this->assertDatabaseCount('transfer_orders', 1);
    }

    public function test_validation_keeps_the_input_and_rejects_unissued_tokens(): void
    {
        $data = $this->formData();
        $this->from(route('transfer'))->post(route('transfer.store'), array_merge($data, [
            'destination' => '', 'pickup_date' => now()->subDay()->toDateString(),
            'booking_type' => 'other', 'arrival_time' => '25:99', 'passengers' => 0,
        ]))->assertRedirect(route('transfer'))
            ->assertSessionHasErrors(['destination', 'pickup_date', 'booking_type', 'arrival_time', 'passengers'])
            ->assertSessionHasInput('name', '<Guest>');
        $this->post(route('transfer.store'), array_merge($data, ['token' => str_repeat('a', 64)]))
            ->assertForbidden();
        $this->assertDatabaseCount('transfer_orders', 0);
    }

    public function test_departure_can_be_submitted_without_flight_details(): void
    {
        $data = array_merge($this->formData(), [
            'booking_type' => 'departure', 'flight_number' => '', 'arrival_time' => '',
        ]);
        $this->post(route('transfer.store'), $data)->assertRedirect(route('transfer.confirmation'));
        $this->assertDatabaseHas('transfer_orders', [
            'booking_type' => 'departure', 'flight_number' => null, 'arrival_time' => null,
        ]);
    }

    public function test_full_destination_is_saved_and_shown_to_admin(): void
    {
        $destination = 'Ubud Hotel, '.str_repeat('Long Street, ', 22).'Bali, Indonesia';
        $data = array_merge($this->formData(), ['destination' => $destination]);
        $this->post(route('transfer.store'), $data)->assertRedirect(route('transfer.confirmation'));
        $this->assertDatabaseHas('transfer_orders', ['destination' => $destination]);
        $this->actingAs($this->user('admin'))->get(route('pages.transfer-order'))
            ->assertOk()->assertSee($destination);
    }

    public function test_destination_over_limit_is_rejected(): void
    {
        $data = array_merge($this->formData(), ['destination' => str_repeat('a', 2001)]);
        $this->post(route('transfer.store'), $data)->assertSessionHasErrors('destination');
        $this->assertDatabaseCount('transfer_orders', 0);
    }

    public function test_only_admins_can_view_paginated_transfer_orders(): void
    {
        $this->get(route('transfer.confirmation'))->assertNotFound();
        $data = $this->formData();
        $this->post(route('transfer.store'), $data)->assertRedirect();
        for ($i = 0; $i < 20; $i++) {
            $order = $data;
            unset($order['token']);
            DB::table('transfer_orders')->insert(array_merge($order, [
                'request_token' => hash('sha256', (string) $i), 'name' => 'Later Guest',
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        $this->get(route('pages.transfer-order'))->assertRedirect(route('login'));
        $this->actingAs($this->user('user'))->get(route('pages.transfer-order'))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('pages.transfer-order'))
            ->assertOk()->assertSee('Later Guest')->assertDontSee('&lt;Guest&gt;', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('pages.transfer-order', ['page' => 2]))->assertOk()
            ->assertSee('&lt;Guest&gt;', false)->assertSee('guest@example.test')->assertSee('Ubud hotel')
            ->assertSee('GA410')->assertSee('09:30')->assertSee('Arrival');
    }
}
