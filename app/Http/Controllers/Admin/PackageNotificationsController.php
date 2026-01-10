<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentPackage;
use App\Services\PackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): View
    {
        $search = $request->get('search', '');
        
        $query = StudentPackage::with(['student.teacher.user'])
            ->where('status', 'completed');
        
        if ($search) {
            $query->whereHas('student', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }
        
        $completedPackages = $query->orderBy('completed_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.package-notifications.index', [
            'completedPackages' => $completedPackages,
            'search' => $search,
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
