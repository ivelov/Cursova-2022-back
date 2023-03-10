<?php

use App\Models\User;
use Illuminate\Database\Seeder;
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
        User::create([
            'firstname' => 'admin',
            'lastname' => 'admin',
            'role' => 'admin',
            'birthdate' => date('Y:m:d'),
            'country' => 'ua',
            'phone' => 'admin',
            'email' => 'admin@groupbwt.com',
            'password' => Hash::make('12345678'),
        ]);
        User::create([
            'firstname' => '1',
            'lastname' => '1',
            'role' => 'listener',
            'birthdate' => date('Y:m:d'),
            'country' => 'ua',
            'phone' => '+380551111111',
            'email' => '1@1.com',
            'password' => Hash::make('111111'),
        ]);
       User::create([
            'firstname' => '2',
            'lastname' => '2',
            'role' => 'announcer',
            'birthdate' => date('Y:m:d'),
            'country' => 'ua',
            'phone' => '+380551111111',
            'email' => '2@2.com',
            'password' => Hash::make('222222'),
        ]);
    }
}
