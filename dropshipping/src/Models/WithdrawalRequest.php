<?php

namespace Plugin\Dropshipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'request_number',
        'amount',
        'status',
        'bank_name',
        'account_number',
        'account_holder_name',
        'bank_code',
        'swift_code',
        'additional_details',
        'admin_notes',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'requested_at',
        'requested_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'requested_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_number)) {
                $request->request_number = 'WR-' . strtoupper(uniqid());
            }
            if (empty($request->requested_at)) {
                $request->requested_at = now();
            }
        });
    }

    /**
     * Get the user who requested the withdrawal
     */
    public function requestedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    /**
     * Get the admin who processed the withdrawal
     */
    public function processedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    /**
     * Get the tenant balance
     */
    public function tenantBalance()
    {
        return $this->belongsTo(TenantBalance::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Scope to get requests for a specific tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get requests by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'processed' => 'badge-info',
            default => 'badge-secondary'
        };
    }

    /**
     * Check if request can be approved
     */
    public function canBeApproved()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if request can be rejected
     */
    public function canBeRejected()
    {
        return $this->status === 'pending';
    }

    /**
     * Approve the withdrawal request
     */
    public function approve($adminId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'processed_by' => $adminId,
            'processed_at' => now(),
            'admin_notes' => $notes
        ]);
    }

    /**
     * Reject the withdrawal request
     */
    public function reject($adminId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'processed_by' => $adminId,
            'processed_at' => now(),
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Mark as processed (payment sent)
     */
    public function markAsProcessed($adminId, $notes = null)
    {
        $this->update([
            'status' => 'processed',
            'processed_by' => $adminId,
            'processed_at' => now(),
            'admin_notes' => $notes
        ]);

        // Deduct from tenant balance
        $balance = TenantBalance::where('tenant_id', $this->tenant_id)->first();
        if ($balance) {
            $balance->processWithdrawal($this->amount);
        }
    }
}
