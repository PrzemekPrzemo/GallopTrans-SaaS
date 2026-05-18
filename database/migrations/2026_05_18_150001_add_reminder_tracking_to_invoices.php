<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('last_reminder_at')->nullable()->after('payment_due_at');
            $table->unsignedTinyInteger('reminders_sent')->default(0)->after('last_reminder_at');
            $table->index(['organization_id', 'payment_due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'payment_due_at']);
            $table->dropColumn(['last_reminder_at', 'reminders_sent']);
        });
    }
};
