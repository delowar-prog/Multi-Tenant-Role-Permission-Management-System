<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->user()->tenant;
        return response()->json($tenant);
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

        // Upload logo
        if ($request->hasFile('logo')) {
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
