<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\PricingUnit;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogue;
use Tests\TestCase;

final class CalculateApiTest extends TestCase
{
    use CreatesCatalogue, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCatalogue();
    }

    public function test_returns_line_by_line_breakdown(): void
    {
        $payload = $this->itemPayload(['quantity' => 10, 'option_ids' => [$this->laminate->id, $this->design->id], 'include_vat' => true]);

        // (2500 + 250) × 10 = 27500, −10% = 24750, + 3000 design = 27750, VAT 5550
        $this->postJson('/api/quotes/calculate', $payload, $this->apiHeaders())
            ->assertOk()
            ->assertJsonPath('net_cents', 27750)
            ->assertJsonPath('vat_cents', 5550)
            ->assertJsonPath('total_cents', 33300)
            ->assertJsonPath('formatted.total', '£333.00')
            ->assertJsonPath('lines.2.type', 'discount')
            ->assertJsonPath('lines.2.amount_cents', -2750)
            ->assertJsonCount(4, 'lines');
    }

    public function test_does_not_save_anything(): void
    {
        $this->postJson('/api/quotes/calculate', $this->itemPayload(), $this->apiHeaders())->assertOk();

        $this->assertSame(0, Quote::count());
    }

    public function test_ignores_prices_sent_by_the_client(): void
    {
        $payload = $this->itemPayload(['price_cents' => 1, 'net_cents' => 1, 'total_cents' => 1]);

        $this->postJson('/api/quotes/calculate', $payload, $this->apiHeaders())->assertJsonPath('net_cents', 5000);
    }

    public function test_requires_item_fields(): void
    {
        $this->postJson('/api/quotes/calculate', [], $this->apiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'material_id', 'width_mm', 'height_mm', 'quantity']);
    }

    public function test_rejects_size_outside_product_limits(): void
    {
        $this->postJson('/api/quotes/calculate', $this->itemPayload(['width_mm' => 5000]), $this->apiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['width_mm' => 'Width must be between 100 and 2000 mm.']);
    }

    public function test_rejects_material_of_another_product(): void
    {
        $other = Product::factory()->create();
        $foreign = $other->materials()->create(['name' => 'X', 'pricing_unit' => PricingUnit::Unit, 'price_cents' => 1]);

        $this->postJson('/api/quotes/calculate', $this->itemPayload(['material_id' => $foreign->id]), $this->apiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('material_id');
    }

    public function test_rejects_inactive_option(): void
    {
        $this->postJson('/api/quotes/calculate', $this->itemPayload(['option_ids' => [$this->inactiveOption->id]]), $this->apiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('option_ids');
    }

    public function test_rejects_inactive_product(): void
    {
        $this->product->update(['is_active' => false]);

        $this->postJson('/api/quotes/calculate', $this->itemPayload(), $this->apiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    }

    public function test_applies_minimum_order(): void
    {
        $this->postJson('/api/quotes/calculate', $this->itemPayload(['width_mm' => 100, 'height_mm' => 100, 'quantity' => 1]), $this->apiHeaders())
            ->assertJsonPath('net_cents', 2000)
            ->assertJsonPath('lines.1.type', 'min_order');
    }
}
