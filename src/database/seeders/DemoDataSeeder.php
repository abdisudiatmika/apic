<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\LeaveAdvance;
use App\Models\LeaveBalance;
use App\Models\LeaveBlackoutDate;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Entirely synthetic master data + one demo account per role, so the app is
     * click-through-able without ever loading real employee data (e.g. from a real
     * attendance machine export) into a dev database. See README for credentials.
     */
    public function run(): void
    {
        $branches = collect([
            Branch::create(['name' => 'Kantor Pusat', 'code' => 'HQ', 'address' => 'Jl. Contoh No. 1', 'is_active' => true]),
            Branch::create(['name' => 'Cabang Surabaya', 'code' => 'SBY', 'address' => 'Jl. Contoh No. 2', 'is_active' => true]),
        ]);

        $departments = collect([
            Department::create(['name' => 'Human Resources', 'code' => 'HRD']),
            Department::create(['name' => 'Operasional', 'code' => 'OPS']),
            Department::create(['name' => 'Keuangan', 'code' => 'FIN']),
            Department::create(['name' => 'IT', 'code' => 'IT']),
        ]);

        $positions = collect([
            Position::create(['name' => 'Staff', 'code' => 'STAFF']),
            Position::create(['name' => 'Supervisor', 'code' => 'SPV']),
            Position::create(['name' => 'Manager', 'code' => 'MGR']),
            Position::create(['name' => 'Direktur', 'code' => 'DIR']),
        ]);

        $shiftPagi = Shift::create(['name' => 'Shift Pagi', 'start_time' => '08:00', 'end_time' => '17:00', 'tolerance_minutes' => 15]);
        $shiftSore = Shift::create(['name' => 'Shift Sore', 'start_time' => '13:00', 'end_time' => '21:00', 'tolerance_minutes' => 15]);

        // --- one demo account per role -------------------------------------------------
        $hrUser = User::create(['name' => 'Demo HR', 'email' => 'hr@demo.test', 'password' => 'DemoHR#2026']);
        $hrUser->assignRole('hr');

        $adminUser = User::create(['name' => 'Demo Administrator', 'email' => 'admin@demo.test', 'password' => 'DemoAdmin#2026']);
        $adminUser->assignRole('administrator');

        $direksiUser = User::create(['name' => 'Demo Direksi', 'email' => 'direksi@demo.test', 'password' => 'DemoDireksi#2026']);
        $direksiUser->assignRole('direksi');

        $atasanUser = User::create(['name' => 'Demo Atasan', 'email' => 'atasan@demo.test', 'password' => 'DemoAtasan#2026']);
        $atasanUser->assignRole('atasan');

        $pegawaiUser = User::create(['name' => 'Demo Pegawai', 'email' => 'pegawai@demo.test', 'password' => 'DemoPegawai#2026']);
        $pegawaiUser->assignRole('pegawai');

        $atasanEmployee = Employee::create([
            'nip' => 'APIC-00001',
            'name' => 'Demo Atasan',
            'email' => 'atasan@demo.test',
            'department_id' => $departments[1]->id,
            'position_id' => $positions[2]->id,
            'branch_id' => $branches[0]->id,
            'user_id' => $atasanUser->id,
            'join_date' => now()->subYears(3),
            'employment_status' => 'tetap',
            'attendance_machine_id' => '001',
            'is_active' => true,
        ]);

        $pegawaiEmployee = Employee::create([
            'nip' => 'APIC-00002',
            'name' => 'Demo Pegawai',
            'email' => 'pegawai@demo.test',
            'department_id' => $departments[1]->id,
            'position_id' => $positions[0]->id,
            'branch_id' => $branches[0]->id,
            'supervisor_id' => $atasanEmployee->id,
            'user_id' => $pegawaiUser->id,
            'join_date' => now()->subYear(),
            'employment_status' => 'tetap',
            'attendance_machine_id' => '002',
            'is_active' => true,
        ]);

        // --- extra synthetic employees (Faker), some reporting to the demo atasan ------
        $others = Employee::factory()->count(18)->create();
        $others->take(4)->each(fn (Employee $e) => $e->update(['supervisor_id' => $atasanEmployee->id]));

        $allEmployees = $others->push($atasanEmployee)->push($pegawaiEmployee);

        // --- today's shift assignment + a few days of attendance, so the Dashboard
        // widget (5.1) has real counts instead of showing an empty state -------------
        foreach ($allEmployees as $employee) {
            $shift = fake()->boolean(70) ? $shiftPagi : $shiftSore;

            foreach (range(0, 4) as $daysAgo) {
                $date = Carbon::today()->subDays($daysAgo);

                EmployeeSchedule::firstOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    ['shift_id' => $shift->id]
                );

                $roll = fake()->numberBetween(1, 100);
                if ($roll <= 5) {
                    // absent — no check-in/check-out at all
                    AttendanceLog::create([
                        'employee_id' => $employee->id,
                        'date' => $date->toDateString(),
                        'status' => 'tidak_hadir',
                        'source' => 'device',
                    ]);

                    continue;
                }

                $lateMinutes = $roll <= 25 ? fake()->numberBetween(1, 60) : 0;

                AttendanceLog::create([
                    'employee_id' => $employee->id,
                    'date' => $date->toDateString(),
                    'check_in' => Carbon::parse($shift->start_time)->addMinutes($lateMinutes)->format('H:i:s'),
                    'check_out' => Carbon::parse($shift->end_time)->format('H:i:s'),
                    'late_minutes' => $lateMinutes,
                    'status' => $lateMinutes > 0 ? 'terlambat' : 'hadir',
                    'source' => 'device',
                ]);
            }
        }

        // --- Fase 2: Cuti & Bon Cuti ---------------------------------------------------
        $leaveTypes = LeaveType::all();
        $year = now()->year;

        foreach ($allEmployees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                    'entitled_days' => $leaveType->default_days_per_year,
                    'carry_forward_days' => 0,
                ]);
            }
        }

        $cutiTahunan = $leaveTypes->firstWhere('code', 'TAHUNAN');
        $subordinates = $allEmployees->where('supervisor_id', $atasanEmployee->id)->values();

        // Menunggu approval atasan — supaya alur approval langsung terlihat begitu login demo.
        LeaveRequest::create([
            'employee_id' => $pegawaiEmployee->id,
            'leave_type_id' => $cutiTahunan->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'days' => 3,
            'reason' => 'Acara keluarga',
            'status' => 'menunggu_atasan',
        ]);

        // Sudah disetujui atasan, menunggu HR.
        if ($subordinates->isNotEmpty()) {
            $req = LeaveRequest::create([
                'employee_id' => $subordinates[0]->id,
                'leave_type_id' => $cutiTahunan->id,
                'start_date' => now()->addDays(10)->toDateString(),
                'end_date' => now()->addDays(12)->toDateString(),
                'days' => 3,
                'reason' => 'Keperluan pribadi',
                'status' => 'menunggu_atasan',
            ]);
            $req->approveByAtasan($atasanUser, 'Disetujui, tim masih cukup orang.');
        }

        // Riwayat cuti yang sudah selesai penuh (disetujui) — supaya "terpakai" di Sisa
        // Cuti tidak selalu nol.
        if ($subordinates->count() > 1) {
            $req = LeaveRequest::create([
                'employee_id' => $subordinates[1]->id,
                'leave_type_id' => $cutiTahunan->id,
                'start_date' => now()->subDays(20)->toDateString(),
                'end_date' => now()->subDays(18)->toDateString(),
                'days' => 3,
                'reason' => 'Cuti tahunan',
                'status' => 'menunggu_atasan',
            ]);
            $req->approveByAtasan($atasanUser, null);
            $req->approveByHr($hrUser, null);
        }

        // Bon Cuti disetujui & outstanding — coba naikkan Saldo Cuti pegawai ini di
        // /admin/leave-balances untuk melihat potongan otomatis (PRD 5.7) terjadi.
        $advance = LeaveAdvance::create([
            'employee_id' => $pegawaiEmployee->id,
            'leave_type_id' => $cutiTahunan->id,
            'days' => 2,
            'reason' => 'Belum ada saldo, keperluan mendesak',
            'status' => 'menunggu_atasan',
        ]);
        $advance->approveByAtasan($atasanUser, null);
        $advance->approveByHr($hrUser, null);

        // Tanggal terbatas cuti (PRD 5.8) — contoh: akhir tahun sibuk tutup buku.
        LeaveBlackoutDate::create([
            'date' => now()->endOfYear()->toDateString(),
            'department_id' => null,
            'reason' => 'Periode tutup buku akhir tahun',
        ]);
    }
}
