<?php

namespace Database\Seeders;

use App\Models\UserGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserGroup::create([
            'name'    => 'admin',
            'permissions'    => '["add_parachute_check","add_user","add_user_group","add_setting","edit_parachute_check","edit_user","edit_user_group","edit_setting","delete_parachute_check","delete_user","delete_user_group","delete_setting","view_parachute","view_parachute_check","view_user","view_user_group","view_report","view_report_parachute_data","view_report_parachute_check","view_setting","add_parachute","edit_parachute","delete_parachute"]',
        ]);
    }
}
