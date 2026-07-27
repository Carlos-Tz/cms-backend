<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name'])]
class Site extends Model
{
    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Site-wide sections (contact info, social icons, whatsapp button).
     *
     * @return HasMany<Section, $this>
     */
    public function globalSections(): HasMany
    {
        return $this->sections()->whereNull('page_id')->orderBy('position');
    }
}
