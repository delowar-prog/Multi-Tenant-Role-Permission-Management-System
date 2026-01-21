<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Tenant $tenant)
    {
        $admin = auth()->user();
        $token = $admin->currentAccessToken();
        if ($token && $this->isImpersonationToken($token)) {
            return response()->json(['message' => 'Already impersonating.'], 409);
        }
        if (!$admin->is_super_admin) {
            abort(403);
        }
        // 3️⃣ Create impersonation token
        $tenantOwner = $tenant->owner;
        if (!$tenantOwner) {
            return response()->json(['message' => 'Tenant owner not found.'], 422);
        }
        $token = $tenantOwner->createToken(
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

    public function exit()
    {
        $token = auth()->user()->currentAccessToken();

        if ($token && $this->isImpersonationToken($token)) {
            $token->delete();
            return response()->json(['message' => 'Exited']);
        }

        return response()->json(['message' => 'Not impersonating.']);
    }

    private function isImpersonationToken($token): bool
    {
        return $token->name === 'tenant-impersonation';
    }
}
