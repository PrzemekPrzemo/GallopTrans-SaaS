<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice, public float $balance) {}

    public function envelope(): Envelope
    {
        $org = $this->invoice->organization;
        $daysOverdue = $this->invoice->payment_due_at?->diffInDays(now(), false);

        $subject = $daysOverdue > 0
            ? "Przypomnienie: faktura {$this->invoice->number} zaległa od {$daysOverdue} dni"
            : "Przypomnienie o płatności: faktura {$this->invoice->number}";

        return new Envelope(
            from: new Address($org->company_email ?: config('mail.from.address'), $org->name),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoices.reminder',
            with: [
                'invoice'   => $this->invoice,
                'balance'   => $this->balance,
                'daysOverdue' => (int) $this->invoice->payment_due_at?->diffInDays(now(), false),
            ],
        );
    }
}
