<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Quote;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public ?string $messageBody = null,
    ) {}

    public function envelope(): Envelope
    {
        $org = $this->quote->organization;
        return new Envelope(
            from: new Address(
                $org->company_email ?: config('mail.from.address'),
                $org->name,
            ),
            subject: "Oferta {$this->quote->number} — {$org->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.quotes.sent',
            with: [
                'quote'       => $this->quote,
                'messageBody' => $this->messageBody,
                'publicUrl'   => route('quotes.public', $this->quote->public_token),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => PdfService::binary($this->quote), "{$this->quote->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
