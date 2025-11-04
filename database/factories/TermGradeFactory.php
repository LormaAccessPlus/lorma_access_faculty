<?php

namespace Database\Factories;

use App\Models\StudentMapping;
use App\Models\Subject;
use App\Models\TermGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TermGrade>
 */
class TermGradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_mapping_id' => StudentMapping::factory(),
            'subject_id' => Subject::factory(),
            'term' => $this->faker->randomElement(['prelim', 'midterm', 'finals']),
            'class_standing' => $this->faker->randomFloat(2, 60, 100),
            'exam_score' => $this->faker->randomFloat(2, 0, 100),
            'exam_grade' => $this->faker->randomFloat(2, 60, 100),
            'term_grade' => $this->faker->randomFloat(2, 60, 100),
            'computation_config' => [
                'class_standing_weight' => 0.4,
                'exam_weight' => 0.6,
                'class_standing_formula' => 'percentage'
            ]
        ];
    }

    /**
     * Indicate that the term grade is for prelim term.
     */
    public function prelim(): static
    {
        return $this->state(fn (array $attributes) => [
            'term' => 'prelim',
            'computation_config' => [
                'class_standing_weight' => 0.4,
                'exam_weight' => 0.6,
                'class_standing_formula' => 'percentage'
            ]
        ]);
    }

    /**
     * Indicate that the term grade is for midterm term.
     */
    public function midterm(): static
    {
        return $this->state(fn (array $attributes) => [
            'term' => 'midterm',
            'computation_config' => [
                'class_standing_weight' => 0.4,
                'exam_weight' => 0.6,
                'class_standing_formula' => 'transmuted',
                'exam_formula' => 'transmuted'
            ]
        ]);
    }

    /**
     * Indicate that the term grade is for finals term.
     */
    public function finals(): static
    {
        return $this->state(fn (array $attributes) => [
            'term' => 'finals',
            'computation_config' => [
                'class_standing_weight' => 0.4,
                'exam_weight' => 0.6,
                'class_standing_formula' => 'transmuted',
                'exam_formula' => 'transmuted'
            ]
        ]);
    }

    /**
     * Indicate that the term grade has no exam score.
     */
    public function withoutExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'exam_score' => null,
            'exam_grade' => 0.0,
        ]);
    }
}