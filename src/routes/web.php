<?php

use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\TravelAssignmentPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/travel-assignments/{travelAssignment}/pdf', [TravelAssignmentPdfController::class, 'show'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('travel-assignments.pdf');

Route::prefix('reports')->name('reports.')->middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('attendance/excel', [ReportExportController::class, 'attendanceExcel'])->name('attendance.excel');
    Route::get('attendance/pdf', [ReportExportController::class, 'attendancePdf'])->name('attendance.pdf');
    Route::get('leave/excel', [ReportExportController::class, 'leaveExcel'])->name('leave.excel');
    Route::get('leave/pdf', [ReportExportController::class, 'leavePdf'])->name('leave.pdf');
    Route::get('leave-advance/excel', [ReportExportController::class, 'leaveAdvanceExcel'])->name('leave-advance.excel');
    Route::get('leave-advance/pdf', [ReportExportController::class, 'leaveAdvancePdf'])->name('leave-advance.pdf');
    Route::get('travel/excel', [ReportExportController::class, 'travelExcel'])->name('travel.excel');
    Route::get('travel/pdf', [ReportExportController::class, 'travelPdf'])->name('travel.pdf');
});
