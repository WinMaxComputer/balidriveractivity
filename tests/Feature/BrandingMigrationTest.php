<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BrandingMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        foreach (['activities', 'destinations', 'tour_packages', 'bookings'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('lang');
                $table->string('slug')->nullable();
                $table->text('desc')->nullable();
            });
        }
    }

    public function test_content_is_rebranded_without_removing_records(): void
    {
        DB::table('activities')->insert(['code' => 'A', 'lang' => 'en', 'slug' => 'rafting-by-theswand']);
        DB::table('destinations')->insert(['code' => 'D', 'lang' => 'id', 'slug' => 'bali-by-the-swand']);
        DB::table('tour_packages')->insert(['code' => 'T', 'lang' => 'id', 'slug' => 'tour-heswand']);
        DB::table('bookings')->insert(['code' => 'B', 'lang' => 'en', 'slug' => 'room-bytheswand', 'desc' => 'Managed by the swand.']);

        $migration = require database_path('migrations/2026_11_27_000001_replace_old_brand_in_content.php');
        $migration->up();

        $this->assertSame('rafting-by-bali-scenic', DB::table('activities')->value('slug'));
        $this->assertSame('bali-by-bali-scenic', DB::table('destinations')->value('slug'));
        $this->assertSame('tour-bali-scenic', DB::table('tour_packages')->value('slug'));
        $this->assertSame('room-by-bali-scenic', DB::table('bookings')->value('slug'));
        $this->assertSame('Managed by Bali Scenic.', DB::table('bookings')->value('desc'));
    }

    public function test_slug_collision_rolls_back_all_changes(): void
    {
        DB::table('activities')->insert(['code' => 'A', 'lang' => 'en', 'slug' => 'rafting-by-theswand']);
        DB::table('destinations')->insert([
            ['code' => 'D1', 'lang' => 'en', 'slug' => 'place-theswand'],
            ['code' => 'D2', 'lang' => 'en', 'slug' => 'place-bali-scenic'],
        ]);
        $migration = require database_path('migrations/2026_11_27_000001_replace_old_brand_in_content.php');

        try {
            $migration->up();
            $this->fail('A conflicting slug must stop the migration.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('ambiguous', $exception->getMessage());
        }
        $this->assertSame('rafting-by-theswand', DB::table('activities')->value('slug'));
        $this->assertSame('place-theswand', DB::table('destinations')->where('code', 'D1')->value('slug'));
    }
}
