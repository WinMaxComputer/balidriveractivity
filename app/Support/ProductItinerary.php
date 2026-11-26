<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class ProductItinerary
{
    public static function parse(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded) && ($decoded['format'] ?? null) === 'product_itinerary_v1') {
            if (!isset($decoded['items']) || !is_array($decoded['items'])) {
                throw new UnexpectedValueException('Invalid product itinerary.');
            }
            foreach ($decoded['items'] as $item) {
                if (!is_array($item) || !isset($item['reference'], $item['note'], $item['legacy_html'])
                    || !is_string($item['reference']) || !is_string($item['note']) || !is_bool($item['legacy_html'])
                    || ($item['reference'] !== '' && !preg_match('/^(activity|destination):[1-9][0-9]*$/', $item['reference']))) {
                    throw new UnexpectedValueException('Invalid product itinerary item.');
                }
            }
            return $decoded['items'];
        }

        return collect(explode(';', $value))
            ->filter(fn ($note) => trim($note) !== '')
            ->map(fn ($note) => ['reference' => '', 'note' => $note, 'legacy_html' => true])
            ->values()->all();
    }

    public static function resolve(?string $value): array
    {
        $items = self::parse($value);
        $ids = ['activity' => [], 'destination' => []];
        foreach ($items as $item) {
            if ($item['reference'] !== '') {
                [$type, $id] = explode(':', $item['reference'], 2);
                $ids[$type][] = (int) $id;
            }
        }

        $entities = [];
        foreach (['activity' => 'activities', 'destination' => 'destinations'] as $type => $table) {
            $entities[$type] = DB::table($table)->whereIn('id', $ids[$type])->get()->keyBy('id');
        }
        $photos = [];
        foreach (['activity' => 'activity_fotos', 'destination' => 'destination_fotos'] as $type => $table) {
            $codes = $entities[$type]->pluck('code')->unique();
            $photos[$type] = DB::table($table)->whereIn('code', $codes)->get()->keyBy('code');
        }

        foreach ($items as &$item) {
            $item['place'] = null;
            $item['photos'] = [];
            if ($item['reference'] === '') {
                continue;
            }
            [$type, $id] = explode(':', $item['reference'], 2);
            $place = $entities[$type]->get((int) $id);
            $item['type'] = $type;
            if (!$place) {
                continue;
            }
            $item['place'] = $place;
            $photo = $photos[$type]->get($place->code);
            if ($photo) {
                $item['photos'] = array_slice(array_values(array_filter(array_map('trim', explode(';', $photo->foto)))), 0, 3);
            }
        }
        unset($item);

        return $items;
    }
}
