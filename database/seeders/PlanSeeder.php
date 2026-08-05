<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'key' => 'free',
                'name' => 'Free',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'limits' => json_encode([
                    'max_users' => 3,
                    'max_modules' => 1,
                    'storage_limit' => 100, // MB
                ]),
                'sort_order' => 1,
            ],
            [
                'key' => 'pro',
                'name' => 'Pro',
                'price_monthly' => 19.00,
                'price_yearly' => 190.00,
                'stripe_price_id_monthly' => 'price_pro_monthly_placeholder',
                'stripe_price_id_yearly' => 'price_pro_yearly_placeholder',
                'limits' => json_encode([
                    'max_users' => 15,
                    'max_modules' => 5,
                    'storage_limit' => 1024, // MB
                ]),
                'sort_order' => 2,
            ],
            [
                'key' => 'business',
                'name' => 'Business',
                'price_monthly' => 49.00,
                'price_yearly' => 490.00,
                'stripe_price_id_monthly' => 'price_business_monthly_placeholder',
                'stripe_price_id_yearly' => 'price_business_yearly_placeholder',
                'limits' => json_encode([
                    'max_users' => 100,
                    'max_modules' => -1, // unlimited
                    'storage_limit' => 10240, // MB
                ]),
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['key' => $plan['key']], $plan);
        }
    }
}
