<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin (primary)
        User::firstOrCreate(['email' => 'admin@gps.test'], [
            'name'        => 'System Admin',
            'phone'       => '0901000001',
            'password'    => Hash::make('123456'),
            'role'        => 'admin',
            'status'      => 'active',
            'employee_id' => 'ADM0001',
            'mfa_enabled' => false,
        ]);

        // Admin (airport alias — used in testing)
        User::firstOrCreate(['email' => 'admin@airport.com'], [
            'name'        => 'Airport Admin',
            'phone'       => '0901000099',
            'password'    => Hash::make('123456'),
            'role'        => 'admin',
            'status'      => 'active',
            'employee_id' => 'ADM0002',
            'mfa_enabled' => false,
        ]);

        // HR
        User::firstOrCreate(['email' => 'hr@gps.test'], [
            'name'        => 'HR Manager',
            'phone'       => '0901000002',
            'password'    => Hash::make('123456'),
            'role'        => 'hr',
            'status'      => 'active',
            'employee_id' => 'HR0001',
            'mfa_enabled' => false,
        ]);

        // Shift Managers (Ca trưởng)
        User::firstOrCreate(['email' => 'shiftmanager1@gps.test'], [
            'name'        => 'Ca Trưởng Sáng',
            'phone'       => '0901000007',
            'password'    => Hash::make('123456'),
            'role'        => 'shift_manager',
            'status'      => 'active',
            'employee_id' => 'SM0001',
            'mfa_enabled' => false,
        ]);

        User::firstOrCreate(['email' => 'shiftmanager2@gps.test'], [
            'name'        => 'Ca Trưởng Chiều',
            'phone'       => '0901000008',
            'password'    => Hash::make('123456'),
            'role'        => 'shift_manager',
            'status'      => 'active',
            'employee_id' => 'SM0002',
            'mfa_enabled' => false,
        ]);

        // Team Leads
        User::firstOrCreate(['email' => 'teamlead1@gps.test'], [
            'name'        => 'Team Lead Alpha',
            'phone'       => '0901000003',
            'password'    => Hash::make('123456'),
            'role'        => 'team_lead',
            'status'      => 'active',
            'employee_id' => 'TL0001',
            'mfa_enabled' => false,
        ]);

        User::firstOrCreate(['email' => 'teamlead2@gps.test'], [
            'name'        => 'Team Lead Beta',
            'phone'       => '0901000004',
            'password'    => Hash::make('123456'),
            'role'        => 'team_lead',
            'status'      => 'active',
            'employee_id' => 'TL0002',
            'mfa_enabled' => false,
        ]);

        // Employees
        $employees = [
            ['name' => 'Nguyen Van An',  'email' => 'emp1@gps.test',  'phone' => '0901000011', 'employee_id' => 'EMP0001'],
            ['name' => 'Tran Thi Binh',  'email' => 'emp2@gps.test',  'phone' => '0901000012', 'employee_id' => 'EMP0002'],
            ['name' => 'Le Van Cuong',   'email' => 'emp3@gps.test',  'phone' => '0901000013', 'employee_id' => 'EMP0003'],
            ['name' => 'Pham Thi Dung',  'email' => 'emp4@gps.test',  'phone' => '0901000014', 'employee_id' => 'EMP0004'],
            ['name' => 'Hoang Van Em',   'email' => 'emp5@gps.test',  'phone' => '0901000015', 'employee_id' => 'EMP0005'],
            ['name' => 'Vu Thi Phuong',  'email' => 'emp6@gps.test',  'phone' => '0901000016', 'employee_id' => 'EMP0006'],
            ['name' => 'Do Van Giang',   'email' => 'emp7@gps.test',  'phone' => '0901000017', 'employee_id' => 'EMP0007'],
            ['name' => 'Bui Thi Huong',  'email' => 'emp8@gps.test',  'phone' => '0901000018', 'employee_id' => 'EMP0008'],
            ['name' => 'Dang Van Hai',   'email' => 'emp9@gps.test',  'phone' => '0901000019', 'employee_id' => 'EMP0009'],
            ['name' => 'Nguyen Thi Lan', 'email' => 'emp10@gps.test', 'phone' => '0901000020', 'employee_id' => 'EMP0010'],
        ];

        foreach ($employees as $emp) {
            User::firstOrCreate(['email' => $emp['email']], [
                'name'        => $emp['name'],
                'phone'       => $emp['phone'],
                'password'    => Hash::make('123456'),
                'role'        => 'employee',
                'status'      => 'active',
                'employee_id' => $emp['employee_id'],
                'mfa_enabled' => false,
            ]);
        }
    }
}
