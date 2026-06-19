<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slim listing payload — only what a card / agenda row needs. The heavy
 * `payload` blob is deliberately left off the wire.
 *
 * @mixin Event
 */
class EventListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->payload['name'] ?? null,
            'description' => $this->payload['description'] ?? null,
            'type' => $this->type,
            'status' => $this->status,
            'created_time' => $this->created_time,
            'city' => $this->city,
            'country' => $this->country,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'images' => $this->images,
            'price' => [
                'min' => isset($this->payload['pricing']['min_price'])
                    ? (float) $this->payload['pricing']['min_price']
                    : null,
                'currency' => $this->payload['pricing']['currency'] ?? 'USD',
            ],
        ];
    }
}
