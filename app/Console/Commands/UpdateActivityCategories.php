<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateActivityCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activities:update-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing activities to categorize quizzes vs activities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating activity categories...');
        
        // Count before
        $totalActivities = DB::table('activities')->count();
        $this->info("Total activities: {$totalActivities}");
        
        // Update activities that look like quizzes
        $quizCount = DB::table('activities')
            ->where(function($query) {
                $query->where('name', 'LIKE', '%quiz%')
                      ->orWhere('name', 'LIKE', '%Quiz%')
                      ->orWhere('name', 'LIKE', '%QUIZ%')
                      ->orWhere('name', 'LIKE', '%test%')
                      ->orWhere('name', 'LIKE', '%Test%')
                      ->orWhere('name', 'LIKE', '%exam%')
                      ->orWhere('name', 'LIKE', '%Exam%')
                      ->orWhere('name', 'REGEXP', 'Q[0-9]+')
                      ->orWhere('name', 'REGEXP', 'q[0-9]+');
            })
            ->update(['activity_category' => 'quiz']);
        
        $this->info("✓ Updated {$quizCount} activities to 'quiz' category");
        
        // Show summary
        $categories = DB::table('activities')
            ->select('activity_category', DB::raw('COUNT(*) as count'))
            ->groupBy('activity_category')
            ->get();
        
        $this->newLine();
        $this->info('Summary:');
        foreach ($categories as $category) {
            $this->line("  - {$category->activity_category}: {$category->count}");
        }
        
        // Show some examples
        $this->newLine();
        $this->info('Sample quizzes detected:');
        $quizzes = DB::table('activities')
            ->where('activity_category', 'quiz')
            ->limit(10)
            ->get(['id', 'name', 'type', 'term']);
        
        if ($quizzes->isEmpty()) {
            $this->warn('  No quizzes found. Make sure your activities have "quiz", "test", or "exam" in their names.');
        } else {
            foreach ($quizzes as $quiz) {
                $this->line("  - [{$quiz->id}] {$quiz->name} ({$quiz->type}, {$quiz->term})");
            }
        }
        
        $this->newLine();
        $this->info('✓ Done! Refresh your nursing matrix page to see the changes.');
        
        return Command::SUCCESS;
    }
}
