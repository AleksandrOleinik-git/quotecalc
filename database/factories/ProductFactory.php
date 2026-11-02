<?php

namespace Database\Factories;

use App\Enums\PricingUnit;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word()).' sign';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'min_width_mm' => 100,
            'max_width_mm' => 2000,
            'min_height_mm' => 100,
            'max_height_mm' => 1000,
            'min_order_cents' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /** Adds one per-m² material priced at £50/m². */
    public function withMaterial(int $priceCents = 5000): static
    {
        return $this->afterCreating(function (Product $product) use ($priceCents): void {
            $product->materials()->create([
                'name' => '3 mm composite',
                'pricing_unit' => PricingUnit::SquareMetre,
                'price_cents' => $priceCents,
            ]);
        });
    }
}
