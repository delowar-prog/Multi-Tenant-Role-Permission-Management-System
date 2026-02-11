<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupportCategory extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'category_id');
    }
}
