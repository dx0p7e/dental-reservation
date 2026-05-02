<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('slot_duration_minutes')->after('end_time');
            $table->boolean('is_active')->default(true)->after('slot_duration_minutes');
            $table->dropColumn('is_break');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropColumn(['slot_duration_minutes', 'is_active']);
            $table->boolean('is_break')->default(false);
        });
    }
};
