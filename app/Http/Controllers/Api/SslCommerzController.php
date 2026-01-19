<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\TenantSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SslCommerzController extends Controller
{
    public function initiate(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = auth()->user();
        $tenant = $user->tenant;
        $plan = Plan::findOrFail($request->plan_id);

        $transactionId = 'TXN_' . Str::uuid();

        $baseUrl = config('sslcommerz.sandbox')
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';

        $response = Http::asForm()->post($baseUrl . '/gwprocess/v4/api.php', [
            'store_id' => config('sslcommerz.store_id'),
            'store_passwd' => config('sslcommerz.store_password'),
            'total_amount' => $plan->price,
            'currency' => 'BDT',
            'tran_id' => $transactionId,

            'success_url' => route('ssl.success'),
            'fail_url' => route('ssl.fail'),
            'cancel_url' => route('ssl.cancel'),

            'cus_name' => $user->name,
            'cus_email' => $user->email,
            'cus_add1' => 'Dhaka',
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'cus_phone' => '01700000000',

            'product_name' => $plan->name,
            'product_category' => 'Subscription',
            'product_profile' => 'general',

            'value_a' => $tenant->id,
            'value_b' => $plan->id,
        ]);

        $data = $response->json();

        if (!isset($data['GatewayPageURL'])) {
            return response()->json(['message' => 'Payment initiation failed'], 500);
        }

        return response()->json([
            'payment_url' => $data['GatewayPageURL'],
        ]);
    }

    public function success(Request $request)
    {
        $tenantId = $request->value_a;
        $planId = $request->value_b;

        $plan = Plan::findOrFail($planId);

        TenantSubscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'subscription_started_at' => now(),
            'subscription_expires_at' => $plan->billing_cycle === 'monthly'
                ? now()->addMonth()
                : now()->addYear(),
            'amount' => $plan->price,
            'billing_cycle' => $plan->billing_cycle,
            'status' => 'active',
        ]);

        return redirect(config('app.frontend_url') . '/dashboard?payment=success');
    }

    public function fail()
    {
        return redirect(config('app.frontend_url') . '/billing?payment=failed');
    }

    public function cancel()
    {
        return redirect(config('app.frontend_url') . '/billing?payment=cancelled');
    }
}
