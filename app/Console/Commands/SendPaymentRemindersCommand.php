<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PaymentReminderService;
use Illuminate\Console\Command;

class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'saas:send-payment-reminders';

    protected $description = 'Wysyła przypomnienia o zaległych płatnościach (codziennie z cron).';

    public function handle(): int
    {
        $stats = PaymentReminderService::sendDue();
        $this->info(sprintf(
            'Wysłano: %d, pominięto: %d, błędy: %d.',
            $stats['sent'], $stats['skipped'], $stats['errors']
        ));
        return self::SUCCESS;
    }
}
