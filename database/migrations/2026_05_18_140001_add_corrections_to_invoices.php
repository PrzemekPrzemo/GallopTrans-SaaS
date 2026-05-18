<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 'invoice' = zwykła faktura VAT, 'correction' = faktura korygująca.
            $table->enum('type', ['invoice', 'correction'])->default('invoice')->after('sequence');
            $table->foreignId('corrects_invoice_id')->nullable()->after('type')
                ->constrained('invoices')->nullOnDelete();
            $table->string('correction_reason', 255)->nullable()->after('corrects_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['corrects_invoice_id']);
            $table->dropColumn(['type', 'corrects_invoice_id', 'correction_reason']);
        });
    }
};
