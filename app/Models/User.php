<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\AssignTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, AssignTenant, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_super_admin',
        'is_woner',
        'phone',
        'address'
    ];

    /**
     * Scope: only current tenant users & Super Admin can access all tenant
     */
    public function scopeTenant($query)
    {
        if (
            auth()->check() &&
            ! auth()->user()->is_super_admin
        ) {
            return $query->where('tenant_id', auth()->user()->tenant_id);
        }

        return $query;
    }


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')                       // 🔹 log type
            ->logOnly(['name', 'email', 'phone'])   // 🔹 only important fields
            ->logOnlyDirty()                                    // 🔹 changed field only
            ->dontSubmitEmptyLogs();                            // 🔹 no fake logs
    }
}
