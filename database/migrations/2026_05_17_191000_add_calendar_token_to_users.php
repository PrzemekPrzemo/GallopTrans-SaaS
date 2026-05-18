<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('calendar_token', 40)->nullable()->unique()->after('locale');
        });

        // Backfill istniejących userów
        \DB::table('users')->whereNull('calendar_token')->orderBy('id')->each(function ($u) {
            \DB::table('users')->where('id', $u->id)->update(['calendar_token' => Str::random(40)]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['calendar_token']);
            $table->dropColumn('calendar_token');
        });
    }
};
