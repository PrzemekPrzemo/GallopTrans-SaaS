<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 190);
            $table->string('plate', 20)->nullable();
            $table->enum('fuel_type', ['diesel', 'petrol', 'lpg', 'electric'])->default('diesel');
            $table->decimal('fuel_consumption', 5, 2)->default(25.00)->comment('l/100km');
            $table->unsignedTinyInteger('horse_capacity')->default(2);
            $table->unsignedInteger('max_weight_kg')->nullable();
            $table->decimal('height_m', 4, 2)->nullable();
            $table->decimal('length_m', 4, 2)->nullable();
            $table->decimal('width_m', 4, 2)->nullable();
            $table->unsignedTinyInteger('axles')->nullable();
            $table->boolean('is_trailer')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
