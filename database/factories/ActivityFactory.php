<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Subject;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
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
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['lecture', 'lab']),
            'term' => $this->faker->randomElement(['prelim', 'midterm', 'finals']),
            'max_score' => $this->faker->randomFloat(2, 10, 100),
            'weight' => $this->faker->optional()->randomFloat(2, 5, 50),
            'gcr_assignment_id' => $this->faker->optional()->uuid(),
        ];
    }

    /**
     * Indicate that the activity is from Google Classroom.
     */
    public function fromGoogleClassroom(): static
    {
        return $this->state(fn (array $attributes) => [
            'gcr_assignment_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that the activity is a lecture activity.
     */
    public function lecture(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'lecture',
        ]);
    }

    /**
     * Indicate that the activity is a lab activity.
     */
    public function lab(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'lab',
        ]);
    }

    /**
     * Indicate that the activity is for prelims.
     */
    public function prelim(): static
    {
        return $this->state(fn (array $attributes) => [
            'term' => 'prelim',
        ]);
    }

    /**
     * Indicate that the activity is for midterm.
     */
    public function midterm(): static
    {
        return $this->state(fn (array $attributes) => [
            'term' => 'midterm',
        ]);
    }

    /**
     * Indicate that the activity is for finals.
     */
    public function finals(): static
    {
        return $this->state(fn (array $attributes) => [
            'term' => 'finals',
        ]);
    }
}
