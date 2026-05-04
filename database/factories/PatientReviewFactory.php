<?php

namespace Database\Factories;

use App\Models\PatientReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientReview>
 */
class PatientReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => null,
            'rating' => fake()->numberBetween(4, 5),
            'title' => null,
            'body' => fake()->paragraph(),
            'is_published' => true,
        ];
    }
}
