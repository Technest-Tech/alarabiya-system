<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentPackage;
use App\Services\PackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PackageNotificationsController extends Controller
{
    public function __construct(
        private PackageService $packageService
    ) {
    }

    /**
     * Display all students with completed packages
     */
    public function index(): View
    {
        $completedPackages = StudentPackage::with(['student.teacher.user'])
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->paginate(20);

        return view('admin.package-notifications.index', [
            'completedPackages' => $completedPackages,
        ]);
    }

    /**
     * Mark package as paid and renew
     */
    public function markAsPaid(StudentPackage $package): RedirectResponse
    {
        $this->packageService->renewPackage($package);

        return redirect()->route('admin.package-notifications.index')
            ->with('status', 'Package marked as paid and renewed successfully.');
    }
}
