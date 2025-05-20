<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name'    => 'ADMIN',
            'username'    => 'admin',
            'email'    => 'admin@gmail.com',
            'password'    => Hash::make(12345678),
            'mobile_access'    => 1,
            'user_group_id'    => 1,
            'created_by'    => 1,
            'created_at'    => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at'    => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
