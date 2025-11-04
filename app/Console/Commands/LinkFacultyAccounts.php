<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Faculty;
use App\Services\SchoolDatabaseService;

class LinkFacultyAccounts extends Command
{
    protected $signature = 'faculty:link-accounts {--auto : Automatically link accounts with matching emails}';
    protected $description = 'Link Google faculty accounts with school database faculty accounts';

    public function handle(SchoolDatabaseService $schoolService)
    {
        $this->info('Faculty Account Linking Tool');
        $this->info('================================');
        
        if ($this->option('auto')) {
            return $this->autoLinkAccounts($schoolService);
        }
        
        return $this->interactiveLinking($schoolService);
    }
    
    private function autoLinkAccounts(SchoolDatabaseService $schoolService): int
    {
        $this->info('Auto-linking accounts with matching emails...');
        
        $unlinkedFaculty = Faculty::whereNull('school_faculty_id')->get();
        $linkedCount = 0;
        
        foreach ($unlinkedFaculty as $faculty) {
            $schoolFaculty = $schoolService->findFacultyByEmail($faculty->email);
            
            if ($schoolFaculty) {
                $faculty->update(['school_faculty_id' => $schoolFaculty->faculty_id]);
                $this->info("✓ Linked {$faculty->email} (ID: {$faculty->id}) → School Faculty ID: {$schoolFaculty->faculty_id}");
                $linkedCount++;
            } else {
                $this->warn("✗ No school database match for {$faculty->email} (ID: {$faculty->id})");
            }
        }
        
        $this->info("\nAuto-linking complete: {$linkedCount} accounts linked.");
        return 0;
    }
    
    private function interactiveLinking(SchoolDatabaseService $schoolService): int
    {
        // Show current status
        $this->showCurrentStatus($schoolService);
        
        // Get unlinked faculty
        $unlinkedFaculty = Faculty::whereNull('school_faculty_id')->get();
        
        if ($unlinkedFaculty->isEmpty()) {
            $this->info('All faculty accounts are already linked!');
            return 0;
        }
        
        $this->info("\nUnlinked Faculty Accounts:");
        foreach ($unlinkedFaculty as $faculty) {
            $this->line("  {$faculty->id}. {$faculty->name} ({$faculty->email})");
        }
        
        // Get school faculty options
        $schoolFaculty = $schoolService->executeReadOnlyQuery(
            'SELECT faculty_id, first_name, last_name, email FROM faculty WHERE status = "active"'
        );
        
        $this->info("\nAvailable School Database Faculty:");
        foreach ($schoolFaculty as $sf) {
            $this->line("  {$sf->faculty_id}. {$sf->first_name} {$sf->last_name} ({$sf->email})");
        }
        
        // Interactive linking
        while (true) {
            $facultyId = $this->ask('Enter Google Faculty ID to link (or "quit" to exit)');
            
            if (strtolower($facultyId) === 'quit') {
                break;
            }
            
            $faculty = Faculty::find($facultyId);
            if (!$faculty) {
                $this->error('Faculty not found!');
                continue;
            }
            
            if ($faculty->school_faculty_id) {
                $this->warn('This faculty is already linked!');
                continue;
            }
            
            $schoolFacultyId = $this->ask("Enter School Faculty ID to link with {$faculty->name} ({$faculty->email})");
            
            $schoolFac = $schoolFaculty->firstWhere('faculty_id', $schoolFacultyId);
            if (!$schoolFac) {
                $this->error('School faculty not found!');
                continue;
            }
            
            if ($this->confirm("Link {$faculty->name} ({$faculty->email}) with {$schoolFac->first_name} {$schoolFac->last_name} ({$schoolFac->email})?")) {
                $faculty->update(['school_faculty_id' => $schoolFacultyId]);
                $this->info('✓ Accounts linked successfully!');
            }
        }
        
        return 0;
    }
    
    private function showCurrentStatus(SchoolDatabaseService $schoolService): void
    {
        $this->info('Current Faculty Account Status:');
        $this->info('==============================');
        
        $allFaculty = Faculty::all();
        
        foreach ($allFaculty as $faculty) {
            $status = $faculty->school_faculty_id ? '✓ Linked' : '✗ Unlinked';
            $schoolInfo = '';
            
            if ($faculty->school_faculty_id) {
                $schoolFaculty = $schoolService->executeReadOnlyQuery(
                    'SELECT first_name, last_name, email FROM faculty WHERE faculty_id = ?',
                    [$faculty->school_faculty_id]
                )->first();
                
                if ($schoolFaculty) {
                    $schoolInfo = " → {$schoolFaculty->first_name} {$schoolFaculty->last_name} ({$schoolFaculty->email})";
                }
            }
            
            $this->line("  {$faculty->id}. {$faculty->name} ({$faculty->email}) - {$status}{$schoolInfo}");
        }
        
        $linkedCount = $allFaculty->whereNotNull('school_faculty_id')->count();
        $totalCount = $allFaculty->count();
        
        $this->info("\nSummary: {$linkedCount}/{$totalCount} accounts linked");
    }
}