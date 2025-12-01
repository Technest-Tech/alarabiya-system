<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupportAttendanceRequest;
use App\Http\Requests\Admin\UpdateFinishTimeRequest;
use App\Models\SupportAttendance;
use App\Models\SupportName;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = [
            'date_from' => $request->date('date_from') ?? Carbon::today(),
            'date_to' => $request->date('date_to') ?? Carbon::today(),
            'status' => $request->string('status')->toString(),
        ];

        $query = SupportAttendance::with(['createdBy', 'supportName'])
            ->whereBetween('date', [$filters['date_from'], $filters['date_to']])
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->latest('date')
            ->latest('created_at');

        $attendances = $query->paginate(15)->withQueryString();

        $supportNames = SupportName::where('is_active', true)->orderBy('name')->get();

        return view('admin.support-attendances.index', [
            'pageTitle' => 'Support Attendance',
            'attendances' => $attendances,
            'filters' => $filters,
            'supportNames' => $supportNames,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupportAttendanceRequest $request): RedirectResponse
    {
        $deviceType = $this->detectDeviceType($request);

        SupportAttendance::create([
            ...$request->validated(),
            'device_type' => $deviceType,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('support-attendances.index')
            ->with('status', 'Attendance start time recorded successfully. Please add finish time when done.');
    }

    /**
     * Update finish time for an attendance record.
     */
    public function updateFinishTime(UpdateFinishTimeRequest $request, SupportAttendance $supportAttendance): RedirectResponse
    {
        $supportAttendance->update([
            'to_time' => $request->validated()['to_time'],
        ]);

        return redirect()->route('support-attendances.index')
            ->with('status', 'Finish time updated successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSupportAttendanceRequest $request, SupportAttendance $supportAttendance): RedirectResponse
    {
        $supportAttendance->update($request->validated());

        return redirect()->route('support-attendances.index')
            ->with('status', 'Attendance updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupportAttendance $supportAttendance): RedirectResponse
    {
        $supportAttendance->delete();

        return redirect()->route('support-attendances.index')
            ->with('status', 'Attendance deleted successfully.');
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent() ?? '';

        // Common mobile device patterns
        $mobilePatterns = [
            '/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i',
            '/Windows Phone/i',
            '/webOS/i',
        ];

        foreach ($mobilePatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return 'phone';
            }
        }

        return 'pc';
    }
}
