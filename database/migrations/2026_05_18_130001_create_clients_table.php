<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 190);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('company', 190)->nullable();
            $table->string('nip', 20)->nullable();
            $table->string('address', 255)->nullable();

            $table->decimal('default_rate_per_km', 8, 2)->nullable();
            $table->decimal('default_min_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
            $table->index(['organization_id', 'email']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('inquiry_id')
                ->constrained('clients')->nullOnDelete();
            $table->index(['organization_id', 'client_id'], 'q_org_client');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropIndex('q_org_client');
            $table->dropColumn('client_id');
        });
        Schema::dropIfExists('clients');
    }
};
