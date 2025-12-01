<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teachers = Teacher::with('user')->paginate(20);
        return view('admin.teachers.index', compact('teachers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.teachers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6'],
            'country_code' => ['required','string','size:2'],
            'whatsapp_number' => ['required', new \App\Rules\PhoneNumberFormat($request->country_code)],
        ];
        
        // Support role cannot set hourly_rate
        if (!$request->user()->isSupport()) {
            $rules['hourly_rate'] = ['required','numeric','min:0'];
            $rules['currency'] = ['required','string','in:EGP,USD'];
        }
        
        $validated = $request->validate($rules);
        
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
            'country_code' => $validated['country_code'],
            'whatsapp_number' => $validated['whatsapp_number'],
        ];
        
        // If support role, don't include hourly_rate
        if (!$request->user()->isSupport()) {
            $userData['hourly_rate'] = $validated['hourly_rate'];
        }
        
        $user = User::create($userData);
        $teacherData = ['user_id' => $user->id];
        if (!$request->user()->isSupport()) {
            $teacherData['currency'] = $validated['currency'];
        }
        Teacher::create($teacherData);
        return redirect()->route('teachers.index')->with('status','Teacher created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Teacher $teacher)
    {
        $teacher->load('user');
        return view('admin.teachers.edit', compact('teacher'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $rules = [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email,'.$teacher->user_id],
            'password' => ['nullable','string','min:6'],
            'country_code' => ['required','string','size:2'],
            'whatsapp_number' => ['required', new \App\Rules\PhoneNumberFormat($request->country_code)],
        ];
        
        // Support role cannot set hourly_rate
        if (!$request->user()->isSupport()) {
            $rules['hourly_rate'] = ['required','numeric','min:0'];
            $rules['currency'] = ['required','string','in:EGP,USD'];
        }
        
        $validated = $request->validate($rules);
        
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'country_code' => $validated['country_code'],
            'whatsapp_number' => $validated['whatsapp_number'],
        ];
        
        // If support role, don't include hourly_rate
        if (!$request->user()->isSupport()) {
            $userData['hourly_rate'] = $validated['hourly_rate'];
        }
        
        $teacher->user->fill($userData);
        if (!empty($validated['password'])) {
            $teacher->user->password = Hash::make($validated['password']);
        }
        $teacher->user->save();
        
        // Update currency if not support role
        if (!$request->user()->isSupport()) {
            $teacher->currency = $validated['currency'];
            $teacher->save();
        }
        return redirect()->route('teachers.index')->with('status','Teacher updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher)
    {
        if ($teacher->students()->exists()) {
            return back()->withErrors(['teacher' => 'Cannot delete teacher with assigned students.']);
        }
        $user = $teacher->user;
        $teacher->delete();
        $user->delete();
        return redirect()->route('teachers.index')->with('status','Teacher deleted.');
    }
}
