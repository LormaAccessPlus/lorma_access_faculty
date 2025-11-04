<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DashboardDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo faculty member
        $faculty = \App\Models\Faculty::firstOrCreate(
            ['email' => 'leandroraphael.tenorio@lorma.edu'],
            [
                'name' => 'Leandro Raphael Tenorio',
                'google_id' => 'demo_google_id',
                'school_faculty_id' => 1,
                'access_token' => 'demo_token',
                'refresh_token' => 'demo_refresh',
                'token_expires_at' => now()->addHours(1)
            ]
        );

        // Create some demo subjects
        $subjects = [
            [
                'subject_code' => 'CS101',
                'subject_name' => 'Introduction to Computer Science',
                'section' => 'A',
                'type' => 'lecture_lab',
                'academic_year' => '2024-2025',
                'semester' => '1st',
                'gcr_class_id' => 'demo_gcr_1'
            ],
            [
                'subject_code' => 'CS102',
                'subject_name' => 'Programming Fundamentals',
                'section' => 'B',
                'type' => 'lecture_lab',
                'academic_year' => '2024-2025',
                'semester' => '1st',
                'gcr_class_id' => null
            ],
            [
                'subject_code' => 'CS201',
                'subject_name' => 'Data Structures and Algorithms',
                'section' => 'A',
                'type' => 'lecture_only',
                'academic_year' => '2024-2025',
                'semester' => '1st',
                'gcr_class_id' => 'demo_gcr_2'
            ]
        ];

        foreach ($subjects as $subjectData) {
            $subject = \App\Models\Subject::firstOrCreate(
                [
                    'faculty_id' => $faculty->id,
                    'subject_code' => $subjectData['subject_code'],
                    'section' => $subjectData['section']
                ],
                array_merge($subjectData, ['faculty_id' => $faculty->id, 'school_subject_id' => rand(1, 100)])
            );

            // Create some activities for each subject
            $activities = [
                ['name' => 'Quiz 1', 'type' => 'lecture', 'term' => 'prelim', 'max_score' => 50],
                ['name' => 'Quiz 2', 'type' => 'lecture', 'term' => 'prelim', 'max_score' => 50],
                ['name' => 'Midterm Exam', 'type' => 'lecture', 'term' => 'midterm', 'max_score' => 100],
                ['name' => 'Final Exam', 'type' => 'lecture', 'term' => 'finals', 'max_score' => 100],
            ];

            if ($subject->type === 'lecture_lab') {
                $activities = array_merge($activities, [
                    ['name' => 'Lab Exercise 1', 'type' => 'lab', 'term' => 'prelim', 'max_score' => 25],
                    ['name' => 'Lab Exercise 2', 'type' => 'lab', 'term' => 'midterm', 'max_score' => 25],
                    ['name' => 'Lab Final Project', 'type' => 'lab', 'term' => 'finals', 'max_score' => 50],
                ]);
            }

            foreach ($activities as $activityData) {
                \App\Models\Activity::firstOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'name' => $activityData['name'],
                        'type' => $activityData['type'],
                        'term' => $activityData['term']
                    ],
                    array_merge($activityData, [
                        'subject_id' => $subject->id,
                        'weight' => rand(10, 30)
                    ])
                );
            }

            // Create some student mappings
            for ($i = 1; $i <= 5; $i++) {
                \App\Models\StudentMapping::firstOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'gcr_student_id' => "demo_student_{$subject->id}_{$i}"
                    ],
                    [
                        'student_name' => "Student {$i}",
                        'student_email' => "student{$i}@lorma.edu",
                        'school_student_id' => rand(1, 3) <= 2 ? rand(1000, 9999) : null, // 2/3 chance of being mapped
                        'mapping_confidence' => rand(80, 100) / 100
                    ]
                );
            }
        }

        $this->command->info('Demo data created successfully!');
        $this->command->info("Faculty: {$faculty->name} ({$faculty->email})");
        $this->command->info("Subjects: " . \App\Models\Subject::where('faculty_id', $faculty->id)->count());
        $this->command->info("Activities: " . \App\Models\Activity::whereHas('subject', function($q) use ($faculty) {
            $q->where('faculty_id', $faculty->id);
        })->count());
    }
}
