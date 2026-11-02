<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Mail\QuoteRequested;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesCatalogue;
use Tests\TestCase;

final class StoreQuoteApiTest extends TestCase
{
    use CreatesCatalogue, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createCatalogue();
        Mail::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'customer' => ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'company' => 'Acme'],
            'include_vat' => true,
            'items' => [
                $this->itemPayload(),
                $this->itemPayload(['quantity' => 1, 'option_ids' => [$this->design->id]]),
            ],
            ...$overrides,
        ];
    }

    public function test_creates_quote_with_all_items(): void
    {
        // 5000 + (2500 + 3000) = 10500 net, 2100 VAT
        $this->postJson('/api/quotes', $this->payload(), $this->apiHeaders())
            ->assertCreated()
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.net_cents', 10500)
            ->assertJsonPath('data.total_cents', 12600)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_returns_signed_pdf_url(): void
    {
        $url = $this->postJson('/api/quotes', $this->payload(), $this->apiHeaders())->json('data.pdf_url');

        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_notifies_manager(): void
    {
        $this->postJson('/api/quotes', $this->payload(), $this->apiHeaders());

        Mail::assertSent(QuoteRequested::class, fn (QuoteRequested $mail): bool => $mail->hasTo('manager@example.com'));
    }

    public function test_marks_source_as_api(): void
    {
        $this->postJson('/api/quotes', $this->payload(), $this->apiHeaders());

        $this->assertSame('api', Quote::sole()->source);
    }

    public function test_requires_customer_and_items(): void
    {
        $this->postJson('/api/quotes', ['items' => []], $this->apiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer.name', 'customer.email', 'items']);
    }

    public function test_reports_pricing_errors_against_the_item_index(): void
    {
        $payload = $this->payload(['items' => [$this->itemPayload(), $this->itemPayload(['height_mm' => 5000])]]);

        $this->postJson('/api/quotes', $payload, $this->apiHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.height_mm');
    }

    public function test_saves_nothing_when_any_item_is_invalid(): void
    {
        $payload = $this->payload(['items' => [$this->itemPayload(), $this->itemPayload(['height_mm' => 5000])]]);

        $this->postJson('/api/quotes', $payload, $this->apiHeaders());

        $this->assertSame(0, Quote::count());
    }

    public function test_quote_keeps_its_prices_after_catalogue_changes(): void
    {
        $this->postJson('/api/quotes', $this->payload(), $this->apiHeaders());

        $this->material->update(['price_cents' => 99_900, 'name' => 'Renamed']);
        $this->product->delete();

        $item = Quote::sole()->items()->firstOrFail();
        $this->assertSame([5000, 'Composite', 'Test sign', null], [$item->net_cents, $item->material_name, $item->product_name, $item->product_id]);
    }
}
