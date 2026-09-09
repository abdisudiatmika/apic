<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * One-off bulk provisioning: pegawai yang belum tertaut ke akun login (User)
 * dibuatkan akun otomatis — email "{nip}@apic.local" (NIP sudah unik per
 * pegawai, lihat migration create_employees_table), password sama untuk
 * semua ("password", atas permintaan eksplisit pengguna meski sudah
 * diperingatkan risikonya — bukan default yang aman untuk dipakai ulang di
 * tempat lain). Semua diberi role "pegawai"; promosi ke "atasan" tetap
 * manual lewat Kelola User, supaya tidak salah tebak siapa yang benar-benar
 * atasan (relasi supervisor_id ada tapi menandakan siapa ATASANNYA seorang
 * pegawai, bukan menandakan pegawai itu sendiri seorang atasan).
 *
 * Idempotent — aman dijalankan ulang, pegawai yang sudah punya user_id
 * dilewati begitu saja.
 */
#[Signature('employees:create-login-accounts {--password=password : Password sama untuk seluruh akun yang dibuat}')]
#[Description('Buat akun login (role pegawai) untuk seluruh Data Pegawai yang belum tertaut ke User, email dari NIP')]
class CreateEmployeeLoginAccounts extends Command
{
    public function handle(): int
    {
        $password = (string) $this->option('password');

        $employees = Employee::query()
            ->whereNull('user_id')
            ->orderBy('name')
            ->get();

        if ($employees->isEmpty()) {
            $this->info('Semua pegawai sudah punya akun login — tidak ada yang dibuat.');

            return self::SUCCESS;
        }

        $created = [];
        $skipped = [];

        foreach ($employees as $employee) {
            $email = Str::of($employee->nip)->trim()->lower()->append('@apic.local')->toString();

            if (User::where('email', $email)->exists()) {
                $skipped[] = "{$employee->name} (NIP {$employee->nip}) — email {$email} sudah dipakai akun lain, dilewati";

                continue;
            }

            $user = User::create([
                'name' => $employee->name,
                'email' => $email,
                'password' => $password,
            ]);
            $user->assignRole('pegawai');

            $employee->update(['user_id' => $user->id]);

            $created[] = [$employee->nip, $employee->name, $email];
        }

        if ($created !== []) {
            $this->table(['NIP', 'Nama', 'Email Login'], $created);
            $this->info(count($created) . ' akun dibuat, password untuk semua: "' . $password . '"');
        }

        if ($skipped !== []) {
            $this->warn(implode(PHP_EOL, $skipped));
        }

        return self::SUCCESS;
    }
}
