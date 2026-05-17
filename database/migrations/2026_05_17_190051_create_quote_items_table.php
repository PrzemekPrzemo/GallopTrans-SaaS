<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->enum('item_type', ['distance', 'fuel', 'surcharge', 'horse', 'fixed', 'custom'])->default('custom');
            $table->string('description', 255);
            $table->decimal('qty', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price_net', 10, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->smallInteger('sort_order')->default(0);

            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
