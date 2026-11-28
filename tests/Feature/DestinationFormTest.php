<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DestinationFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('slug');
            $table->string('lang');
            $table->string('type');
            $table->text('deskripsi')->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_keyword', 500)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->timestamps();
            $table->unique(['code', 'lang']);
            $table->unique(['slug', 'lang']);
        });
        Schema::create('destination_fotos', function (Blueprint $table) {
            $table->string('code')->unique();
            $table->text('foto');
            $table->timestamps();
        });

        $admin = new User();
        $admin->id = 1;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    public function test_destination_form_supports_translation_codes_and_seo_metadata(): void
    {
        $this->get(route('pages.destinasi_add'))->assertOk()
            ->assertSee('SEO settings')->assertSee('Generate a new code automatically');

        $this->post(route('destinasi.store'), [
            'name' => 'Bali Beach', 'slug' => '', 'lang' => 'en', 'type' => 'south',
            'deskripsi' => '<p>Visit a beautiful Bali beach.</p>',
            'meta_title' => 'Best Bali Beaches', 'meta_keyword' => 'Bali beach, Bali travel',
            'meta_description' => 'Discover beautiful beaches in Bali.',
            'document' => [],
        ])->assertRedirect(route('pages.destinasi'))->assertSessionHasNoErrors();

        $english = DB::table('destinations')->where('lang', 'en')->first();
        $this->assertSame('bali-beach', $english->slug);
        $this->assertStringStartsWith('DST-', $english->code);
        $this->assertDatabaseHas('destinations', [
            'code' => $english->code, 'meta_title' => 'Best Bali Beaches',
            'meta_description' => 'Discover beautiful beaches in Bali.',
        ]);

        DB::table('destination_fotos')->where('code', $english->code)->update(['foto' => 'beach.webp;']);
        $this->post(route('destinasi.store'), [
            'code' => $english->code, 'name' => 'Pantai Bali', 'slug' => 'Pantai Bali',
            'lang' => 'id', 'type' => 'south', 'deskripsi' => '<p>Kunjungi pantai Bali.</p>',
            'document' => [],
        ])->assertRedirect(route('pages.destinasi'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('destinations', ['code' => $english->code, 'lang' => 'id']);
        $this->assertDatabaseHas('destination_fotos', ['code' => $english->code, 'foto' => 'beach.webp;']);
    }

    public function test_destination_detail_outputs_saved_seo_fields_and_translation_links(): void
    {
        DB::table('destinations')->insert([
            'code' => 'DST1', 'name' => 'Bali Beach', 'slug' => 'bali-beach', 'lang' => 'en',
            'type' => 'south', 'deskripsi' => '<p>Sunny beach destination.</p>',
            'meta_title' => 'Best Bali Beaches', 'meta_keyword' => 'Bali beach',
            'meta_description' => 'Find the best beaches in Bali.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('destinations')->insert([
            'code' => 'DST1', 'name' => 'Pantai Bali', 'slug' => 'pantai-bali', 'lang' => 'id',
            'type' => 'south', 'deskripsi' => '<p>Pantai indah di Bali.</p>',
            'meta_title' => 'Pantai Terbaik Bali', 'meta_keyword' => 'pantai Bali',
            'meta_description' => 'Temukan pantai terbaik di Bali.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('destination_fotos')->insert([
            'code' => 'DST1', 'foto' => 'beach.webp;', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('destinations.detail', ['locale' => 'en', 'slug' => 'bali-beach']))
            ->assertOk()->assertSee('<title>Best Bali Beaches</title>', false)
            ->assertSee('name="description" content="Find the best beaches in Bali."', false)
            ->assertSee('hreflang="id"')->assertSee('/id/destinations/pantai-bali');
    }

    public function test_destination_rejects_duplicate_translation_language(): void
    {
        DB::table('destinations')->insert([
            'code' => 'DST1', 'name' => 'Bali Beach', 'slug' => 'bali-beach', 'lang' => 'en',
            'type' => 'south', 'deskripsi' => '', 'meta_title' => '', 'meta_keyword' => '',
            'meta_description' => '', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->from(route('pages.destinasi_add'))->post(route('destinasi.store'), [
            'code' => 'DST1', 'name' => 'Duplicate', 'slug' => 'duplicate', 'lang' => 'en',
            'type' => 'south', 'document' => [],
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseCount('destinations', 1);
    }
}
