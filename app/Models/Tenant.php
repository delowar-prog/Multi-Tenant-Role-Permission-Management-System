<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        // যদি আরও ফিল্ড থাকে, যেমন 'domain', 'uuid', add here
    ];
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
