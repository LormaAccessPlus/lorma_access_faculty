<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentMapping>
 */
class StudentMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'school_student_id' => $this->faker->numberBetween(1, 1000),
            'gcr_student_id' => 'gcr_' . $this->faker->uuid(),
            'student_name' => $this->faker->name(),
            'student_email' => $this->faker->email(),
            'mapping_confidence' => $this->faker->randomFloat(2, 0, 1),
        ];
    }

    /**
     * Create a high confidence mapping
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'mapping_confidence' => $this->faker->randomFloat(2, 0.8, 1.0),
        ]);
    }

    /**
     * Create a low confidence mapping
     */
    public function lowConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'mapping_confidence' => $this->faker->randomFloat(2, 0.1, 0.6),
        ]);
    }

    /**
     * Create an unmapped student (no school student ID)
     */
    public function unmapped(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_student_id' => null,
            'mapping_confidence' => 0.0,
        ]);
    }
}
