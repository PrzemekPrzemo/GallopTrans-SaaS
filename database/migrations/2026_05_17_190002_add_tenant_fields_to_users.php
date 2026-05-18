<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')
                ->constrained('organizations')->nullOnDelete();
            $table->enum('role', ['owner', 'admin', 'operator', 'driver'])
                ->default('owner')->after('email');
            $table->string('phone', 40)->nullable()->after('role');
            $table->string('locale', 5)->default('pl')->after('phone');
            $table->boolean('is_active')->default(true)->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index(['organization_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'role', 'phone', 'locale', 'is_active', 'last_login_at']);
        });
    }
};
