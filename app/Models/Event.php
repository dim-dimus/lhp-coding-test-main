<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string $status
 * @property int|null $created_time
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $city
 * @property string|null $country
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read list<string> $images
 * @property-read string|null $address
 */
class Event extends Model
{
    use HasFactory, HasUuids;

    /** Scene placeholders combined with the category image for each event. */
    private const SCENES = ['scene-stage', 'scene-crowd', 'scene-venue', 'scene-night'];

    protected $guarded = [];

    protected $appends = ['images', 'address'];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Attendee, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
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

    /** Human-readable address built from the denormalized location columns. */
    public function getAddressAttribute(): ?string
    {
        $parts = array_filter([$this->city, $this->country]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
