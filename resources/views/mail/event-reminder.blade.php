<x-mail::message>
# See you {{ $lead }}!

Hi {{ $attendeeName }},

This is a friendly reminder that **{{ $title }}** is happening **{{ $lead }}**.

**When:** {{ $when }}
**Where:** {{ $where }}

We're looking forward to seeing you,<br>
{{ config('app.name') }}
</x-mail::message>
