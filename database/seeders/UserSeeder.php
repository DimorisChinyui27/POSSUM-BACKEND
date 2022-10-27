<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!User::count()) {

            // ===================
            // CREATING ROLES
            // ===================
            $adminRole = new Role();
            $adminRole->name = "admin";
            $adminRole->display_name = "Administrator";
            $adminRole->description  = 'User is the super admin of the system. They own the project.';
            $adminRole->save();

            $userRole = new Role();
            $userRole->name = "user";
            $userRole->display_name = "User";
            $userRole->description  = 'Regular/Default User of the system';
            $userRole->save();

            // ===================
            // CREATING USERS
            // ===================
            $normalUser = new User();
            $normalUser->name = 'Default User';
            $normalUser->email = 'default@possum.com';
            $normalUser->password = Hash::make('password');
            $normalUser->username = generateUsername('default');
            $normalUser->save();

            $adminUser = new User();
            $adminUser->name = 'Admin User';
            $adminUser->email = 'admin@possum.com';
            $adminUser->password = Hash::make('password');
            $adminUser->username = generateUsername('admin@possum.com');
            $adminUser->save();
        }
    }
}
