<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\Services\PaymentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reminder_for_overdue_unpaid_invoice(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice('owes@example.com', overdueDays: 5, paidAmount: 0);

        $stats = PaymentReminderService::sendDue();

        Mail::assertSent(PaymentReminderMail::class);
        $this->assertEquals(1, $stats['sent']);
        $this->assertEquals(1, $invoice->fresh()->reminders_sent);
        $this->assertNotNull($invoice->fresh()->last_reminder_at);
    }

    public function test_skips_invoice_that_is_already_paid(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice('paid@example.com', overdueDays: 10, paidAmount: 1230);

        $stats = PaymentReminderService::sendDue();

        Mail::assertNothingSent();
        $this->assertEquals(0, $stats['sent']);
        $this->assertEquals(1, $stats['skipped']);
    }

    public function test_respects_cooldown_between_reminders(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice('owes2@example.com', overdueDays: 5, paidAmount: 0);
        $invoice->update(['last_reminder_at' => now()->subDay(), 'reminders_sent' => 1]);

        PaymentReminderService::sendDue();

        Mail::assertNothingSent();
        $this->assertEquals(1, $invoice->fresh()->reminders_sent);  // bez zmian
    }

    private function makeInvoice(string $email, int $overdueDays, float $paidAmount): Invoice
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $quote = Quote::create([
            'organization_id' => $org->id,
            'number' => 'OF/2026/05/0001', 'year' => 2026, 'month' => 5, 'sequence' => 1,
            'client_name' => 'X', 'from_address' => 'A', 'to_address' => 'B',
            'fuel_consumption' => 25, 'fuel_price' => 6.5, 'base_rate_per_km' => 4.5,
            'subtotal_net' => 1000, 'vat_amount' => 230, 'total_gross' => 1230,
            'vat_percent' => 23, 'currency' => 'PLN',
            'public_token' => Str::random(40),
        ]);

        if ($paidAmount > 0) {
            Payment::create([
                'organization_id' => $org->id, 'quote_id' => $quote->id,
                'amount_gross' => $paidAmount, 'amount_net' => $paidAmount / 1.23,
                'vat_amount' => $paidAmount - ($paidAmount / 1.23),
                'currency' => 'PLN', 'paid_at' => now()->toDateString(),
            ]);
        }

        return Invoice::create([
            'organization_id' => $org->id, 'quote_id' => $quote->id,
            'number' => 'FV/2026/05/0001', 'year' => 2026, 'month' => 5, 'sequence' => 1,
            'client_name' => 'X', 'client_email' => $email,
            'subtotal_net' => 1000, 'vat_amount' => 230, 'total_gross' => 1230,
            'vat_percent' => 23, 'currency' => 'PLN',
            'issued_at' => now()->subDays($overdueDays + 14)->toDateString(),
            'sold_at' => now()->subDays($overdueDays + 14)->toDateString(),
            'payment_due_at' => now()->subDays($overdueDays)->toDateString(),
        ]);
    }
}
