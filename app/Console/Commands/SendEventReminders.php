<?php

namespace App\Console\Commands;

use App\Mail\EventReminder;
use App\Models\Attendee;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

#[Signature('events:send-reminders')]
#[Description('Queue 3-day and 24-hour reminder emails for upcoming events (idempotent).')]
class SendEventReminders extends Command
{
    private const DAY = 86_400;

    public function handle(): int
    {
        $now = Carbon::now()->getTimestamp();

        // Partition the next three days so each event hits exactly one window.
        $total = $this->sendWindow('24h', $now, $now + self::DAY)
            + $this->sendWindow('3d', $now + self::DAY, $now + 3 * self::DAY);

        $this->info("Queued {$total} reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Queue the given reminder kind for attendees of events starting in
     * (`$from`, `$to`], skipping anyone already reminded for that kind.
     *
     * @param  '3d'|'24h'  $kind
     */
    private function sendWindow(string $kind, int $from, int $to): int
    {
        $sent = 0;

        Attendee::query()
            ->with('event')
            ->whereHas('event', fn ($q) => $q->where('created_time', '>', $from)->where('created_time', '<=', $to))
            ->whereDoesntHave('reminders', fn ($q) => $q->where('kind', $kind))
            ->chunkById(500, function ($attendees) use ($kind, &$sent) {
                foreach ($attendees as $attendee) {
                    // Write the ledger row first; the unique (attendee_id, kind)
                    // constraint makes this safe to run repeatedly / concurrently.
                    $reminder = $attendee->reminders()->firstOrCreate(
                        ['kind' => $kind],
                        ['event_id' => $attendee->event_id, 'sent_at' => Carbon::now()],
                    );

                    if ($reminder->wasRecentlyCreated) {
                        Mail::to($attendee->email)->queue(new EventReminder($attendee, $kind));
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
