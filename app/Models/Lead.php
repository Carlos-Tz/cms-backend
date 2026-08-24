<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A contact captured through a `lead_capture_form` section of a public site.
 *
 * `site_id` is not fillable on purpose: the site always comes from the route
 * binding of the public endpoint, never from the payload. `source` is: it says
 * which page's form produced the lead, and the request validates it against the
 * published pages of that site so it cannot hold junk.
 */
#[Fillable(['source', 'name', 'email', 'phone', 'message'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
