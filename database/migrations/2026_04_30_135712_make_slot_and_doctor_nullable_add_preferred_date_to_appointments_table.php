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
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->change();
            $table->foreignId('slot_id')->nullable()->change();
            $table->date('preferred_date')->nullable()->after('slot_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('preferred_date');
            $table->foreignId('slot_id')->nullable(false)->change();
            $table->foreignId('doctor_id')->nullable(false)->change();
        });
    }
};
