<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Admin\FamilyController as AdminFamilyController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\TimetableController as AdminTimetableController;
use App\Http\Controllers\Admin\TimetableCalendarController as AdminTimetableCalendarController;
use App\Http\Controllers\Admin\TeacherSalaryController as AdminTeacherSalaryController;
use App\Http\Controllers\Admin\TodayLessonsController as AdminTodayLessonsController;
use App\Http\Controllers\Admin\TimezoneAdjustmentController as AdminTimezoneAdjustmentController;
use App\Http\Controllers\Admin\SupportAttendanceController as AdminSupportAttendanceController;
use App\Http\Controllers\Admin\PackageNotificationsController as AdminPackageNotificationsController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\FinancialOverviewController as AdminFinancialOverviewController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\LessonController as TeacherLessonController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Shared routes accessible by admin, support, and accountant
Route::middleware(['auth','role:admin|support|accountant'])->prefix('admin')->group(function () {
    Route::get('/', function(){ return view('admin.dashboard'); })->name('admin.dashboard');
    
    // Students - accessible by admin, support, and accountant (with different permissions handled in controller)
    Route::resource('students', AdminStudentController::class)->names([
        'index' => 'students.index',
        'create' => 'students.create',
        'store' => 'students.store',
        'show' => 'students.show',
        'edit' => 'students.edit',
        'update' => 'students.update',
        'destroy' => 'students.destroy',
    ]);
    Route::patch('students/{student}/toggle', [AdminStudentController::class, 'toggleStatus'])->name('admin.students.toggle');
    
    // Teachers - accessible by admin, support, and accountant (with different permissions handled in controller)
    Route::resource('teachers', AdminTeacherController::class)->names([
        'index' => 'teachers.index',
        'create' => 'teachers.create',
        'store' => 'teachers.store',
        'show' => 'teachers.show',
        'edit' => 'teachers.edit',
        'update' => 'teachers.update',
        'destroy' => 'teachers.destroy',
    ]);
    
    // Lessons - accessible by admin and support
    Route::middleware(['role:admin|support'])->group(function () {
        Route::resource('lessons', AdminLessonController::class)->only(['index','create','store','edit','update','destroy'])->names([
            'index' => 'lessons.index',
            'create' => 'lessons.create',
            'store' => 'lessons.store',
            'edit' => 'lessons.edit',
            'update' => 'lessons.update',
            'destroy' => 'lessons.destroy',
        ]);
    });
});

// Admin-only routes
Route::middleware(['auth','role:admin'])->prefix('admin')->group(function () {
    Route::resource('families', AdminFamilyController::class)->names([
        'index' => 'admin.families.index',
        'create' => 'admin.families.create',
        'store' => 'admin.families.store',
        'show' => 'admin.families.show',
        'edit' => 'admin.families.edit',
        'update' => 'admin.families.update',
        'destroy' => 'admin.families.destroy',
    ]);
    Route::get('families/{family}/report', [AdminFamilyController::class, 'report'])->name('admin.families.report');
    Route::resource('timetables', AdminTimetableController::class)->only(['index','store','update','destroy']);
    Route::post('timetables/{timetable}/deactivate', [AdminTimetableController::class, 'deactivate'])->name('timetables.deactivate');
    Route::post('timetables/{timetable}/reactivate', [AdminTimetableController::class, 'reactivate'])->name('timetables.reactivate');
    Route::post('timetables/bulk-deactivate', [AdminTimetableController::class, 'bulkDeactivate'])->name('timetables.bulk-deactivate');
    Route::get('timetables/calendar', [AdminTimetableCalendarController::class, 'index'])->name('timetables.calendar');
    Route::get('timetables/events', [AdminTimetableCalendarController::class, 'events'])->name('timetables.events.index');
    Route::put('timetables/events/{event}', [AdminTimetableCalendarController::class, 'updateEvent'])->name('timetables.events.update');
    Route::delete('timetables/events/{event}', [AdminTimetableCalendarController::class, 'destroyEvent'])->name('timetables.events.destroy');
    Route::get('timetables/export', [AdminTimetableCalendarController::class, 'export'])->name('timetables.export');
    Route::get('today-lessons', [AdminTodayLessonsController::class, 'index'])->name('today-lessons.index');
    Route::post('today-lessons/{event}/reschedule', [AdminTodayLessonsController::class, 'reschedule'])->name('today-lessons.reschedule');
    Route::post('today-lessons/{event}/cancel', [AdminTodayLessonsController::class, 'cancel'])->name('today-lessons.cancel');
    Route::post('today-lessons/{event}/absent', [AdminTodayLessonsController::class, 'absent'])->name('today-lessons.absent');
    Route::resource('timezone-adjustments', AdminTimezoneAdjustmentController::class)->only(['index', 'store']);
    Route::resource('support-attendances', AdminSupportAttendanceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('support-attendances/{supportAttendance}/finish-time', [AdminSupportAttendanceController::class, 'updateFinishTime'])->name('support-attendances.finish-time');
    Route::get('package-notifications', [AdminPackageNotificationsController::class, 'index'])->name('admin.package-notifications.index');
    Route::post('package-notifications/{package}/mark-paid', [AdminPackageNotificationsController::class, 'markAsPaid'])->name('admin.package-notifications.mark-paid');
    
    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('settings/support-names', [AdminSettingsController::class, 'storeSupportName'])->name('admin.settings.support-names.store');
    Route::put('settings/support-names/{supportName}', [AdminSettingsController::class, 'updateSupportName'])->name('admin.settings.support-names.update');
    Route::delete('settings/support-names/{supportName}', [AdminSettingsController::class, 'destroySupportName'])->name('admin.settings.support-names.destroy');
});

// Support-only routes (families, timetables, etc. - shared with admin but defined here to avoid conflicts)
Route::middleware(['auth','role:admin|support'])->prefix('admin')->group(function () {
    Route::resource('families', AdminFamilyController::class)->names([
        'index' => 'admin.families.index',
        'create' => 'admin.families.create',
        'store' => 'admin.families.store',
        'show' => 'admin.families.show',
        'edit' => 'admin.families.edit',
        'update' => 'admin.families.update',
        'destroy' => 'admin.families.destroy',
    ]);
    Route::get('families/{family}/report', [AdminFamilyController::class, 'report'])->name('admin.families.report');
    Route::resource('timetables', AdminTimetableController::class)->only(['index','store','update','destroy']);
    Route::post('timetables/{timetable}/deactivate', [AdminTimetableController::class, 'deactivate'])->name('timetables.deactivate');
    Route::post('timetables/{timetable}/reactivate', [AdminTimetableController::class, 'reactivate'])->name('timetables.reactivate');
    Route::post('timetables/bulk-deactivate', [AdminTimetableController::class, 'bulkDeactivate'])->name('timetables.bulk-deactivate');
    Route::get('timetables/calendar', [AdminTimetableCalendarController::class, 'index'])->name('timetables.calendar');
    Route::get('timetables/events', [AdminTimetableCalendarController::class, 'events'])->name('timetables.events.index');
    Route::put('timetables/events/{event}', [AdminTimetableCalendarController::class, 'updateEvent'])->name('timetables.events.update');
    Route::delete('timetables/events/{event}', [AdminTimetableCalendarController::class, 'destroyEvent'])->name('timetables.events.destroy');
    Route::get('timetables/export', [AdminTimetableCalendarController::class, 'export'])->name('timetables.export');
    Route::get('today-lessons', [AdminTodayLessonsController::class, 'index'])->name('today-lessons.index');
    Route::post('today-lessons/{event}/reschedule', [AdminTodayLessonsController::class, 'reschedule'])->name('today-lessons.reschedule');
    Route::post('today-lessons/{event}/cancel', [AdminTodayLessonsController::class, 'cancel'])->name('today-lessons.cancel');
    Route::post('today-lessons/{event}/absent', [AdminTodayLessonsController::class, 'absent'])->name('today-lessons.absent');
    Route::resource('timezone-adjustments', AdminTimezoneAdjustmentController::class)->only(['index', 'store']);
    Route::resource('support-attendances', AdminSupportAttendanceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('support-attendances/{supportAttendance}/finish-time', [AdminSupportAttendanceController::class, 'updateFinishTime'])->name('support-attendances.finish-time');
});

// Accountant and Admin routes (packages, billings, salaries)
Route::middleware(['auth','role:admin|accountant'])->prefix('admin')->group(function () {
    Route::get('packages', [AdminPackageController::class, 'index'])->name('admin.packages.index');
    Route::get('packages/{student}/report', [AdminPackageController::class, 'studentReport'])->name('admin.packages.report');
    Route::get('packages/{student}/completed', [AdminPackageController::class, 'completedPackages'])->name('admin.packages.completed');
    Route::get('package-notifications', [AdminPackageNotificationsController::class, 'index'])->name('admin.package-notifications.index');
    Route::post('package-notifications/{package}/mark-paid', [AdminPackageNotificationsController::class, 'markAsPaid'])->name('admin.package-notifications.mark-paid');
    Route::get('billings', [AdminBillingController::class, 'index'])->name('admin.billings.index');
    Route::post('billings/manual', [AdminBillingController::class, 'storeManual'])->name('admin.billings.manual.store');
    Route::patch('billings/{billing}/mark-paid', [AdminBillingController::class, 'markPaid'])->name('admin.billings.markPaid');
    Route::patch('billings/{billing}/mark-unpaid', [AdminBillingController::class, 'markUnpaid'])->name('admin.billings.markUnpaid');
    Route::get('teacher-salaries', [AdminTeacherSalaryController::class, 'index'])->name('admin.teacher-salaries.index');
    Route::get('teacher-salaries/export', [AdminTeacherSalaryController::class, 'export'])->name('admin.teacher-salaries.export');
    Route::post('teacher-salaries/apply-exchange-rate', [AdminTeacherSalaryController::class, 'applyExchangeRate'])->name('admin.teacher-salaries.apply-exchange-rate');
    Route::patch('teacher-salaries/{salary}/mark-paid', [AdminTeacherSalaryController::class, 'markPaid'])->name('admin.teacher-salaries.markPaid');
    Route::patch('teacher-salaries/{salary}/mark-unpaid', [AdminTeacherSalaryController::class, 'markUnpaid'])->name('admin.teacher-salaries.markUnpaid');
    
    // Financial Overview
    Route::get('financials', [AdminFinancialOverviewController::class, 'index'])->name('admin.financials.index');
    Route::post('financials/support-salary', [AdminFinancialOverviewController::class, 'storeSupportSalary'])->name('admin.financials.support-salary.store');
    Route::post('financials/accountant-salary', [AdminFinancialOverviewController::class, 'storeAccountantSalary'])->name('admin.financials.accountant-salary.store');
    Route::patch('financials/support-salary/{salary}/status', [AdminFinancialOverviewController::class, 'updateSupportSalaryStatus'])->name('admin.financials.support-salary.status');
    Route::patch('financials/accountant-salary/{salary}/status', [AdminFinancialOverviewController::class, 'updateAccountantSalaryStatus'])->name('admin.financials.accountant-salary.status');
    Route::delete('financials/support-salary/{salary}', [AdminFinancialOverviewController::class, 'deleteSupportSalary'])->name('admin.financials.support-salary.delete');
    Route::delete('financials/accountant-salary/{salary}', [AdminFinancialOverviewController::class, 'deleteAccountantSalary'])->name('admin.financials.accountant-salary.delete');
});

// Teacher routes
Route::middleware(['auth','role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::resource('lessons', TeacherLessonController::class)->only(['index','create','store','edit','update','destroy']);
    Route::get('timetables/calendar', [\App\Http\Controllers\Teacher\TimetableCalendarController::class, 'index'])->name('timetables.calendar');
    Route::get('timetables/events', [\App\Http\Controllers\Teacher\TimetableCalendarController::class, 'events'])->name('timetables.events.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
