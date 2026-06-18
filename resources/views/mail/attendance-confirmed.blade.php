<x-mail::message>
# You're on the list!

Hi {{ $attendeeName }},

Thanks for registering your interest in **{{ $title }}**. We've added you to the attendee list and we'll send you a reminder as the date approaches.

**When:** {{ $when }}
**Where:** {{ $where }}

See you there,<br>
{{ config('app.name') }}
</x-mail::message>
