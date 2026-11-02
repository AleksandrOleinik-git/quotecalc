<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogue;
use Tests\TestCase;

final class ProductsApiTest extends TestCase
{
    use CreatesCatalogue, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCatalogue();
    }

    public function test_requires_api_key(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_rejects_wrong_api_key(): void
    {
        $this->getJson('/api/products', ['X-Api-Key' => 'nope'])->assertUnauthorized();
    }

    public function test_is_locked_when_no_key_is_configured(): void
    {
        config(['quotecalc.api_key' => null]);

        $this->getJson('/api/products', ['X-Api-Key' => ''])->assertUnauthorized();
    }

    public function test_lists_active_products_with_pricing_configuration(): void
    {
        Product::factory()->create(['name' => 'Hidden', 'is_active' => false]);

        $this->getJson('/api/products', $this->apiHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Test sign')
            ->assertJsonPath('data.0.materials.0.price_cents', 5000)
            ->assertJsonCount(2, 'data.0.options')
            ->assertJsonPath('data.0.quantity_tiers.0.discount_bps', 1000);
    }
}
