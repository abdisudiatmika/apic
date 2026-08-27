<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['surat_tugas', 'perjalanan_dinas', 'surat_jalan'])->default('surat_tugas');
            $table->string('destination');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('purpose');
            $table->string('transportation')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->string('attachment_path')->nullable();

            // Diisi HR saat approval final (lihat generateLetterNumber() di model).
            $table->string('letter_number')->nullable()->unique();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_position')->nullable();

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
        });

        Schema::create('travel_assignment_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['travel_assignment_id', 'employee_id'], 'travel_assignment_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_assignment_employees');
        Schema::dropIfExists('travel_assignments');
    }
};
