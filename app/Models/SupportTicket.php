<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends TenantModel
{

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'category_id',
        'ticket_no',
        'subject',
        'priority',
        'status',
        'assigned_to',
        'last_reply_at',
    ];

    /* ================= Relationships ================= */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(SupportCategory::class, 'category_id');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id')
            ->latest();
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function logs()
    {
        return $this->hasMany(SupportTicketLog::class);
    }

    public function scopeVisibleTo($query, $user)  //super admin, 
    {
        if ($user->is_super_admin || $user->is_support_admin) {
            return $query;
        }

        $query->where('tenant_id', $user->tenant_id);

        if (! $user->is_woner) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }
}
