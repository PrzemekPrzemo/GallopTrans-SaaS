<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();

            $table->string('number', 40);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('sequence');

            // Snapshot z oferty / klienta (faktura jest niezmienna po wystawieniu).
            $table->string('client_name', 190);
            $table->string('client_company', 190)->nullable();
            $table->string('client_nip', 20)->nullable();
            $table->string('client_address', 255)->nullable();
            $table->string('client_email', 190)->nullable();

            $table->decimal('subtotal_net', 12, 2);
            $table->decimal('vat_amount', 12, 2);
            $table->decimal('total_gross', 12, 2);
            $table->decimal('vat_percent', 5, 2)->default(23.00);
            $table->char('currency', 3)->default('PLN');

            $table->date('issued_at');
            $table->date('sold_at');           // data sprzedaży
            $table->date('payment_due_at')->nullable();
            $table->enum('payment_method', ['transfer', 'cash', 'card', 'other'])->default('transfer');

            // KSeF metadata
            $table->enum('ksef_status', ['draft', 'sending', 'sent', 'rejected', 'manual'])->default('draft');
            $table->string('ksef_reference', 64)->nullable();   // KSeF reference number
            $table->string('ksef_session_token', 64)->nullable();
            $table->json('ksef_response')->nullable();           // diagnostyczne
            $table->timestamp('ksef_sent_at')->nullable();
            $table->timestamp('ksef_confirmed_at')->nullable();

            $table->string('xml_path', 255)->nullable();
            $table->string('upo_path', 255)->nullable();         // UPO PDF z KSeF
            $table->string('pdf_path', 255)->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'year', 'month', 'sequence'], 'inv_seq');
            $table->unique(['organization_id', 'number'], 'inv_num');
            $table->index(['organization_id', 'ksef_status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('qty', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price_net', 10, 2);
            $table->decimal('total_net', 12, 2);
            $table->decimal('vat_percent', 5, 2)->default(23.00);
            $table->smallInteger('sort_order')->default(0);

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
