<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Mail\QuoteRequested;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\CreatesCatalogue;
use Tests\TestCase;

final class QuoteFlowTest extends TestCase
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
    private function form(array $overrides = []): array
    {
        return [
            ...$this->itemPayload(['option_ids' => [$this->laminate->id]]),
            'include_vat' => '1',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'company' => 'Acme Ltd',
            ...$overrides,
        ];
    }

    public function test_request_creates_quote_and_redirects_to_signed_page(): void
    {
        $response = $this->post('/quotes', $this->form());

        $quote = Quote::sole();
        $response->assertRedirect(URL::signedRoute('quotes.show', $quote));
    }

    public function test_stores_server_side_snapshot(): void
    {
        $this->post('/quotes', $this->form());

        // (2500 + 250) × 2 = 5500 net, 1100 VAT
        $item = Quote::sole()->items()->firstOrFail();
        $this->assertSame([5500, 1100, ['Laminate']], [$item->net_cents, $item->vat_cents, $item->option_names]);
    }

    public function test_sends_email_to_manager_with_pdf_attachment(): void
    {
        $this->post('/quotes', $this->form());

        Mail::assertSent(QuoteRequested::class, fn (QuoteRequested $mail): bool => $mail->hasTo('manager@example.com')
            && count($mail->attachments()) === 1);
    }

    public function test_quote_page_shows_breakdown_and_pdf_link(): void
    {
        $url = $this->post('/quotes', $this->form())->headers->get('Location');

        $this->get((string) $url)->assertOk()->assertSee(Quote::sole()->reference)->assertSee('£66.00')->assertSee('Download PDF');
    }

    public function test_pdf_downloads(): void
    {
        $this->post('/quotes', $this->form());
        $quote = Quote::sole();

        $this->get(URL::signedRoute('quotes.pdf', $quote))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload("quote-{$quote->reference}.pdf");
    }

    public function test_quote_page_requires_signature(): void
    {
        $this->post('/quotes', $this->form());

        $this->get('/quotes/'.Quote::sole()->reference)->assertForbidden();
    }

    public function test_pdf_requires_signature(): void
    {
        $this->post('/quotes', $this->form());

        $this->get('/quotes/'.Quote::sole()->reference.'/pdf')->assertForbidden();
    }

    public function test_validates_contact_details(): void
    {
        $this->from('/')->post('/quotes', $this->form(['name' => '', 'email' => 'not-an-email']))
            ->assertRedirect('/')
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_pricing_errors_return_to_form(): void
    {
        $this->from('/')->post('/quotes', $this->form(['width_mm' => 50]))
            ->assertRedirect('/')
            ->assertSessionHasErrors('width_mm');
    }

    public function test_invalid_request_saves_nothing(): void
    {
        $this->post('/quotes', $this->form(['email' => '']));

        $this->assertSame(0, Quote::count());
    }
}
