<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->user()->tenant;
        return response()->json($tenant);
    }

    public function tenants(Request $request)
    {
        $query = Tenant::query()->select(['id', 'name'])->orderBy('name');

        if (! $request->user()->is_super_admin) {
            $query->where('id', $request->user()->tenant_id);
        }

        return response()->json($query->get());
    }

    public function update(Request $request)
    {
        $tenant = $request->user()->tenant;

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'logo' => 'nullable|image|max:2048',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);


        //If Logo input
        if ($request->hasFile('logo')) {
            // 🔴 Delete old logo if exists
            if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            // Upload logo
            $path = $request->file('logo')->store('tenants/logos', 'public');
            $data['logo'] = $path;
        }

        $tenant->update($data);

        return response()->json([
            'message' => 'Tenant branding updated successfully',
            'tenant' => $tenant
        ]);
    }
}
