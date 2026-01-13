<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'price' => 0,
                'duration_days' => 30,
                'features' => [
                    'users' => 3,
                    'branding' => true,
                    'custom_domain' => false,
                    'storage_mb' => 500,
                    'support' => 'basic',
                ],
            ],
            [
                'name' => 'Basic',
                'price' => 999,
                'duration_days' => 30,
                'features' => [
                    'users' => 10,
                    'branding' => true,
                    'custom_domain' => false,
                    'storage_mb' => 2000,
                    'support' => 'standard',
                ],
            ],
            [
                'name' => 'Pro',
                'price' => 1999,
                'duration_days' => 30,
                'features' => [
                    'users' => 20,
                    'branding' => true,
                    'custom_domain' => true,
                    'storage_mb' => 10000,
                    'support' => 'priority',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                [
                    'price' => $plan['price'],
                    'duration_days' => $plan['duration_days'],
                    'features' => $plan['features'],
                ]
            );
        }
    }
}
