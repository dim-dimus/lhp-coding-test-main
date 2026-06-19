<?php

namespace App\Support;

/**
 * Offline reverse geocoder. Maps a latitude/longitude to the nearest seeded
 * city anchor (see config/geo.php) and returns a human-readable address. No
 * external API — events are jittered around known anchors, so nearest-anchor
 * recovers the city reliably and works in CI.
 */
class Geocoder
{
    /** @var array<int, array{0: float, 1: float, 2: string, 3: ?string, 4: string}>|null */
    private static ?array $anchors = null;

    /** @var list<string>|null */
    private static ?array $cities = null;

    /**
     * @return array{city: string, region: ?string, country: string}|null
     */
    public static function nearest(?float $lat, ?float $lng): ?array
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $anchors = self::anchors();

        $best = null;
        $bestDistance = INF;

        foreach ($anchors as $anchor) {
            // Squared euclidean distance is enough to rank nearest anchor.
            $distance = ($anchor[0] - $lat) ** 2 + ($anchor[1] - $lng) ** 2;
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $anchor;
            }
        }

        if ($best === null) {
            return null;
        }

        return ['city' => $best[2], 'region' => $best[3], 'country' => $best[4]];
    }

    /**
     * Compact, display-ready string, e.g. "New York, NY, USA" or "Tokyo, Japan".
     */
    public static function address(?float $lat, ?float $lng): ?string
    {
        $place = self::nearest($lat, $lng);

        if ($place === null) {
            return null;
        }

        return implode(', ', array_filter([$place['city'], $place['region'], $place['country']]));
    }

    /**
     * Sorted, unique list of city names — used to populate location filters.
     *
     * @return list<string>
     */
    public static function cities(): array
    {
        if (self::$cities !== null) {
            return self::$cities;
        }

        $names = array_map(fn (array $anchor): string => $anchor[2], self::anchors());
        $names = array_values(array_unique($names));
        sort($names);

        return self::$cities = $names;
    }

    /**
     * @return array<int, array{0: float, 1: float, 2: string, 3: ?string, 4: string}>
     */
    private static function anchors(): array
    {
        return self::$anchors ??= config('geo.anchors', []);
    }
}
