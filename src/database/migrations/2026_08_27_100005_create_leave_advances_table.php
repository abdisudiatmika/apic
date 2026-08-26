<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->decimal('days', 5, 1);
            $table->text('reason')->nullable();

            $table->enum('status', ['menunggu_atasan', 'menunggu_hr', 'disetujui', 'ditolak'])
                ->default('menunggu_atasan');

            $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('atasan_note')->nullable();
            $table->timestamp('atasan_at')->nullable();

            $table->foreignId('hr_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('hr_note')->nullable();
            $table->timestamp('hr_at')->nullable();

            // Diisi = days saat disetujui HR, lalu berkurang otomatis tiap kali hak
            // cuti baru muncul (lihat LeaveBalanceService::deductOutstandingAdvances).
            $table->decimal('outstanding_days', 5, 1)->default(0);
            $table->timestamp('settled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_advances');
    }
};
