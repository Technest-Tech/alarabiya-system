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

    public function index(): View
    {
        $adjustments = TimezoneAdjustment::with('appliedBy')
            ->latest('applied_at')
            ->paginate(20);

        return view('admin.timezone-adjustments.index', [
            'pageTitle'       => 'Timezone Adjustments',
            'adjustments'     => $adjustments,
            'timezoneOptions' => config('timetables.timezones', []),
        ]);
    }

    public function store(StoreTimezoneAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->service->applyAdjustment(
            $data['timezone'],
            $data['adjustment_hours'],
            auth()->id(),
            $data['target']
        );

        $targetLabel = $data['target'] === 'teacher' ? 'Teacher' : 'Student';

        return redirect()->route('timezone-adjustments.index')
            ->with('status', "{$targetLabel} timezone adjustment applied successfully.");
    }
}
