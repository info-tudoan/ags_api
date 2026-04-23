<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@gps.test',
            'phone' => '0901000001',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'employee_id' => 'ADM0001',
            'mfa_enabled' => false,
        ]);

        // HR
        User::create([
            'name' => 'HR Manager',
            'email' => 'hr@gps.test',
            'phone' => '0901000002',
            'password' => Hash::make('password'),
            'role' => 'hr',
            'status' => 'active',
            'employee_id' => 'HR0001',
            'mfa_enabled' => false,
        ]);

        // Team Leads
        User::create([
            'name' => 'Team Lead Alpha',
            'email' => 'teamlead1@gps.test',
            'phone' => '0901000003',
            'password' => Hash::make('password'),
            'role' => 'team_lead',
            'status' => 'active',
            'employee_id' => 'TL0001',
            'mfa_enabled' => false,
        ]);

        User::create([
            'name' => 'Team Lead Beta',
            'email' => 'teamlead2@gps.test',
            'phone' => '0901000004',
            'password' => Hash::make('password'),
            'role' => 'team_lead',
            'status' => 'active',
            'employee_id' => 'TL0002',
            'mfa_enabled' => false,
        ]);

        // Employees
        $employees = [
            ['name' => 'Nguyen Van An',   'email' => 'emp1@gps.test',  'phone' => '0901000011', 'employee_id' => 'EMP0001'],
            ['name' => 'Tran Thi Binh',   'email' => 'emp2@gps.test',  'phone' => '0901000012', 'employee_id' => 'EMP0002'],
            ['name' => 'Le Van Cuong',    'email' => 'emp3@gps.test',  'phone' => '0901000013', 'employee_id' => 'EMP0003'],
            ['name' => 'Pham Thi Dung',   'email' => 'emp4@gps.test',  'phone' => '0901000014', 'employee_id' => 'EMP0004'],
            ['name' => 'Hoang Van Em',    'email' => 'emp5@gps.test',  'phone' => '0901000015', 'employee_id' => 'EMP0005'],
            ['name' => 'Vu Thi Phuong',   'email' => 'emp6@gps.test',  'phone' => '0901000016', 'employee_id' => 'EMP0006'],
            ['name' => 'Do Van Giang',    'email' => 'emp7@gps.test',  'phone' => '0901000017', 'employee_id' => 'EMP0007'],
            ['name' => 'Bui Thi Huong',   'email' => 'emp8@gps.test',  'phone' => '0901000018', 'employee_id' => 'EMP0008'],
            ['name' => 'Dang Van Hai',    'email' => 'emp9@gps.test',  'phone' => '0901000019', 'employee_id' => 'EMP0009'],
            ['name' => 'Nguyen Thi Lan',  'email' => 'emp10@gps.test', 'phone' => '0901000020', 'employee_id' => 'EMP0010'],
        ];

        foreach ($employees as $emp) {
            User::create([
                'name' => $emp['name'],
                'email' => $emp['email'],
                'phone' => $emp['phone'],
                'password' => Hash::make('password'),
                'role' => 'employee',
                'status' => 'active',
                'employee_id' => $emp['employee_id'],
                'mfa_enabled' => false,
            ]);
        }
    }
}
