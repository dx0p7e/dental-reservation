<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('password');
            $table->enum('role', ['patient', 'doctor', 'admin'])->default('patient')->after('phone');
            $table->timestamp('gdpr_consent_at')->nullable()->after('remember_token');
            $table->timestamp('deletion_requested_at')->nullable()->after('gdpr_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'gdpr_consent_at',
                'deletion_requested_at',
            ]);
        });
    }
};
