<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TravelGuideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('travel_guides', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100);
            $table->string('title');
            $table->string('slug');
            $table->string('lang', 2);
            $table->longText('content');
            $table->longText('photos')->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->timestamps();
            $table->unique(['code', 'lang']);
            $table->unique(['slug', 'lang']);
        });

        $admin = new User();
        $admin->id = 1;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    public function test_admin_can_create_two_language_articles_under_one_code_and_public_pages_use_seo_fields(): void
    {
        $this->get(route('pages.travel-guide_add'))->assertOk()
            ->assertSee('SEO settings')->assertSee('Search result preview');

        $this->post(route('travel-guide.store'), [
            'title' => 'Bali Travel Tips', 'slug' => 'Bali Travel Tips', 'lang' => 'en',
            'content' => '<p>Plan your Bali trip.</p><p><a href="https://example.com">More tips</a></p>',
            'meta_title' => 'Best Bali Travel Tips', 'meta_description' => 'Helpful advice for planning a trip to Bali.',
            'meta_keywords' => 'Bali travel, Bali tips',
            'photos' => [],
        ])->assertRedirect(route('pages.travel-guide'))->assertSessionHasNoErrors();

        $english = DB::table('travel_guides')->where('lang', 'en')->first();
        $this->assertSame('bali-travel-tips', $english->slug);
        $this->assertStringStartsWith('GUIDE-', $english->code);

        $this->post(route('travel-guide.store'), [
            'code' => $english->code, 'title' => 'Tips Wisata Bali', 'slug' => 'Tips Wisata Bali', 'lang' => 'id',
            'content' => '<p>Rencanakan perjalanan ke Bali.</p>', 'meta_title' => 'Tips Wisata Bali',
            'meta_description' => 'Saran bermanfaat untuk merencanakan perjalanan ke Bali.',
        ])->assertRedirect(route('pages.travel-guide'))->assertSessionHasNoErrors();

        $this->get(route('travel_guides.detail', ['locale' => 'en', 'slug' => $english->slug]))
            ->assertOk()->assertSee('<title>Best Bali Travel Tips</title>', false)
            ->assertSee('name="description" content="Helpful advice for planning a trip to Bali."', false)
            ->assertSee('href="https://example.com"', false)
            ->assertSee('hreflang="id"');
        $this->assertDatabaseHas('travel_guides', [
            'code' => $english->code, 'lang' => 'id', 'slug' => 'tips-wisata-bali',
        ]);
    }

    public function test_admin_rejects_duplicate_translation_language_and_slug(): void
    {
        DB::table('travel_guides')->insert([
            'code' => 'GUIDE1', 'title' => 'Bali Tips', 'slug' => 'bali-tips', 'lang' => 'en',
            'content' => '<p>Tips</p>', 'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->from(route('pages.travel-guide_add'))->post(route('travel-guide.store'), [
            'code' => 'GUIDE1', 'title' => 'Duplicate', 'slug' => 'another-slug', 'lang' => 'en',
            'content' => '<p>Duplicate</p>',
        ])->assertSessionHasErrors('code');

        $this->from(route('pages.travel-guide_add'))->post(route('travel-guide.store'), [
            'code' => '', 'title' => 'Duplicate Slug', 'slug' => 'bali-tips', 'lang' => 'en',
            'content' => '<p>Duplicate slug</p>',
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('travel_guides', 1);
    }
}
