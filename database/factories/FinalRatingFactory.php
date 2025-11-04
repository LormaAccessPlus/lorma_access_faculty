<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinalRating>
 */
class FinalRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prelimGrade = $this->faker->numberBetween(75, 95);
        $midtermGrade = $this->faker->numberBetween(75, 95);
        $finalsGrade = $this->faker->numberBetween(75, 95);
        
        // Calculate final rating using standard formula
        $finalRating = round(($prelimGrade * 0.3) + ($midtermGrade * 0.3) + ($finalsGrade * 0.4), 2);
        
        return [
            'student_mapping_id' => \App\Models\StudentMapping::factory(),
            'subject_id' => \App\Models\Subject::factory(),
            'prelim_grade' => $prelimGrade,
            'midterm_grade' => $midtermGrade,
            'finals_grade' => $finalsGrade,
            'final_rating' => $finalRating,
            'academic_year' => '2024-2025',
            'semester' => '1'
        ];
    }
}
