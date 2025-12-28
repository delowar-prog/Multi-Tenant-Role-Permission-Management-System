<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function booted()
    {
        static::creating(function ($tenant) {
            if (!$tenant->id) {
                $tenant->id = (string) Str::uuid();
            }
        });
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
