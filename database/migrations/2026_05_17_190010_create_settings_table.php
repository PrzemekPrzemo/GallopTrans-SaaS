<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'int', 'float', 'bool', 'json'])->default('string');
            $table->string('group', 50)->default('general');
            $table->string('label', 190)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
            $table->index(['organization_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
