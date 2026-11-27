<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActivityFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('travel_area', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('slug');
            $table->string('lang');
            $table->string('type');
            $table->string('area');
            $table->text('deskripsi');
            $table->timestamps();
        });
        Schema::create('activity_fotos', function (Blueprint $table) {
            $table->string('code')->unique();
            $table->text('foto');
            $table->timestamps();
        });
        DB::table('travel_area')->insert([
            ['id' => 1, 'name' => 'ubud'],
            ['id' => 5, 'name' => 'kintamani'],
        ]);
        $user = new User();
        $user->id = 1;
        $user->role = 'admin';
        $user->name = 'Admin';
        $this->actingAs($user);
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'code' => '', 'name' => 'Rafting', 'slug' => 'rafting',
            'lang' => 'en', 'type' => 'water', 'area' => ['1', '5'],
            'deskripsi' => 'A river activity.', 'documents_present' => '1',
            'document' => ['photo.webp'],
        ], $overrides);
    }

    public function test_create_form_shows_supported_selects_and_saves_multiple_areas(): void
    {
        $this->get(route('pages.activity_add'))->assertOk()
            ->assertSee('name="area[]"', false)->assertSee('Land Activity')
            ->assertSee('Water Activity')->assertSee('English')->assertSee('Kintamani')
            ->assertSee('Generate a new code automatically');
        $this->post(route('activity.store'), $this->data())
            ->assertRedirect(route('pages.activity', 'all'))->assertSessionHasNoErrors();
        $code = DB::table('activities')->value('code');
        $this->assertMatchesRegularExpression('/^ACT-[A-Z0-9]{16}$/', $code);
        $this->assertDatabaseHas('activities', ['code' => $code, 'lang' => 'en', 'type' => 'water', 'area' => '1;5;']);
        $this->assertDatabaseHas('activity_fotos', ['code' => $code, 'foto' => 'photo.webp;']);
    }

    public function test_edit_keeps_selected_values_and_can_edit_an_activity_without_a_photo_row(): void
    {
        $id = DB::table('activities')->insertGetId([
            'code' => 'ACT1', 'name' => 'Rafting', 'slug' => 'rafting', 'lang' => 'id',
            'type' => 'water', 'area' => '1;5;', 'deskripsi' => '',
        ]);
        $this->get(route('activity.edit', $id))->assertOk()
            ->assertSee('value="id" selected', false)
            ->assertSee('value="water" selected', false)
            ->assertSee('value="1" checked', false)
            ->assertSee('value="5" checked', false);
        $this->post(route('activity.store'), $this->data(['id' => $id, 'code' => 'ACT1', 'lang' => 'id', 'area' => ['5'], 'document' => []]))
            ->assertRedirect(route('pages.activity', 'all'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activities', ['id' => $id, 'area' => '5;', 'lang' => 'id']);
    }

    public function test_new_translation_without_uploads_keeps_the_shared_gallery(): void
    {
        DB::table('activities')->insert([
            'code' => 'ACT1', 'name' => 'Rafting', 'slug' => 'rafting', 'lang' => 'en',
            'type' => 'water', 'area' => '1;', 'deskripsi' => '',
        ]);
        DB::table('activity_fotos')->insert(['code' => 'ACT1', 'foto' => 'original.webp;']);
        $this->get(route('pages.activity_add'))->assertOk()->assertSee('Rafting — ACT1 (en)');
        $this->post(route('activity.store'), $this->data(['code' => 'ACT1', 'lang' => 'id', 'document' => []]))
            ->assertRedirect(route('pages.activity', 'all'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_fotos', ['code' => 'ACT1', 'foto' => 'original.webp;']);
        $this->assertDatabaseHas('activities', ['code' => 'ACT1', 'lang' => 'id']);
    }

    public function test_existing_code_rejects_duplicate_language_and_unknown_codes(): void
    {
        DB::table('activities')->insert([
            'code' => 'ACT1', 'name' => 'Rafting', 'slug' => 'rafting', 'lang' => 'en',
            'type' => 'water', 'area' => '1;', 'deskripsi' => '',
        ]);
        $this->from(route('pages.activity_add'))->post(route('activity.store'), $this->data(['code' => 'ACT1']))
            ->assertSessionHasErrors('code');
        $this->from(route('pages.activity_add'))->post(route('activity.store'), $this->data(['code' => 'UNKNOWN']))
            ->assertSessionHasErrors('code');
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_generated_code_retries_an_existing_photo_code(): void
    {
        DB::table('activity_fotos')->insert(['code' => 'ACT-XXXXXXXXXXXXXXXX', 'foto' => 'old.webp;']);
        $generated = 0;
        Str::createRandomStringsUsing(function () use (&$generated) {
            return ++$generated === 1 ? str_repeat('X', 16) : str_repeat('Y', 16);
        });
        try {
            $this->post(route('activity.store'), $this->data())
                ->assertRedirect(route('pages.activity', 'all'))->assertSessionHasNoErrors();
        } finally {
            Str::createRandomStringsNormally();
        }
        $this->assertDatabaseHas('activities', ['code' => 'ACT-YYYYYYYYYYYYYYYY']);
        $this->assertDatabaseHas('activity_fotos', ['code' => 'ACT-XXXXXXXXXXXXXXXX', 'foto' => 'old.webp;']);
    }

    public function test_failed_code_generation_shows_an_error_without_saving(): void
    {
        DB::table('activity_fotos')->insert(['code' => 'ACT-XXXXXXXXXXXXXXXX', 'foto' => 'old.webp;']);
        Str::createRandomStringsUsing(fn () => str_repeat('X', 16));
        try {
            $this->from(route('pages.activity_add'))->post(route('activity.store'), $this->data())
                ->assertRedirect(route('pages.activity_add'))->assertSessionHasErrors('code');
        } finally {
            Str::createRandomStringsNormally();
        }
        $this->assertDatabaseCount('activities', 0);
        $this->assertDatabaseHas('activity_fotos', ['code' => 'ACT-XXXXXXXXXXXXXXXX', 'foto' => 'old.webp;']);
    }

    public function test_invalid_options_show_errors_and_keep_entered_values_and_uploaded_photos(): void
    {
        $this->from(route('pages.activity_add'))->post(route('activity.store'), $this->data([
            'type' => 'invalid', 'area' => ['999'], 'lang' => 'fr',
        ]))->assertRedirect(route('pages.activity_add'))->assertSessionHasErrors(['type', 'area.0', 'lang']);
        $this->get(route('pages.activity_add'))->assertOk()
            ->assertSee('Rafting')->assertSee('photo.webp')->assertSee('The selected type is invalid.');
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_existing_legacy_values_remain_editable(): void
    {
        $id = DB::table('activities')->insertGetId([
            'code' => 'ACT1', 'name' => 'Rafting', 'slug' => 'rafting', 'lang' => 'legacy',
            'type' => 'other', 'area' => '99;', 'deskripsi' => '',
        ]);
        $this->get(route('activity.edit', $id))->assertOk()
            ->assertSee('other (existing)')->assertSee('99 (existing)')->assertSee('legacy (existing)');
        $this->post(route('activity.store'), $this->data([
            'id' => $id, 'code' => 'ACT1', 'lang' => 'legacy', 'type' => 'other', 'area' => ['99'],
        ]))->assertRedirect(route('pages.activity', 'all'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activities', ['id' => $id, 'lang' => 'legacy', 'type' => 'other', 'area' => '99;']);
    }
}
