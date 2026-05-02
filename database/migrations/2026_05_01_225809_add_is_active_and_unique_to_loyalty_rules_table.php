<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove duplicate service_id rows, keeping the one with the highest id per service.
        // Uses a subquery that works with both MySQL and SQLite.
        DB::statement('
            DELETE FROM loyalty_rules
            WHERE id NOT IN (
                SELECT max_id FROM (
                    SELECT MAX(id) AS max_id FROM loyalty_rules GROUP BY service_id
                ) AS keep
            )
        ');

        Schema::table('loyalty_rules', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('valid_months');
            $table->unique('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_rules', function (Blueprint $table): void {
            $table->dropUnique(['service_id']);
            $table->dropColumn('is_active');
        });
    }
};
