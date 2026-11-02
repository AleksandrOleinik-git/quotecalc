<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Quote;
use App\Services\QuotePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the sales manager that a customer requested a quote.
 */
class QuoteRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->quote->customer_email, $this->quote->customer_name)],
            subject: "New quote request {$this->quote->reference}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quote-requested');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $pdf = app(QuotePdf::class);

        return [
            Attachment::fromData(fn (): string => $pdf->render($this->quote), $pdf->filename($this->quote))
                ->withMime('application/pdf'),
        ];
    }
}
