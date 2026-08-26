<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 1);
            $table->text('reason')->nullable();
            $table->foreignId('replacement_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('attachment_path')->nullable();

            // menunggu_atasan -> menunggu_hr -> disetujui | ditolak | dibatalkan.
            // UI menampilkan menunggu_atasan/menunggu_hr sama-sama sebagai "Menunggu";
            // status granular ini menentukan antrean approval siapa berikutnya.
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

            $table->index(['employee_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
