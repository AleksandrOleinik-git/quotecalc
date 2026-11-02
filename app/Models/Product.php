<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $min_width_mm
 * @property int $max_width_mm
 * @property int $min_height_mm
 * @property int $max_height_mm
 * @property int $min_order_cents
 * @property bool $is_active
 * @property int $sort_order
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description',
        'min_width_mm', 'max_width_mm', 'min_height_mm', 'max_height_mm',
        'min_order_cents', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_width_mm' => 'integer',
            'max_width_mm' => 'integer',
            'min_height_mm' => 'integer',
            'max_height_mm' => 'integer',
            'min_order_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<Material, $this> */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<ProductOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasMany<QuantityTier, $this> */
    public function tiers(): HasMany
    {
        return $this->hasMany(QuantityTier::class)->orderBy('min_quantity');
    }

    /**
     * Active products with their active materials/options and tiers, ready for the calculator.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeForCalculator(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with([
                'materials' => fn (HasMany $q) => $q->where('is_active', true),
                'options' => fn (HasMany $q) => $q->where('is_active', true),
                'tiers',
            ]);
    }
}
