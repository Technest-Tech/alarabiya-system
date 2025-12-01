<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTimezoneAdjustmentRequest;
use App\Models\TimezoneAdjustment;
use App\Services\TimezoneAdjustmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TimezoneAdjustmentController extends Controller
{
    public function __construct(
        private readonly TimezoneAdjustmentService $service
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $adjustments = TimezoneAdjustment::with('appliedBy')
            ->latest('applied_at')
            ->paginate(15);

        return view('admin.timezone-adjustments.index', [
            'pageTitle' => 'Timezone Adjustments',
            'adjustments' => $adjustments,
            'timezoneOptions' => config('timetables.timezones', []),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimezoneAdjustmentRequest $request): RedirectResponse
    {
        $this->service->applyAdjustment(
            $request->validated()['timezone'],
            $request->validated()['adjustment_hours'],
            auth()->id()
        );

        return redirect()->route('timezone-adjustments.index')
            ->with('status', 'Timezone adjustment applied successfully.');
    }
}
