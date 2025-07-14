<?php

namespace Plugin\Dropshipping\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalSetting extends Model
{
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

        // Force central database connection for withdrawal settings
        $this->connection = config('tenancy.database.central_connection', 'mysql');
    }

    protected $fillable = [
        'minimum_withdrawal_amount',
        'maximum_withdrawal_amount',
        'withdrawal_fee_percentage',
        'withdrawal_fee_fixed',
        'withdrawal_processing_days',
        'auto_approve_withdrawals',
        'withdrawal_terms',
        'bank_requirements',
        'is_active'
    ];

    protected $casts = [
        'minimum_withdrawal_amount' => 'decimal:2',
        'maximum_withdrawal_amount' => 'decimal:2',
        'withdrawal_fee_percentage' => 'decimal:2',
        'withdrawal_fee_fixed' => 'decimal:2',
        'auto_approve_withdrawals' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active withdrawal settings
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first() ?? self::create([
            'minimum_withdrawal_amount' => 50.00,
            'withdrawal_fee_percentage' => 0,
            'withdrawal_fee_fixed' => 0,
            'withdrawal_processing_days' => 3,
            'auto_approve_withdrawals' => false,
            'is_active' => true
        ]);
    }

    /**
     * Calculate withdrawal fee for given amount
     */
    public function calculateFee($amount)
    {
        $percentageFee = ($amount * $this->withdrawal_fee_percentage) / 100;
        return $percentageFee + $this->withdrawal_fee_fixed;
    }

    /**
     * Calculate net amount after fee deduction
     */
    public function calculateNetAmount($amount)
    {
        return $amount - $this->calculateFee($amount);
    }

    /**
     * Check if amount meets minimum withdrawal requirement
     */
    public function meetsMinimumAmount($amount)
    {
        return $amount >= $this->minimum_withdrawal_amount;
    }

    /**
     * Check if amount is within maximum withdrawal limit
     */
    public function withinMaximumAmount($amount)
    {
        return is_null($this->maximum_withdrawal_amount) || $amount <= $this->maximum_withdrawal_amount;
    }

    /**
     * Validate withdrawal amount
     */
    public function validateAmount($amount)
    {
        if (!$this->meetsMinimumAmount($amount)) {
            throw new \Exception("Minimum withdrawal amount is {$this->minimum_withdrawal_amount}");
        }

        if (!$this->withinMaximumAmount($amount)) {
            throw new \Exception("Maximum withdrawal amount is {$this->maximum_withdrawal_amount}");
        }

        return true;
    }
}
