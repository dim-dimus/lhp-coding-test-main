<?php

use App\Mail\EventReminder;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/** Create an event starting `$seconds` from now, with one attendee. */
function eventStartingIn(int $seconds): Attendee
{
    $event = Event::factory()->for(User::factory())->create([
        'created_time' => Carbon::now()->timestamp + $seconds,
    ]);

    return Attendee::create([
        'event_id' => $event->id,
        'name' => 'Ada',
        'email' => 'ada@example.test',
        'status' => 'attending',
    ]);
}

it('queues a 24h reminder for an event starting within a day', function () {
    Mail::fake();
    $attendee = eventStartingIn(20 * 3600);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminder::class, fn ($mail) => $mail->kind === '24h' && $mail->hasTo('ada@example.test'));
    $this->assertDatabaseHas('event_reminders', ['attendee_id' => $attendee->id, 'kind' => '24h']);
});

it('queues a 3-day reminder for an event two days out', function () {
    Mail::fake();
    eventStartingIn(2 * 86400);

    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminder::class, fn ($mail) => $mail->kind === '3d');
});

it('does not send the same reminder twice', function () {
    Mail::fake();
    eventStartingIn(20 * 3600);

    $this->artisan('events:send-reminders');
    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminder::class, 1);
});

it('ignores events outside the reminder windows', function () {
    Mail::fake();
    eventStartingIn(-3600);       // already started
    eventStartingIn(10 * 86400);  // far in the future

    $this->artisan('events:send-reminders');

    Mail::assertNothingQueued();
});
