<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Denormalized location, backfilled from the offline geocoder, so the
            // location filter is an indexed equality instead of a lat/lng scan.
            $table->string('city')->nullable()->after('longitude');
            $table->string('country')->nullable()->after('city');

            // Keyset pagination + chronological sort, with and without a city filter.
            $table->index(['created_time', 'id'], 'events_created_time_id_index');
            $table->index(['city', 'created_time', 'id'], 'events_city_created_time_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_created_time_id_index');
            $table->dropIndex('events_city_created_time_id_index');
            $table->dropColumn(['city', 'country']);
        });
    }
};
