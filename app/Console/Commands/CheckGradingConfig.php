<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Models\GradingConfig;
use Illuminate\Console\Command;

class CheckGradingConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grades:check-config {subject_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the grading configuration for a subject';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subjectId = $this->argument('subject_id');

        $subject = Subject::find($subjectId);

        if (!$subject) {
            $this->error("Subject with ID {$subjectId} not found!");
            return 1;
        }

        $this->info("Checking grading configuration for: {$subject->subject_code} - {$subject->subject_name}");
        $this->info("Subject ID: {$subject->id}\n");

        $terms = ['prelim', 'midterm', 'finals'];

        foreach ($terms as $term) {
            $this->info("=== {$term} Term ===");
            
            $gradingConfig = GradingConfig::where('subject_id', $subject->id)
                ->where('term', $term)
                ->first();

            if ($gradingConfig) {
                $this->info("Config ID: {$gradingConfig->id}");
                $this->info("Class Standing Weight: {$gradingConfig->class_standing_weight}%");
                $this->info("Exam Weight: {$gradingConfig->exam_weight}%");
                
                $formulaConfig = $gradingConfig->formula_config;
                if ($formulaConfig && isset($formulaConfig['type'])) {
                    $this->info("Formula Type: {$formulaConfig['type']}");
                } else {
                    $this->warn("Formula Type: NOT SET (will default to 'percentage')");
                }
                
                $this->info("Created: {$gradingConfig->created_at}");
                $this->info("Updated: {$gradingConfig->updated_at}");
            } else {
                $this->warn("No grading config found for {$term} term");
            }
            
            $this->info("");
        }

        $this->info("\n=== Expected for General Education ===");
        $this->info("Class Standing Weight: 66.67%");
        $this->info("Exam Weight: 33.33%");
        $this->info("Formula Type: transmuted");
        
        return 0;
    }
}
