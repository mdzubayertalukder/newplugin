# Multipurcpay Visibility Fix - Complete Solution

## Problem Analysis

Your users can't see the **multipurcpay** payment gateway during store creation because of **THREE MAIN ISSUES**:

### Issue 1: Super Admin Restriction ❌
The `MultipurcpayController` has hardcoded checks that only allow Super Admin users to use this payment gateway.

### Issue 2: Missing Package Association ❌
The payment gateway is not associated with packages in the `tl_saas_package_has_payment_methods` table.

### Issue 3: Payment Gateway Status ❌
The payment method might not be active in the `tl_saas_payment_methods` table.

---

## COMPLETE SOLUTION

### Step 1: Fix Database Issues

**Run this SQL script first:**

```sql
-- Execute: saas/fix_multipurcpay_visibility.sql

-- 1. Ensure multipurcpay is active in payment methods table
INSERT INTO `tl_saas_payment_methods` (`id`, `name`, `status`, `created_at`, `updated_at`) 
VALUES (18, 'multipurcpay', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `name` = 'multipurcpay',
    `status` = 1,
    `updated_at` = NOW();

-- 2. Associate multipurcpay with ALL packages
INSERT INTO `tl_saas_package_has_payment_methods` (`package_id`, `payment_method_id`)
SELECT p.id, 18 
FROM `tl_saas_packages` p 
WHERE p.id NOT IN (
    SELECT package_id 
    FROM `tl_saas_package_has_payment_methods` 
    WHERE payment_method_id = 18
);

-- 3. Verify associations
SELECT 
    p.name as package_name,
    pm.name as payment_method_name,
    pm.status as payment_method_status
FROM `tl_saas_packages` p
JOIN `tl_saas_package_has_payment_methods` ppm ON p.id = ppm.package_id
JOIN `tl_saas_payment_methods` pm ON ppm.payment_method_id = pm.id
WHERE pm.name = 'multipurcpay'
ORDER BY p.name;
```

### Step 2: Remove Super Admin Restrictions

**Edit: `saas/src/Http/Controllers/Payment/MultipurcpayController.php`**

#### 2.1 Update the `index()` method:
```php
public function index()
{
    // REMOVED: Super Admin check
    // Original: if (!$this->isSuperAdmin()) { return redirect()->back()->with('error', '...'); }

    $data = [
        'currency' => $this->currency,
        'total_payable_amount' => number_format($this->total_payable_amount, 2, '.', ''),
        'payable_amount' => $this->total_payable_amount,
        'api_key' => $this->api_key,
        'base_url' => $this->base_url,
        'logo' => \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_logo'),
        'instruction' => \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_instruction'),
    ];

    return view('plugin/saas::payments.gateways.multipurcpay.index', $data);
}
```

#### 2.2 Update the `createCharge()` method:
```php
public function createCharge(Request $request)
{
    try {
        // Validate credentials
        if (empty($this->api_key)) {
            return response()->json([
                'success' => false,
                'message' => 'Multipurcpay API key not configured. Please configure API key in payment settings.',
            ]);
        }

        // REMOVED: Super Admin check
        // Original: if (!$this->isSuperAdmin()) { return response()->json(['success' => false, 'message' => '...']); }

        // ... rest of the method remains the same
    }
}
```

#### 2.3 Update the `success()` method:
```php
public function success(Request $request)
{
    try {
        // REMOVED: Super Admin check
        // Original: if (!$this->isSuperAdmin()) { return redirect()->back()->with('error', '...'); }

        // ... rest of the method remains the same
    }
}
```

#### 2.4 Update the `cancel()` method:
```php
public function cancel()
{
    try {
        // REMOVED: Super Admin check
        // Original: if (!$this->isSuperAdmin()) { return redirect()->back()->with('error', '...'); }

        // ... rest of the method remains the same
    }
}
```

#### 2.5 Remove the `isSuperAdmin()` method entirely:
```php
// DELETE THIS ENTIRE METHOD (lines 422-433):
/*
private function isSuperAdmin()
{
    $user = auth()->user();
    
    if (!$user) {
        return false;
    }

    return $user->user_type == 1 || $user->hasRole('Super Admin') || $user->can('Manage Payments');
}
*/
```

### Step 3: Update Views

**Edit: `saas/views/payments/gateways/multipurcpay/configuration.blade.php`**

Remove the Super Admin warning (lines 71-74):
```php
{{-- REMOVE OR COMMENT OUT:
<div class="alert alert-warning mb-20">
    <strong>{{ translate('Super Admin Only') }}</strong><br>
    {{ translate('This payment gateway is only available for Super Admin users when creating stores.') }}
</div>
--}}
```

**Edit: `saas/views/payments/gateways/multipurcpay/index.blade.php`**

Remove the Super Admin warning (lines 157-160):
```php
{{-- REMOVE OR COMMENT OUT:
<div class="alert alert-warning">
    <strong>{{ translate('Super Admin Payment Gateway') }}</strong><br>
    {{ translate('This payment method is exclusively available for Super Admin users.') }}
</div>
--}}
```

---

## How Payment Gateway Selection Works

The system determines which payment gateways to show users through this logic:

1. **StoreController.php** (line 80): Gets active payment methods
   ```php
   $payment_gateways = $this->payment_method_repository->paymentMethods(config('settings.general_status.active'));
   ```

2. **Package Model** (line 38-41): Defines relationship with payment methods
   ```php
   public function payment_methods(): HasMany
   {
       return $this->hasMany(PackagePaymentMethod::class, 'package_id');
   }
   ```

3. **Database Tables**:
   - `tl_saas_payment_methods`: Stores payment method info and status
   - `tl_saas_package_has_payment_methods`: Associates payment methods with packages

4. **User Selection**: Only payment methods that are:
   - ✅ Active (`status = 1`)
   - ✅ Associated with the selected package
   - ✅ Not restricted by controller logic

---

## Testing the Fix

After applying all changes:

1. **Check Database**: Verify multipurcpay is active and associated with packages
2. **Test User Flow**: 
   - Login as regular user (not Super Admin)
   - Go to store creation
   - Select a package
   - Verify multipurcpay appears in payment gateway options
3. **Test Payment**: Complete a test payment to ensure functionality

---

## Files Modified

- ✅ `saas/fix_multipurcpay_visibility.sql` - Database fixes
- ✅ `saas/src/Http/Controllers/Payment/MultipurcpayController.php` - Remove Super Admin restrictions
- ✅ `saas/views/payments/gateways/multipurcpay/configuration.blade.php` - Remove warning
- ✅ `saas/views/payments/gateways/multipurcpay/index.blade.php` - Remove warning

---

## Summary

The main issue was that **multipurcpay was restricted to Super Admin users only**. By removing these restrictions and ensuring proper database associations, all users will now be able to see and use the multipurcpay payment gateway during store creation.

**Key Changes:**
1. 🔧 Fixed database associations
2. 🚫 Removed Super Admin restrictions
3. 🎨 Updated UI to remove warnings
4. ✅ Made multipurcpay available to all users