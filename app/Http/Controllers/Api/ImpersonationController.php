<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Tenant $tenant){
        $admin =auth()->user();
        if(!$admin->is_super_admin){
            abort(403);
        }
         // 3️⃣ Create impersonation token
         $tenantWoner=$tenant->owner;
        $token = $tenantWoner->createToken(
            'tenant-impersonation',
            abilities: [
                'impersonate',
                'tenant_id:' . $tenant->id,
                'impersonator:' . $admin->id,
            ],
            expiresAt: now()->addMinutes(30)
        );

         return response()->json([
            'impersonation_token' => $token->plainTextToken,
            'expires_in' => 1800,
        ]);
    }
}
