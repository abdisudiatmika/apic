<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_leave_minutes')->default(0);
            $table->enum('status', ['hadir', 'terlambat', 'tidak_hadir'])->default('hadir');
            $table->enum('source', ['device', 'excel_import', 'manual_correction']);
            $table->foreignId('attendance_import_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // One attendance record per employee per day: a re-import for the same
            // period upserts this row instead of creating duplicates (PRD 5.3.1).
            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
