<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogue;
use Tests\TestCase;

final class CalculatorPageTest extends TestCase
{
    use CreatesCatalogue, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCatalogue();
    }

    public function test_shows_calculator_with_products(): void
    {
        $this->get('/')->assertOk()->assertSee('Get an instant price')->assertSee('Test sign');
    }

    public function test_live_price_endpoint_returns_breakdown(): void
    {
        $this->postJson('/calculator/price', $this->itemPayload(['include_vat' => false]))
            ->assertOk()
            ->assertJsonPath('net_cents', 5000)
            ->assertJsonPath('total_cents', 5000);
    }

    public function test_live_price_endpoint_validates_input(): void
    {
        $this->postJson('/calculator/price', $this->itemPayload(['quantity' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }
}
