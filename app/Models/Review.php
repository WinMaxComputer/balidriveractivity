<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Review extends Model
{
    use HasFactory;
    protected $table = 'review_ratings';

    protected $fillable = [
        'booking_id', 'guest_name', 'guest_email' ,'comments', 'product_code' ,'star_rating', 'status'
    ];

    public static function ratingSummaries(iterable $codes): Collection
    {
        $codes = collect($codes)->filter(fn ($code) => is_string($code) && $code !== '')->unique()->values();

        return static::query()
            ->whereIn('product_code', $codes)
            ->where('status', 'active')
            ->whereIn('star_rating', ['1', '2', '3', '4', '5'])
            ->selectRaw('product_code, AVG(star_rating) as average_rating, COUNT(*) as review_count')
            ->groupBy('product_code')
            ->get()
            ->keyBy('product_code');
    }
}
