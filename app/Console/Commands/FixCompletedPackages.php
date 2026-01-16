<?php

namespace App\Console\Commands;

use App\Models\StudentPackage;
use App\Models\Lesson;
use Illuminate\Console\Command;

class FixCompletedPackages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:fix-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix packages that should be marked as completed but are not';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for packages that should be marked as completed...');
        
        $activePackages = StudentPackage::where('status', 'active')->get();
        $fixedCount = 0;
        
        foreach ($activePackages as $package) {
            $shouldComplete = false;
            $reason = '';
            
            // Check if package is exhausted
            if ($package->isExhausted()) {
                $shouldComplete = true;
                $reason = 'exhausted hours';
            }
            
            // Check if package has pending lessons
            $hasPendingLessons = Lesson::where('student_package_id', $package->id)
                ->where('is_pending', true)
                ->exists();
            
            if ($hasPendingLessons) {
                $shouldComplete = true;
                $reason = 'has pending lessons';
            }
            
            if ($shouldComplete) {
                $package->markAsCompleted();
                $fixedCount++;
                
                $this->line("✓ Fixed package #{$package->id} for {$package->student->name} ({$reason})");
            }
        }
        
        if ($fixedCount === 0) {
            $this->info('No packages needed fixing.');
        } else {
            $this->info("Fixed {$fixedCount} package(s).");
        }
        
        return 0;
    }
}
