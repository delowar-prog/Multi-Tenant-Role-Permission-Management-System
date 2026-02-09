<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenantBranch;

class SupportTicket extends Model
{
    use BelongsToTenantBranch;

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
}
