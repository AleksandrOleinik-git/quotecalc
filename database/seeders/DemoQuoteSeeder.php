<?php

namespace Database\Seeders;

use App\Actions\CreateQuote;
use App\Actions\Data\CustomerData;
use App\Actions\Data\ItemSelection;
use App\Enums\QuoteStatus;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A month of quotes so the admin list and dashboard chart aren't empty. Created through the real action, so prices are genuine.
 */
class DemoQuoteSeeder extends Seeder
{
    public const GENERATED_QUOTES = 40;

    public function run(CreateQuote $createQuote): void
    {
        $samples = [
            ['aluminium-sign', 1200, 600, 3, [0, 2], new CustomerData('Emma Clarke', 'emma@example.com', 'Clarke Dental Practice'), QuoteStatus::Accepted, 6],
            ['vinyl-banner', 3000, 1000, 12, [0], new CustomerData('Tom Harris', 'tom@example.com', 'Riverside Running Club'), QuoteStatus::Sent, 2],
            ['acrylic-letters', 180, 220, 14, [1], new CustomerData('Priya Shah', 'priya@example.com', 'Northwind Studio', 'Reception wall, matte black'), QuoteStatus::New, 0],
        ];

        // Same pseudo-random month on every seed, so screenshots and tests stay stable.
        mt_srand(2026);
        $companies = ['Harbour Café', 'Greenleaf Gym', 'Oakwood Builders', 'Bright Smile Dental', 'City Lettings', 'Maple Bakery', 'Summit Motors', 'Bluebell Florist'];
        $sizes = ['aluminium-sign' => [300, 1500], 'vinyl-banner' => [1000, 3000], 'acrylic-letters' => [100, 400], 'vinyl-stickers' => [50, 300]];
        for ($i = 0; $i < self::GENERATED_QUOTES; $i++) {
            $slug = array_rand($sizes);
            [$min, $max] = $sizes[$slug];
            $company = $companies[array_rand($companies)];
            $samples[] = [$slug, mt_rand($min, $max), mt_rand($min, $max), mt_rand(1, 20), [mt_rand(0, 1)],
                new CustomerData('Demo Customer', 'demo@example.com', $company),
                QuoteStatus::cases()[array_rand(QuoteStatus::cases())], mt_rand(0, 29)];
        }

        foreach ($samples as [$slug, $width, $height, $quantity, $optionIndexes, $customer, $status, $daysAgo]) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();
            $materialId = $product->materials()->firstOrFail()->id;
            // The catalogue seeder stores each option's list position as its sort order.
            $optionIds = $product->options()->whereIn('sort_order', $optionIndexes)->pluck('id')->all();

            $quote = $createQuote->handle(
                $customer,
                [new ItemSelection($product->id, $materialId, $width, $height, $quantity, array_values($optionIds))],
                includeVat: true,
                source: 'seed',
            );
            $createdAt = Carbon::now()->subDays($daysAgo)->setTime(mt_rand(8, 18), mt_rand(0, 59));
            $quote->forceFill(['status' => $status, 'created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }
    }
}
