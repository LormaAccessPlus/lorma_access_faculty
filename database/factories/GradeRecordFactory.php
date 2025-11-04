<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Faculty;
use App\Models\StudentMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GradeRecord>
 */
class GradeRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $maxScore = $this->faker->randomFloat(2, 50, 100);
        $score = $this->faker->randomFloat(2, 0, $maxScore);
        
        return [
            'student_mapping_id' => StudentMapping::factory(),
            'activity_id' => Activity::factory(),
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => ($score / $maxScore) * 100,
            'term' => $this->faker->randomElement(['prelim', 'midterm', 'finals']),
            'created_by' => Faculty::factory(),
        ];
    }

    /**
     * Create a grade record with null score
     */
    public function withoutScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => null,
            'percentage' => null,
        ]);
    }

    /**
     * Create a grade record for a specific term
     */
    public function forTerm(string $term): static
    {
        return $this->state(fn (array $attributes) => [
            'term' => $term,
        ]);
    }

    /**
     * Create a grade record with perfect score
     */
    public function perfectScore(): static
    {
        return $this->state(function (array $attributes) {
            $maxScore = $attributes['max_score'] ?? 100;
            return [
                'score' => $maxScore,
                'percentage' => 100,
            ];
        });
    }

    /**
     * Create a grade record with failing score
     */
    public function failingScore(): static
    {
        return $this->state(function (array $attributes) {
            $maxScore = $attributes['max_score'] ?? 100;
            $score = $this->faker->randomFloat(2, 0, $maxScore * 0.6); // Below 60%
            return [
                'score' => $score,
                'percentage' => ($score / $maxScore) * 100,
            ];
        });
    }
}
