<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('requested_check_in')->nullable();
            $table->time('requested_check_out')->nullable();
            $table->text('reason');
            $table->string('attachment_path')->nullable();

            $table->enum('status', ['menunggu_atasan', 'menunggu_hr', 'disetujui', 'ditolak', 'dibatalkan'])
                ->default('menunggu_atasan');

            $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('atasan_note')->nullable();
            $table->timestamp('atasan_at')->nullable();

            $table->foreignId('hr_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('hr_note')->nullable();
            $table->timestamp('hr_at')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
