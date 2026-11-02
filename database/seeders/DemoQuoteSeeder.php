<?php

namespace Database\Seeders;

use App\Actions\CreateQuote;
use App\Actions\Data\CustomerData;
use App\Actions\Data\ItemSelection;
use App\Enums\QuoteStatus;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * A few quotes so the admin list isn't empty. Created through the real action, so prices are genuine.
 */
class DemoQuoteSeeder extends Seeder
{
    public function run(CreateQuote $createQuote): void
    {
        $samples = [
            ['aluminium-sign', 1200, 600, 3, [0, 2], new CustomerData('Emma Clarke', 'emma@example.com', 'Clarke Dental Practice'), QuoteStatus::Accepted],
            ['vinyl-banner', 3000, 1000, 12, [0], new CustomerData('Tom Harris', 'tom@example.com', 'Riverside Running Club'), QuoteStatus::Sent],
            ['acrylic-letters', 180, 220, 14, [1], new CustomerData('Priya Shah', 'priya@example.com', 'Northwind Studio', 'Reception wall, matte black'), QuoteStatus::New],
        ];

        foreach ($samples as [$slug, $width, $height, $quantity, $optionIndexes, $customer, $status]) {
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
            $quote->update(['status' => $status]);
        }
    }
}
