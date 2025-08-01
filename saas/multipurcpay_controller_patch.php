<?php
/**
 * PATCH FOR MULTIPURCPAY CONTROLLER
 * 
 * This patch removes the Super Admin restriction from MultipurcpayController
 * Apply this by replacing the existing MultipurcpayController.php content
 */

// STEP 1: Remove Super Admin checks from these methods:
// - index() method: Remove lines 32-34
// - createCharge() method: Remove lines 60-66  
// - success() method: Remove lines 304-306
// - cancel() method: Remove lines 384-386

// STEP 2: Update the index() method to include missing data for the view:
/*
Replace the index() method with:

public function index()
{
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
*/

// STEP 3: Remove the isSuperAdmin() method entirely (lines 422-433)

// STEP 4: Update configuration view to remove Super Admin warning
// In saas/views/payments/gateways/multipurcpay/configuration.blade.php
// Remove or comment out lines 71-74:
/*
<div class="alert alert-warning mb-20">
    <strong>{{ translate('Super Admin Only') }}</strong><br>
    {{ translate('This payment gateway is only available for Super Admin users when creating stores.') }}
</div>
*/

// STEP 5: Update the payment view to remove Super Admin warning  
// In saas/views/payments/gateways/multipurcpay/index.blade.php
// Remove or comment out lines 157-160:
/*
<div class="alert alert-warning">
    <strong>{{ translate('Super Admin Payment Gateway') }}</strong><br>
    {{ translate('This payment method is exclusively available for Super Admin users.') }}
</div>
*/