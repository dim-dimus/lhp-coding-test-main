<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event_id
 * @property int $attendee_id
 * @property string $kind
 * @property Carbon $sent_at
 */
class EventReminder extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'attendee_id', 'kind', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
