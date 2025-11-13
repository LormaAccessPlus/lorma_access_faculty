<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckAuth extends Command
{
    protected $signature = 'auth:check';
    protected $description = 'Check current authentication status and faculty info';

    public function handle()
    {
        $this->info('=== AUTHENTICATION STATUS ===');
        $this->newLine();

        // Check faculties in database
        $faculties = DB::table('faculties')->get(['id', 'name', 'email', 'school_faculty_id']);
        
        $this->info('Faculties in database:');
        $this->table(
            ['ID', 'Name', 'Email', 'School Faculty ID'],
            $faculties->map(function ($faculty) {
                return [
                    $faculty->id,
                    $faculty->name,
                    $faculty->email,
                    $faculty->school_faculty_id,
                ];
            })
        );

        $this->newLine();
        $this->info('To log in, visit: http://localhost:8000/auth/google');
        $this->info('Or use the login page: http://localhost:8000/login');

        return 0;
    }
}
