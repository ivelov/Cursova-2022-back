<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'firstname' => 'admin',
            'lastname' => 'admin',
            'role' => 'admin',
            'birthdate' => date('Y:m:d'),
            'country' => 'ukr',
            'phone' => 'admin',
            'email' => 'admin@groupbwt.com',
            'password' => Hash::make('12345678'),
        ]);
        DB::table('users')->insert([
            'firstname' => '1',
            'lastname' => '1',
            'role' => 'listener',
            'birthdate' => date('Y:m:d'),
            'country' => 'ukr',
            'phone' => '0551111111',
            'email' => '1@1.com',
            'password' => Hash::make('111111'),
        ]);
        DB::table('users')->insert([
            'firstname' => '2',
            'lastname' => '2',
            'role' => 'announcer',
            'birthdate' => date('Y:m:d'),
            'country' => 'ukr',
            'phone' => '0551111111',
            'email' => '2@2.com',
            'password' => Hash::make('222222'),
        ]);
    }
}
