<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Tryb pracy z KSeF: disabled = nic; test = sandbox MF; production = live KSeF.
            $table->enum('ksef_mode', ['disabled', 'test', 'production'])->default('disabled');
            // Identyfikator podmiotu w KSeF — zazwyczaj NIP (10 cyfr).
            $table->string('ksef_identifier', 20)->nullable();
            // Ścieżka do certyfikatu .pem (klucz publiczny) lub .pfx (z prywatnym).
            // Sam plik leży poza repo, w storage/app/ksef/{org_id}/.
            $table->string('ksef_cert_path', 255)->nullable();
            // Zaszyfrowany hash hasła certyfikatu / token autoryzacyjny (Crypt::encrypt).
            $table->text('ksef_token_encrypted')->nullable();

            // Format numeracji faktur np. FV/{Y}/{M}/{####} (analogicznie do quote_number_format).
            $table->string('invoice_number_format', 100)->default('FV/{Y}/{M}/{####}');
            $table->unsignedTinyInteger('invoice_payment_due_days')->default(14);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'ksef_mode',
                'ksef_identifier',
                'ksef_cert_path',
                'ksef_token_encrypted',
                'invoice_number_format',
                'invoice_payment_due_days',
            ]);
        });
    }
};
