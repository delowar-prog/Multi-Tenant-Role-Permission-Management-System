<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Branch extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'address',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($branch) {
            if (! $branch->id) {
                $branch->id = (string) Str::uuid();
            }
        });
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'branch_user'
        );
    }
}
