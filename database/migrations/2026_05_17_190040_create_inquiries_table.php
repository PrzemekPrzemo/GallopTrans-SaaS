<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->char('token', 32)->unique();

            $table->string('client_name', 190);
            $table->string('client_email', 190);
            $table->string('client_phone', 40)->nullable();

            $table->string('from_address', 255);
            $table->string('to_address', 255);
            $table->date('transport_date')->nullable();
            $table->unsignedTinyInteger('horses_count')->default(1);
            $table->text('notes')->nullable();

            $table->string('source', 50)->default('web');
            $table->enum('status', ['new', 'in_progress', 'quoted', 'closed', 'spam'])->default('new');
            $table->foreignId('quote_id')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
