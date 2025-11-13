<?php

namespace App\Console\Commands;

use App\Models\Faculty;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class DevLogin extends Command
{
    protected $signature = 'dev:login {faculty_id?}';
    protected $description = 'Development only: Create a login session for testing';

    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('This command is only available in development!');
            return 1;
        }

        $facultyId = $this->argument('faculty_id') ?? 1;

        $faculty = Faculty::find($facultyId);

        if (!$faculty) {
            $this->error("Faculty with ID {$facultyId} not found!");
            
            $this->info("\nAvailable faculties:");
            $faculties = Faculty::all(['id', 'name', 'email']);
            $this->table(['ID', 'Name', 'Email'], $faculties->map(fn($f) => [$f->id, $f->name, $f->email]));
            
            return 1;
        }

        $this->info("Creating development session for:");
        $this->info("  Name: {$faculty->name}");
        $this->info("  Email: {$faculty->email}");
        $this->newLine();

        // Create a session file manually
        $sessionId = \Illuminate\Support\Str::random(40);
        $sessionData = [
            'faculty_id' => $faculty->id,
            '_token' => \Illuminate\Support\Str::random(40),
        ];

        // Store in Laravel's session
        $sessionPath = storage_path('framework/sessions');
        if (!file_exists($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }

        $sessionFile = $sessionPath . '/' . $sessionId;
        file_put_contents($sessionFile, serialize($sessionData));

        $this->info("✓ Session created!");
        $this->newLine();
        $this->info("To use this session in your browser:");
        $this->info("1. Open your browser's Developer Tools (F12)");
        $this->info("2. Go to Application/Storage > Cookies");
        $this->info("3. Add a cookie named: laravel_session");
        $this->info("4. Set the value to: {$sessionId}");
        $this->info("5. Refresh the page");
        $this->newLine();
        $this->warn("OR use the simpler method below:");
        $this->newLine();

        return 0;
    }
}
