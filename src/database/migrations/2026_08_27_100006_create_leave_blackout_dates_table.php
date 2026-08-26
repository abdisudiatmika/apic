<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_blackout_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            // null = berlaku untuk semua departemen
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->timestamps();

            $table->index(['date', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_blackout_dates');
    }
};
