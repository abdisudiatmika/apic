<?php

use App\Http\Controllers\TravelAssignmentPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/travel-assignments/{travelAssignment}/pdf', [TravelAssignmentPdfController::class, 'show'])
    ->middleware('auth')
    ->name('travel-assignments.pdf');
