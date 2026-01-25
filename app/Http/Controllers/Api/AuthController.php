<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:11',
            'address' => 'nullable|string|max:200',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        DB::beginTransaction();

        try {
            $plan = null;
            if (! empty($fields['plan_id'])) {
                $plan = Plan::find($fields['plan_id']);
            }
            // 1️⃣ Create Tenant
            $tenantData = [
                'name' => $fields['name'],
            ];

            if ($plan) {
                $tenantData['plan_id'] = $plan->id;
                $tenantData['name'] = $fields['name'];
                $tenantData['email'] = $fields['email'];
                $tenantData['phone'] = $fields['phone'];
                $tenantData['address'] = $fields['address'];
                $tenantData['subscription_started_at'] = now();
                $tenantData['subscription_expires_at'] = now()->addDays($plan->duration_days);
                $tenantData['subscription_status'] = 'active';
                $tenantData['trial_ends_at'] = $plan->trial_days ? now()->addDays($plan->trial_days) : null;
            }

            $tenant = Tenant::create($tenantData);

            TenantSubscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'subscription_started_at' => now(),
                'subscription_expires_at' => now()->addDays($plan->duration_days),
                'amount' => $plan->price,
                'billing_cycle' => $plan->billing_cycle,
                'status' => 'active',
            ]);
            // 2️⃣ Create User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $fields['name'],
                'email' => $fields['email'],
                'password' => Hash::make($fields['password']),
                'is_super_admin' => false,
                'is_woner' => true,
                'phone' => $fields['phone'],
                'address' => $fields['address']
            ]);

            // 3️⃣ Set Spatie Team Context (tenant_id)
            app(\Spatie\Permission\PermissionRegistrar::class)
                ->setPermissionsTeamId($tenant->id);

            $role = Role::where('name', 'tenant-admin')->first();
            // 6️⃣ Assign role to user
            $user->assignRole($role);

            // 🆕 Create Default Branch "Main"
            $branch = \App\Models\Branch::create([
                'tenant_id' => $tenant->id,
                'name' => 'Main',
                'email' => $fields['email'],
                'phone' => $fields['phone'],
                'address' => $fields['address'] ?? null,
            ]);

            // 🆕 Assign User to Branch
            $user->branches()->attach($branch->id);

            DB::commit();

            // 7️⃣ Create auth token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user'  => $user,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (! $user || ! Hash::check($fields['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Tenant check (skip for Super Admin)
        if (! $user->is_super_admin && ! $user->tenant_id) {
            return response()->json([
                'message' => 'Tenant not assigned to this user.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        // log save
        activity('audit')
            ->causedBy(auth()->user())
            ->event('login')    
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged in');
        // Prepare response
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'tenant_id' => $user->tenant_id,
            ],
            'token' => $token
        ]);
    }

    public function me()
    {
        $user = auth()->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'is_super_admin' => $user->is_super_admin,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $baseUrl = rtrim(config('app.frontend_url'), '/');
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return "{$baseUrl}/reset-password?token={$token}&email={$email}";
        });

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 422);
    }
}
