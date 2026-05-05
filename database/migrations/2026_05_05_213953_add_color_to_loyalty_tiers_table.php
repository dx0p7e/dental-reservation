<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->string('color', 30)->default('#6b7280')->after('discount_bonus_pct');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_tiers', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
