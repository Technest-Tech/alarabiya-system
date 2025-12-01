<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\PackageService;
use Illuminate\Console\Command;

class RecalculateStudentPackages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packages:recalculate {--student-id= : Recalculate for specific student ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all student packages and hours taken';

    public function __construct(
        private PackageService $packageService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $studentId = $this->option('student-id');
        
        if ($studentId) {
            $students = Student::where('id', $studentId)->get();
        } else {
            $students = Student::with('currentPackage')->get();
        }

        $this->info("Recalculating packages for {$students->count()} student(s)...");

        $bar = $this->output->createProgressBar($students->count());
        $bar->start();

        foreach ($students as $student) {
            // Recalculate student's hours taken
            $student->recalculateHoursTaken();

            // Recalculate all packages for this student
            foreach ($student->packages as $package) {
                // Sync package_hours with student's package_hours_total
                if ($package->package_hours != $student->package_hours_total) {
                    $package->update(['package_hours' => $student->package_hours_total]);
                }
                
                // Recalculate package lessons
                $this->packageService->recalculatePackageLessons($package);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Recalculated packages for {$students->count()} student(s).");
        
        return Command::SUCCESS;
    }
}
