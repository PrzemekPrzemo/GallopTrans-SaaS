<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Pod-typ obok 'type' (invoice/correction):
            //   regular  - zwykła faktura w pełnej kwocie,
            //   advance  - faktura zaliczkowa (RodzajFaktury='ZAL' w KSeF),
            //   final    - faktura końcowa rozliczeniowa (po zaliczkach).
            $table->enum('invoice_subtype', ['regular', 'advance', 'final'])->default('regular')->after('type');

            // JSON z ID faktur zaliczkowych rozliczonych przez tę fakturę końcową.
            $table->json('advance_invoice_ids')->nullable()->after('invoice_subtype');

            // Dla faktur 'final' — kwota już rozliczona zaliczkami.
            $table->decimal('settled_from_advances', 12, 2)->default(0)->after('advance_invoice_ids');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_subtype', 'advance_invoice_ids', 'settled_from_advances']);
        });
    }
};
