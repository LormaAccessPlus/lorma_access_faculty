<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faculty>
 */
class FacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_id' => $this->faker->unique()->numerify('##########'),
            'email' => $this->faker->unique()->userName() . '@lorma.edu',
            'name' => $this->faker->name(),
            'school_faculty_id' => $this->faker->optional()->numberBetween(1, 1000),
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->optional()->sha256(),
        ];
    }

    /**
     * Indicate that the faculty is linked to school database.
     */
    public function withSchoolDatabase(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_faculty_id' => $this->faker->numberBetween(1, 1000),
        ]);
    }
}
