<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the events listing shell without authentication', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('statuses', 4)
            ->where('filters.from', '2023-01-01')
        );
});

it('returns a keyset page of slim events with load stats', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'city' => 'New York',
        'country' => 'USA',
        'payload' => ['name' => 'Jazz Night', 'description' => 'Live jazz'],
    ]);

    $response = $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'type', 'status', 'created_time', 'city', 'country', 'address', 'images', 'price']],
            'next_cursor',
            'has_more',
            'stats' => ['ms', 'bytes'],
        ])
        ->assertJsonPath('has_more', false)
        ->assertJsonPath('data.0.type', 'concert')
        ->assertJsonPath('data.0.name', 'Jazz Night')
        ->assertJsonPath('data.0.city', 'New York')
        ->assertJsonPath('data.0.created_time', 1_700_000_000);

    // Slim resource: the heavy payload blob is never on the wire.
    expect($response->json('data.0'))->not->toHaveKey('payload');
});

it('filters the data endpoint by status', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['status' => 'published']);
    Event::factory()->for($user)->create(['status' => 'cancelled']);

    $response = $this->getJson(route('events.data', ['status' => 'cancelled']))->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.status', 'cancelled');
});

it('filters the data endpoint by city', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['city' => 'Berlin']);
    Event::factory()->for($user)->create(['city' => 'Paris']);

    $response = $this->getJson(route('events.data', ['city' => 'Berlin']))->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.city', 'Berlin');
});

it('paginates by cursor without duplicates or gaps', function () {
    $user = User::factory()->create();
    foreach (range(1, 30) as $i) {
        Event::factory()->for($user)->create(['created_time' => 1_700_000_000 + $i]);
    }

    $seen = [];
    $cursor = null;

    do {
        $params = ['sort' => 'asc'];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        $payload = $this->getJson(route('events.data', $params))->assertOk()->json();
        foreach ($payload['data'] as $row) {
            $seen[] = $row['id'];
        }
        $cursor = $payload['next_cursor'];
    } while ($payload['has_more']);

    expect($seen)->toHaveCount(30);                // no gaps
    expect(array_unique($seen))->toHaveCount(30);  // no duplicates
});

it('shows an event detail page with its payload', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'payload' => ['name' => 'Global Tech Summit', 'location' => ['lat' => 1.5, 'lng' => 2.5]],
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.payload.name', 'Global Tech Summit')
        );
});

it('renders the two visualization pages without authentication', function () {
    $this->get(route('events.visual1'))->assertOk();
    $this->get(route('events.visual2'))->assertOk();
});
