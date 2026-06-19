<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Support\Geocoder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('events:backfill-locations {--all : Recompute every row, not just missing ones}')]
#[Description('Backfill events.city / events.country from the offline geocoder.')]
class BackfillEventLocations extends Command
{
    public function handle(): int
    {
        $done = 0;

        Event::query()
            ->select('id', 'latitude', 'longitude')
            ->when(! $this->option('all'), fn ($q) => $q->whereNull('city'))
            ->chunkById(2000, function ($events) use (&$done) {
                // Group ids by resolved place so each chunk is a handful of
                // bulk UPDATEs (one per distinct city) rather than per-row writes.
                $byPlace = [];

                foreach ($events as $event) {
                    $place = Geocoder::nearest($event->latitude, $event->longitude);
                    $key = $place ? $place['city'].'|'.$place['country'] : '|';
                    $byPlace[$key][] = $event->id;
                }

                foreach ($byPlace as $key => $ids) {
                    [$city, $country] = explode('|', $key, 2);
                    DB::table('events')->whereIn('id', $ids)->update([
                        'city' => $city !== '' ? $city : null,
                        'country' => $country !== '' ? $country : null,
                    ]);
                }

                $done += $events->count();
                $this->getOutput()->write("\r  updated {$done}");
            });

        $this->getOutput()->writeln("\r  updated {$done} event(s).");

        return self::SUCCESS;
    }
}
