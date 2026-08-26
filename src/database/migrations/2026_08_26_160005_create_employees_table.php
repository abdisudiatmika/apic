<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();

            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();

            // Linked once a login account is provisioned for this employee; master data
            // can exist before that happens, so this stays nullable.
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            $table->date('join_date')->nullable();
            $table->enum('employment_status', ['tetap', 'kontrak', 'probation'])->default('kontrak');

            // Matches the ID configured on the attendance device / its export software
            // (see PRD 5.2 & 5.3) — used to map imported/device rows back to this employee.
            $table->string('attendance_machine_id')->nullable()->index();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
