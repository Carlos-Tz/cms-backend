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
#[Fillable(['source', 'name', 'email', 'phone', 'message', 'details'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * The fields every `lead_capture_form` has, one column each. Anything a
     * form declares beyond these is an extra and lands in `details`.
     *
     * @var list<string>
     */
    public const CORE_FIELDS = ['name', 'email', 'phone', 'message'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
