<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 190);
            $table->string('slug', 100)->unique();
            $table->string('company_address', 255)->nullable();
            $table->string('company_nip', 20)->nullable();
            $table->string('company_phone', 40)->nullable();
            $table->string('company_email', 190)->nullable();
            $table->string('company_bank', 100)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('locale', 5)->default('pl');
            $table->char('currency', 3)->default('PLN');
            $table->string('timezone', 64)->default('Europe/Warsaw');

            // billing
            $table->string('plan', 30)->default('trial');
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
