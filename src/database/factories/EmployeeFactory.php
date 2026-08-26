<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Synthetic data only — never seed real employee records (e.g. from a real
     * attendance export) through this factory. See README "Data dev sintetis".
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'nip' => $this->faker->unique()->numerify('APIC-#####'),
            'name' => $name,
            'email' => Str::lower(Str::slug($name, '.')).'@example.test',
            'phone' => fake()->numerify('08##########'),
            'department_id' => Department::inRandomOrder()->value('id'),
            'position_id' => Position::inRandomOrder()->value('id'),
            'branch_id' => Branch::inRandomOrder()->value('id'),
            'supervisor_id' => null,
            'join_date' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'employment_status' => fake()->randomElement(['tetap', 'kontrak', 'probation']),
            'attendance_machine_id' => (string) fake()->unique()->numberBetween(100, 999),
            'is_active' => true,
        ];
    }
}
