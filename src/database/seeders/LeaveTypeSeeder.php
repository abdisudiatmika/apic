<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        LeaveType::create(['name' => 'Cuti Tahunan', 'code' => 'TAHUNAN', 'default_days_per_year' => 12, 'is_active' => true]);
        LeaveType::create(['name' => 'Cuti Sakit', 'code' => 'SAKIT', 'default_days_per_year' => 12, 'is_active' => true]);
    }
}
