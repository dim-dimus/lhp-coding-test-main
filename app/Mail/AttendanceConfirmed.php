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

class AttendanceConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Attendee $attendee) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're on the list — ".$this->eventTitle(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.attendance-confirmed',
            with: [
                'attendeeName' => $this->attendee->name,
                'title' => $this->eventTitle(),
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
