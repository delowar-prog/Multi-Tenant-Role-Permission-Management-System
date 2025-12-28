<?php

namespace Database\Seeders;

use App\Models\Tenant;
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

        $this->call([
            TenantSeeder::class,
            AuthorSeeder::class,
            CategorySeeder::class,
        ]);

        // User::factory(10)->create();
        $tenant = Tenant::first(); // must exist
        User::create([
            'tenant_id' => $tenant->id ?? 1,
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'phone' => '01738118208',
            'address' => 'Dhaka'
        ]);
    }
}
