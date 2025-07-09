<?php

namespace Plugin\Dropshipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DropshippingOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'order_number',
        'original_order_id',
        'order_code',
        'local_product_id',
        'dropshipping_product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_amount',
        'commission_rate',
        'commission_amount',
        'tenant_earning',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'fulfillment_note',
        'status',
        'admin_notes',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'shipped_at',
        'delivered_at',
        'submitted_by',
        'approved_by'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'tenant_earning' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'DS-' . strtoupper(uniqid());
            }
            if (empty($order->submitted_at)) {
                $order->submitted_at = now();
            }
        });
    }

    /**
     * Get the local product associated with this order
     */
    public function localProduct()
    {
        return $this->belongsTo(\Plugin\TlcommerceCore\Models\Product::class, 'local_product_id');
    }

    /**
     * Get the dropshipping product associated with this order
     */
    public function dropshippingProduct()
    {
        return $this->belongsTo(\Plugin\Dropshipping\Models\DropshippingProduct::class, 'dropshipping_product_id');
    }

    /**
     * Get the user who submitted the order
     */
    public function submittedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    /**
     * Get the admin who approved the order
     */
    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Scope to get orders for a specific tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get orders by status
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
            'processing' => 'badge-info',
            'shipped' => 'badge-primary',
            'delivered' => 'badge-success',
            'cancelled' => 'badge-secondary',
            'rejected' => 'badge-danger',
            default => 'badge-secondary'
        };
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    /**
     * Check if order can be approved
     */
    public function canBeApproved()
    {
        return $this->status === 'pending';
    }

    /**
     * Approve the order
     */
    public function approve($adminId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $notes
        ]);

        // Update tenant balance
        $this->updateTenantBalance();
    }

    /**
     * Reject the order
     */
    public function reject($adminId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $adminId,
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Update tenant balance when order is approved
     */
    protected function updateTenantBalance()
    {
        $balance = \Plugin\Dropshipping\Models\TenantBalance::firstOrCreate(
            ['tenant_id' => $this->tenant_id],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earnings' => 0,
                'total_withdrawn' => 0,
                'total_orders' => 0,
                'pending_orders' => 0,
                'approved_orders' => 0
            ]
        );

        // Move from pending to available balance
        $balance->increment('available_balance', $this->tenant_earning);
        $balance->decrement('pending_balance', $this->tenant_earning);
        $balance->increment('total_earnings', $this->tenant_earning);
        $balance->increment('approved_orders');
        $balance->decrement('pending_orders');
    }
}
