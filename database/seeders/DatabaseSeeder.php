<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'phone' => '01738118208',
            'address' => 'Dhaka'
        ]);

         $this->call([
            AuthorSeeder::class,
            PublisherSeeder::class,
            CategorySeeder::class,
            BookSeeder::class,
            BookCopySeeder::class,
            BorrowSeeder::class,
        ]);
    }
}
