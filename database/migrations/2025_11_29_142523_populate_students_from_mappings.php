<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all unique students from student_mappings
        $mappings = DB::table('student_mappings')
            ->whereNotNull('student_email')
            ->select('student_name', 'student_email', 'subject_id')
            ->get();

        $processedEmails = [];

        foreach ($mappings as $mapping) {
            // Skip if we've already processed this email
            if (in_array(strtolower($mapping->student_email), $processedEmails)) {
                continue;
            }

            // Parse the student name
            $nameParts = $this->parseStudentName($mapping->student_name);

            // Create or get the student
            $student = DB::table('students')->where('email', $mapping->student_email)->first();

            if (!$student) {
                $studentId = DB::table('students')->insertGetId([
                    'student_number' => $this->generateStudentNumber($mapping->student_email),
                    'first_name' => $nameParts['first_name'],
                    'middle_name' => $nameParts['middle_name'],
                    'last_name' => $nameParts['last_name'],
                    'email' => $mapping->student_email,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $studentId = $student->id;
            }

            // Link student to subject in student_subject pivot table
            $exists = DB::table('student_subject')
                ->where('student_id', $studentId)
                ->where('subject_id', $mapping->subject_id)
                ->exists();

            if (!$exists) {
                DB::table('student_subject')->insert([
                    'student_id' => $studentId,
                    'subject_id' => $mapping->subject_id,
                    'status' => 'enrolled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update student_mappings to reference the new student_id
            DB::table('student_mappings')
                ->where('student_email', $mapping->student_email)
                ->where('subject_id', $mapping->subject_id)
                ->update(['student_id' => $studentId]);

            $processedEmails[] = strtolower($mapping->student_email);
        }
    }

    /**
     * Parse student name into first, middle, and last name
     */
    private function parseStudentName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName));
        $count = count($parts);

        if ($count === 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[0],
            ];
        } elseif ($count === 2) {
            return [
                'first_name' => $parts[0],
                'middle_name' => null,
                'last_name' => $parts[1],
            ];
        } else {
            // Assume: First Middle(s) Last
            return [
                'first_name' => $parts[0],
                'middle_name' => implode(' ', array_slice($parts, 1, -1)),
                'last_name' => $parts[$count - 1],
            ];
        }
    }

    /**
     * Generate a student number from email
     */
    private function generateStudentNumber(string $email): string
    {
        // Extract username part before @
        $username = explode('@', $email)[0];
        
        // If it looks like a student number (numeric), use it
        if (preg_match('/\d{4,}/', $username, $matches)) {
            return $matches[0];
        }
        
        // Otherwise generate a temporary one
        return 'TEMP' . str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the student_id from student_mappings
        DB::table('student_mappings')->update(['student_id' => null]);
        
        // Clear student_subject pivot table
        DB::table('student_subject')->truncate();
        
        // Clear students table
        DB::table('students')->truncate();
    }
};
