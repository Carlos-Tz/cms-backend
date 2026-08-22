<?php

namespace App\Models;

use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vehicle inspection booked from the public site.
 *
 * Data domain, like leads: born on the public site, read and cancelled by the
 * admin. `site_id` is not fillable — it comes from the route binding.
 */
#[Fillable(['station_id', 'date', 'slot', 'name', 'email', 'phone', 'vehicle_plate', 'vehicle_description'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * The bookable slots of a working day, Monday to Saturday.
     *
     * They live here and not in the `service_scheduling` section because the
     * availability endpoint is their single source of truth: the admin edits
     * the copy around the widget, not the opening hours.
     *
     * @var list<string>
     */
    public const SLOTS = ['10:00-12:00', '12:00-14:00', '14:00-16:00'];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * `date` is deliberately NOT cast to a Carbon date: the cast would store it
     * as `Y-m-d H:i:s`, and then every availability lookup and the unique index
     * on (station_id, date, slot) would compare against a value that never
     * matches the `Y-m-d` the API takes and returns.
     *
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
