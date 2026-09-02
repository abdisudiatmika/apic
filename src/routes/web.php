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
    Route::get('employee-performance/excel', [ReportExportController::class, 'employeePerformanceExcel'])->name('employee-performance.excel');
    Route::get('employee-performance/pdf', [ReportExportController::class, 'employeePerformancePdf'])->name('employee-performance.pdf');
});
