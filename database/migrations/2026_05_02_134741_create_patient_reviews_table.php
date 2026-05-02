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
        Schema::create('patient_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->nullOnDelete()->constrained('users');
            $table->tinyInteger('rating')->unsigned();
            $table->string('title', 150)->nullable();
            $table->text('body');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_reviews');
    }
};
