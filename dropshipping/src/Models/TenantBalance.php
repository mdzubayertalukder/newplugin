<?php

namespace Plugin\Dropshipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantBalance extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     * Forces this model to use the central database connection
     *
     * @var string|null
     */
    protected $connection = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Force central database connection for tenant balances
        $this->connection = config('tenancy.database.central_connection', 'mysql');
    }

    protected $fillable = [
        'tenant_id',
        'available_balance',
        'pending_balance',
        'total_earnings',
        'total_withdrawn',
        'total_orders',
        'pending_orders',
        'approved_orders'
    ];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    /**
     * Get the total balance (available + pending)
     */
    public function getTotalBalanceAttribute()
    {
        return $this->available_balance + $this->pending_balance;
    }

    /**
     * Get withdrawal requests for this tenant
     */
    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get pending withdrawal requests
     */
    public function pendingWithdrawals()
    {
        return $this->withdrawalRequests()->where('status', 'pending');
    }

    /**
     * Get dropshipping orders for this tenant
     */
    public function orders()
    {
        return $this->hasMany(DropshippingOrder::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Check if tenant can withdraw specified amount
     */
    public function canWithdraw($amount)
    {
        return $this->available_balance >= $amount;
    }

    /**
     * Process withdrawal (deduct from available balance)
     */
    public function processWithdrawal($amount)
    {
        if (!$this->canWithdraw($amount)) {
            throw new \Exception('Insufficient balance for withdrawal');
        }

        $this->decrement('available_balance', $amount);
        $this->increment('total_withdrawn', $amount);
    }

    /**
     * Add pending earning from new order
     */
    public function addPendingEarning($amount)
    {
        $this->increment('pending_balance', $amount);
        $this->increment('total_orders');
        $this->increment('pending_orders');
    }

    /**
     * Approve pending earning (move from pending to available)
     */
    public function approvePendingEarning($amount)
    {
        $this->decrement('pending_balance', $amount);
        $this->increment('available_balance', $amount);
        $this->increment('total_earnings', $amount);
        $this->decrement('pending_orders');
        $this->increment('approved_orders');
    }

    /**
     * Scope to get balance for specific tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
