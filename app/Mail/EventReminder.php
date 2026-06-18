<?php

namespace App\Mail;

use App\Models\Attendee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class EventReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  '3d'|'24h'  $kind
     */
    public function __construct(public Attendee $attendee, public string $kind) {}

    public function envelope(): Envelope
    {
        $lead = $this->kind === '24h' ? 'tomorrow' : 'in a few days';

        return new Envelope(
            subject: "Reminder: {$this->eventTitle()} is {$lead}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.event-reminder',
            with: [
                'attendeeName' => $this->attendee->name,
                'title' => $this->eventTitle(),
                'lead' => $this->kind === '24h' ? 'in 24 hours' : 'in 3 days',
                'when' => $this->eventWhen(),
                'where' => $this->attendee->event->address ?? 'Location to be announced',
            ],
        );
    }

    private function eventTitle(): string
    {
        return $this->attendee->event->payload['name'] ?? 'the event';
    }

    private function eventWhen(): string
    {
        $timestamp = $this->attendee->event->created_time;

        return $timestamp
            ? Carbon::createFromTimestamp($timestamp, 'UTC')->format('D, M j, Y \a\t H:i').' UTC'
            : 'To be announced';
    }
}
