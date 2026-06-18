<?php

namespace App\Models;

use App\Support\Geocoder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, HasUuids;

    /** Scene placeholders combined with the category image for each event. */
    private const SCENES = ['scene-stage', 'scene-crowd', 'scene-venue', 'scene-night'];

    protected $guarded = [];

    protected $appends = ['images', 'address', 'city'];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /** @var array{city: string, region: ?string, country: string}|null */
    private ?array $resolvedGeo = null;

    private bool $geoResolved = false;

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Two or more local placeholder images per event, picked deterministically
     * from the pool in public/images/events so an event always shows the same
     * set. Category image first, then two distinct scene images.
     *
     * @return list<string>
     */
    public function getImagesAttribute(): array
    {
        $seed = crc32((string) $this->id);
        $count = count(self::SCENES);

        $first = self::SCENES[$seed % $count];
        $second = self::SCENES[intdiv($seed, $count) % $count];
        if ($second === $first) {
            $second = self::SCENES[($seed + 1) % $count];
        }

        return array_map(
            fn (string $name): string => asset("images/events/{$name}.svg"),
            [$this->type, $first, $second],
        );
    }

    /** Human-readable address derived from the coordinates (offline). */
    public function getAddressAttribute(): ?string
    {
        $geo = $this->geo();

        if ($geo === null) {
            return null;
        }

        return implode(', ', array_filter([$geo['city'], $geo['region'], $geo['country']]));
    }

    /** City name only, handy for location filtering. */
    public function getCityAttribute(): ?string
    {
        return $this->geo()['city'] ?? null;
    }

    /** @return array{city: string, region: ?string, country: string}|null */
    private function geo(): ?array
    {
        if (! $this->geoResolved) {
            $this->resolvedGeo = Geocoder::nearest($this->latitude, $this->longitude);
            $this->geoResolved = true;
        }

        return $this->resolvedGeo;
    }
}
