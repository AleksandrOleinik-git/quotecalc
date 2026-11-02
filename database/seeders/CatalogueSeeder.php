<?php

namespace Database\Seeders;

use App\Enums\OptionType;
use App\Enums\PricingUnit;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Realistic sign & print catalogue. Money in pence, rates in basis points.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalogue() as $sort => $data) {
            $product = Product::updateOrCreate(['slug' => $data['slug']], [
                ...$data['product'],
                'slug' => $data['slug'],
                'sort_order' => $sort,
                'is_active' => true,
            ]);

            $product->materials()->delete();
            $product->options()->delete();
            $product->tiers()->delete();

            foreach ($data['materials'] as $i => [$name, $unit, $price]) {
                $product->materials()->create(['name' => $name, 'pricing_unit' => $unit, 'price_cents' => $price, 'sort_order' => $i]);
            }
            foreach ($data['options'] as $i => [$name, $type, $value]) {
                $product->options()->create(['name' => $name, 'type' => $type, 'value' => $value, 'sort_order' => $i]);
            }
            foreach ($data['tiers'] as [$minQuantity, $discountBps]) {
                $product->tiers()->create(['min_quantity' => $minQuantity, 'discount_bps' => $discountBps]);
            }
        }
    }

    /**
     * @return list<array{slug: string, product: array<string, mixed>, materials: list<array{string, PricingUnit, int}>, options: list<array{string, OptionType, int}>, tiers: list<array{int, int}>}>
     */
    private function catalogue(): array
    {
        return [
            [
                'slug' => 'aluminium-sign',
                'product' => [
                    'name' => 'Aluminium sign',
                    'description' => 'Full-colour print on aluminium composite panel. Ideal for shop fronts, site boards and wayfinding.',
                    'min_width_mm' => 100, 'max_width_mm' => 3050,
                    'min_height_mm' => 100, 'max_height_mm' => 1500,
                    'min_order_cents' => 2500,
                ],
                'materials' => [
                    ['3 mm aluminium composite (white)', PricingUnit::SquareMetre, 4500],
                    ['4 mm aluminium composite (white)', PricingUnit::SquareMetre, 5600],
                    ['3 mm brushed silver composite', PricingUnit::SquareMetre, 6800],
                ],
                'options' => [
                    ['Matt anti-glare laminate', OptionType::Percent, 1000],
                    ['Anti-graffiti laminate', OptionType::Percent, 1500],
                    ['Drilled corner holes', OptionType::PerUnit, 150],
                    ['Rounded corners', OptionType::PerUnit, 200],
                    ['Rear rail mounting kit', OptionType::PerUnit, 1200],
                    ['Artwork design service', OptionType::Fixed, 3500],
                ],
                'tiers' => [[5, 500], [10, 1000], [25, 1500], [50, 2000]],
            ],
            [
                'slug' => 'vinyl-banner',
                'product' => [
                    'name' => 'Vinyl banner',
                    'description' => 'Weatherproof PVC banner for events, fences and scaffolding. Printed with UV-stable inks.',
                    'min_width_mm' => 300, 'max_width_mm' => 5000,
                    'min_height_mm' => 300, 'max_height_mm' => 3000,
                    'min_order_cents' => 1500,
                ],
                'materials' => [
                    ['440 gsm frontlit PVC', PricingUnit::SquareMetre, 1200],
                    ['510 gsm heavy-duty PVC', PricingUnit::SquareMetre, 1600],
                    ['370 gsm mesh (wind-permeable)', PricingUnit::SquareMetre, 1450],
                ],
                'options' => [
                    ['Hemmed edges & eyelets every 50 cm', OptionType::Percent, 2000],
                    ['Pole pockets (top & bottom)', OptionType::PerUnit, 800],
                    ['Double-sided print', OptionType::Percent, 8000],
                    ['Express production (48 h)', OptionType::Fixed, 2500],
                ],
                'tiers' => [[3, 500], [10, 1000], [20, 1500]],
            ],
            [
                'slug' => 'acrylic-letters',
                'product' => [
                    'name' => 'Acrylic letters',
                    'description' => 'Laser-cut 3D acrylic lettering and logos for reception walls. Priced per piece by bounding box.',
                    'min_width_mm' => 50, 'max_width_mm' => 1000,
                    'min_height_mm' => 50, 'max_height_mm' => 1000,
                    'min_order_cents' => 4000,
                ],
                'materials' => [
                    ['5 mm cast acrylic (gloss colour)', PricingUnit::SquareMetre, 28000],
                    ['10 mm cast acrylic (gloss colour)', PricingUnit::SquareMetre, 42000],
                    ['10 mm mirror gold acrylic', PricingUnit::SquareMetre, 55000],
                ],
                'options' => [
                    ['Painted face (Pantone match)', OptionType::Percent, 2500],
                    ['Stand-off locators', OptionType::PerUnit, 350],
                    ['Paper fitting template', OptionType::Fixed, 1500],
                ],
                'tiers' => [[10, 500], [26, 1000], [50, 1500]],
            ],
            [
                'slug' => 'vinyl-stickers',
                'product' => [
                    'name' => 'Vinyl stickers',
                    'description' => 'Self-adhesive printed vinyl for windows, vans and packaging.',
                    'min_width_mm' => 20, 'max_width_mm' => 1200,
                    'min_height_mm' => 20, 'max_height_mm' => 1200,
                    'min_order_cents' => 1000,
                ],
                'materials' => [
                    ['Gloss white permanent vinyl', PricingUnit::SquareMetre, 2200],
                    ['Clear permanent vinyl', PricingUnit::SquareMetre, 2600],
                    ['Removable low-tack vinyl', PricingUnit::SquareMetre, 2400],
                    ['Pre-cut circle sticker 50 mm', PricingUnit::Unit, 18],
                ],
                'options' => [
                    ['Contour cut to shape', OptionType::Percent, 1500],
                    ['Gloss UV laminate', OptionType::Percent, 1200],
                    ['Application tape', OptionType::PerUnit, 10],
                ],
                'tiers' => [[50, 1000], [100, 2000], [250, 3000], [500, 4000]],
            ],
        ];
    }
}
