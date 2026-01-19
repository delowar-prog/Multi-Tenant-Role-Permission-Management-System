<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans()
    {
        return response()->json([
            'data' => Plan::where('is_active', true)->get()
        ]);
    }

}
