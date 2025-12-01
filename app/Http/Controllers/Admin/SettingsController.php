<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(): View
    {
        $supportNames = SupportName::orderBy('name')->get();

        return view('admin.settings.index', [
            'pageTitle' => 'Settings',
            'supportNames' => $supportNames,
        ]);
    }

    /**
     * Store a new support name.
     */
    public function storeSupportName(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:support_names,name'],
        ]);

        SupportName::create($validated);

        return redirect()->route('admin.settings.index')
            ->with('status', 'Support name created successfully.');
    }

    /**
     * Update a support name.
     */
    public function updateSupportName(Request $request, SupportName $supportName): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:support_names,name,' . $supportName->id],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $supportName->update($validated);

        return redirect()->route('admin.settings.index')
            ->with('status', 'Support name updated successfully.');
    }

    /**
     * Delete a support name.
     */
    public function destroySupportName(SupportName $supportName): RedirectResponse
    {
        $supportName->delete();

        return redirect()->route('admin.settings.index')
            ->with('status', 'Support name deleted successfully.');
    }
}
