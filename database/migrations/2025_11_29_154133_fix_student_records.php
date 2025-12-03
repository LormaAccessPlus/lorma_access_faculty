<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing students and start fresh
        DB::table('student_subject')->truncate();
        DB::table('students')->truncate();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Create students with proper data
        $students = [
            [
                'student_number' => '2024001',
                'first_name' => 'Mark Joshua',
                'middle_name' => null,
                'last_name' => 'Navida',
                'email' => 'markjoshua.navida@lorma.edu',
                'google_user_id' => '109026432821101673146',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_number' => '2024002',
                'first_name' => 'Leandro Raphael',
                'middle_name' => null,
                'last_name' => 'Tenorio',
                'email' => 'leandroraphael.tenorio@lorma.edu',
                'google_user_id' => '112183648145585474934',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_number' => '2024003',
                'first_name' => 'Jasper Ace',
                'middle_name' => null,
                'last_name' => 'Lapitan',
                'email' => 'jasperace.lapitan@lorma.edu',
                'google_user_id' => '104227935388751864861',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        foreach ($students as $student) {
            $studentId = DB::table('students')->insertGetId($student);
            
            // Enroll in subject ID 2 (Introduction to Computer Science)
            DB::table('student_subject')->insert([
                'student_id' => $studentId,
                'subject_id' => 2,
                'status' => 'enrolled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse
    }
};
