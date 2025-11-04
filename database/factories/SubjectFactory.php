<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjectCodes = ['CS101', 'CS102', 'MATH101', 'PHYS101', 'ENG101', 'HIST101'];
        $sections = ['A', 'B', 'C', 'D'];
        $semesters = ['1st Semester', '2nd Semester', 'Summer'];
        $types = ['lecture_only', 'lecture_lab'];

        return [
            'school_subject_id' => $this->faker->optional()->numberBetween(1, 1000),
            'faculty_id' => Faculty::factory(),
            'subject_code' => $this->faker->randomElement($subjectCodes),
            'subject_name' => $this->faker->words(3, true),
            'section' => $this->faker->randomElement($sections),
            'type' => $this->faker->randomElement($types),
            'academic_year' => $this->faker->randomElement(['2023-2024', '2024-2025', '2025-2026']),
            'semester' => $this->faker->randomElement($semesters),
            'gcr_class_id' => $this->faker->optional()->uuid(),
        ];
    }

    /**
     * Indicate that the subject is lecture only.
     */
    public function lectureOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'lecture_only',
        ]);
    }

    /**
     * Indicate that the subject has both lecture and lab.
     */
    public function lectureLab(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'lecture_lab',
        ]);
    }

    /**
     * Indicate that the subject is connected to Google Classroom.
     */
    public function withGoogleClassroom(): static
    {
        return $this->state(fn (array $attributes) => [
            'gcr_class_id' => $this->faker->uuid(),
        ]);
    }

    /**
     * Indicate that the subject is linked to school database.
     */
    public function withSchoolDatabase(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_subject_id' => $this->faker->numberBetween(1, 1000),
        ]);
    }

    /**
     * Create a subject for the current semester.
     */
    public function currentSemester(): static
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Determine current semester based on month
        if ($currentMonth >= 6 && $currentMonth <= 10) {
            $semester = '1st Semester';
            $academicYear = $currentYear . '-' . ($currentYear + 1);
        } elseif ($currentMonth >= 11 || $currentMonth <= 3) {
            $semester = '2nd Semester';
            if ($currentMonth >= 11) {
                $academicYear = $currentYear . '-' . ($currentYear + 1);
            } else {
                $academicYear = ($currentYear - 1) . '-' . $currentYear;
            }
        } else {
            $semester = 'Summer';
            $academicYear = ($currentYear - 1) . '-' . $currentYear;
        }

        return $this->state(fn (array $attributes) => [
            'academic_year' => $academicYear,
            'semester' => $semester,
        ]);
    }
}
