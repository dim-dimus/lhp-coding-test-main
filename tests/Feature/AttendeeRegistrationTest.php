<?php

use App\Mail\AttendanceConfirmed;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('registers an attendee and queues a confirmation email', function () {
    Mail::fake();
    $event = Event::factory()->for(User::factory())->create();

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
    ])->assertRedirect();

    $this->assertDatabaseHas('attendees', [
        'event_id' => $event->id,
        'email' => 'ada@example.test',
        'status' => 'interested',
    ]);

    Mail::assertQueued(AttendanceConfirmed::class, fn ($mail) => $mail->hasTo('ada@example.test'));
});

it('does not register the same email twice for one event', function () {
    Mail::fake();
    $event = Event::factory()->for(User::factory())->create();

    $payload = ['name' => 'Ada', 'email' => 'ada@example.test'];
    $this->post(route('events.attendees.store', $event), $payload);
    $this->post(route('events.attendees.store', $event), $payload);

    expect($event->attendees()->count())->toBe(1);
    Mail::assertQueued(AttendanceConfirmed::class, 1);
});

it('requires a name and a valid email', function () {
    $event = Event::factory()->for(User::factory())->create();

    $this->post(route('events.attendees.store', $event), ['email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);
});
