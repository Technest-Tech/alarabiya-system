<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        
        $query = Student::with(['teacher.user', 'currentPackage']);
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$search}%")
                  ->orWhereHas('teacher.user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $students = $query->latest()->paginate(20)->withQueryString();
        
        return view('admin.students.index', compact('students', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $teachers = Teacher::with('user')->get();
        return view('admin.students.create', compact('teachers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => ['required','string','max:255'],
            'country_code' => ['required','string','size:2'],
            'whatsapp_number' => ['required', new \App\Rules\PhoneNumberFormat($request->country_code)],
            'package_hours_total' => ['required','integer','min:1'],
            'payment_method' => ['required','in:cash,bank_transfer,credit_card,paypal,other'],
            'currency' => ['required','string','size:3','in:AED,USD,GBP,INR,EGP,EUR,SAR,KWD,QAR,JPY,CAD,AUD'],
            'assigned_teacher_id' => ['nullable','exists:teachers,id'],
        ];
        
        // Support role cannot set hourly_rate
        if (!$request->user()->isSupport()) {
            $rules['hourly_rate'] = ['required','numeric','min:0'];
        }
        
        $validated = $request->validate($rules);
        
        // If support role, set hourly_rate to 0 (default value, accountant can update later)
        if ($request->user()->isSupport()) {
            $validated['hourly_rate'] = 0;
        }
        
        $student = Student::create($validated);
        return redirect()->route('students.index')->with('status','Student created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        return redirect()->route('students.edit', $student);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        $teachers = Teacher::with('user')->get();
        return view('admin.students.edit', compact('student','teachers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $rules = [
            'name' => ['required','string','max:255'],
            'country_code' => ['required','string','size:2'],
            'whatsapp_number' => ['required', new \App\Rules\PhoneNumberFormat($request->country_code)],
            'package_hours_total' => ['required','integer','min:1'],
            'payment_method' => ['required','in:cash,bank_transfer,credit_card,paypal,other'],
            'currency' => ['required','string','size:3','in:AED,USD,GBP,INR,EGP,EUR,SAR,KWD,QAR,JPY,CAD,AUD'],
            'assigned_teacher_id' => ['nullable','exists:teachers,id'],
            'status' => ['required','in:active,disabled'],
        ];
        
        // Support role cannot set hourly_rate
        if (!$request->user()->isSupport()) {
            $rules['hourly_rate'] = ['required','numeric','min:0'];
        }
        
        $validated = $request->validate($rules);
        
        // If support role, don't update hourly_rate (keep existing value)
        if ($request->user()->isSupport()) {
            unset($validated['hourly_rate']);
        }
        
        $oldPackageHours = $student->package_hours_total;
        $student->update($validated);
        
        // If package_hours_total changed, update current package
        if ($oldPackageHours != $validated['package_hours_total'] && $student->currentPackage) {
            $student->currentPackage->update(['package_hours' => $validated['package_hours_total']]);
        }
        
        return redirect()->route('students.index')->with('status','Student updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        if ($student->lessons()->exists()) {
            return back()->withErrors(['student' => 'Cannot delete a student with lessons.']);
        }
        $student->delete();
        return redirect()->route('students.index')->with('status','Student deleted.');
    }

    public function toggleStatus(Student $student)
    {
        $student->status = $student->status === 'active' ? 'disabled' : 'active';
        $student->save();
        return back()->with('status','Student status updated.');
    }
}
