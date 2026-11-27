<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (['activities', 'destinations', 'tour_packages', 'bookings'] as $table) {
                $rows = DB::table($table)->get(['id', 'code', 'lang', 'slug']);
                $slugs = [];
                foreach ($rows as $row) {
                    $slugs[$row->id] = str_ireplace(
                        ['bytheswand', 'the-swand', 'theswand', 'heswand'],
                        ['by-bali-scenic', 'bali-scenic', 'bali-scenic', 'bali-scenic'],
                        $row->slug ?? ''
                    );
                }

                foreach ($rows as $row) {
                    $slug = $slugs[$row->id];
                    if ($row->slug === null || $slug === $row->slug) {
                        continue;
                    }
                    foreach ($rows as $other) {
                        if ($other->id !== $row->id
                            && strcasecmp($slugs[$other->id], $slug) === 0
                            && (strcasecmp($other->lang ?? '', $row->lang ?? '') === 0 || $other->code !== $row->code)) {
                            throw new RuntimeException("Cannot update {$table} slug: the replacement is ambiguous.");
                        }
                    }
                    DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
                }
            }

            DB::table('bookings')->where('desc', 'like', '%the swand%')->orderBy('id')
                ->eachById(function ($row) {
                    DB::table('bookings')->where('id', $row->id)
                        ->update(['desc' => str_ireplace('the swand', 'Bali Scenic', $row->desc)]);
                });
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This branding migration cannot be reversed automatically. Restore a database backup if needed.');
    }
};
