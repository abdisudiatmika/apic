<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * The 5 roles from the PRD. Permission granularity (per-record ownership for
     * atasan/pegawai) lives in the model Policies, not here — these roles just gate
     * which Filament panel an account can open (see User::canAccessPanel()).
     */
    public function run(): void
    {
        foreach (['administrator', 'hr', 'direksi', 'atasan', 'pegawai'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
