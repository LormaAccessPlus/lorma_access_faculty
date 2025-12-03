<?php

namespace App\Console\Commands;

use App\Models\Subject;
use Illuminate\Console\Command;

class SetSubjectMatrixType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subject:set-matrix-type {subject_id} {matrix_type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the matrix type for a subject (nursing, general-education, zero-based, customized)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subjectId = $this->argument('subject_id');
        $matrixType = $this->argument('matrix_type');

        // Validate matrix type
        $validTypes = ['nursing', 'general-education', 'zero-based', 'customized'];
        if (!in_array($matrixType, $validTypes)) {
            $this->error("Invalid matrix type! Must be one of: " . implode(', ', $validTypes));
            return 1;
        }

        // Find the subject
        $subject = Subject::find($subjectId);

        if (!$subject) {
            $this->error("Subject with ID {$subjectId} not found!");
            return 1;
        }

        // Show current info
        $this->info("Subject: {$subject->subject_code} - {$subject->subject_name}");
        $this->info("Section: {$subject->section}");
        $this->info("Current matrix type: " . ($subject->matrix_type ?? 'NULL'));

        // Confirm the change
        if (!$this->confirm("Do you want to change the matrix type to '{$matrixType}'?", true)) {
            $this->info("Operation cancelled.");
            return 0;
        }

        // Update the matrix type
        $subject->matrix_type = $matrixType;
        $subject->save();

        $this->info("✓ Successfully updated matrix type to '{$matrixType}'");
        $this->info("\nYou can now refresh your page and the grades will calculate correctly!");

        return 0;
    }
}
