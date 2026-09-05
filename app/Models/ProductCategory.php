<?php

namespace App\Models;

use Database\Factories\ProductCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product category of the Tectuus catalogue — EDRM, compresoras, valvulería.
 * Data domain, not blueprint structure: the admin creates, edits and deletes
 * these freely, like blog posts.
 *
 * Categories are not pages: they do not show up in `GET {site}/menu`. The front
 * builds that submenu from `GET {site}/product-categories`.
 */
#[Fillable(['site_id', 'slug', 'name', 'description', 'image', 'position'])]
class ProductCategory extends Model
{
    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
