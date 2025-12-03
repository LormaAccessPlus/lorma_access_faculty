<?php

namespace App\Console\Commands;

use App\Models\Subject;
use App\Models\GradingConfig;
use Illuminate\Console\Command;

class SetGeneralEducationFormula extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grades:set-gened-formula {subject_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set General Education formula (66.67/33.33 transmuted) for a subject';

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

        $this->info("Setting General Education formula for: {$subject->subject_code} - {$subject->subject_name}");

        $terms = ['prelim', 'midterm', 'finals'];

        foreach ($terms as $term) {
            $this->info("\nProcessing {$term} term...");
            
            // Get or create grading config
            $gradingConfig = GradingConfig::where('subject_id', $subject->id)
                ->where('term', $term)
                ->first();

            if ($gradingConfig) {
                // Update existing config
                $gradingConfig->update([
                    'class_standing_weight' => 66.67,
                    'exam_weight' => 33.33,
                    'formula_config' => [
                        'type' => 'transmuted',
                        'components' => []
                    ]
                ]);
                $this->info("  ✓ Updated existing config");
            } else {
                // Create new config
                GradingConfig::create([
                    'subject_id' => $subject->id,
                    'term' => $term,
                    'class_standing_weight' => 66.67,
                    'exam_weight' => 33.33,
                    'formula_config' => [
                        'type' => 'transmuted',
                        'components' => []
                    ]
                ]);
                $this->info("  ✓ Created new config");
            }

            $this->info("  Formula: 66.67% CS + 33.33% Exam (transmuted)");
        }

        $this->info("\n✓ Formula configuration complete!");
        $this->info("\nNow run: php artisan grades:recalculate {$subjectId}");
        
        return 0;
    }
}
