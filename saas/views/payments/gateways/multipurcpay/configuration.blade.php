@php
$selecected_currency = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(
    $method->id,
    'multipurcpay_currency',
);
$default_currency = $selecected_currency == null ? getSaasDefaultCurrency() : $selecected_currency;
@endphp
<div class="border-top2 p-3 payment-method-item-body">
    <div class="configuration">
        <form id="credential-form">
            <input type="hidden" name="payment_id" value="{{ $method->id }}">
            <div class="form-group mb-20">
                <label class="black bold mb-2">{{ translate('Logo') }}</label>
                <div class="input-option">
                    @include('core::base.includes.media.media_input', [
                        'input' => 'multipurcpay_logo',
                        'data' => \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(
                            $method->id,
                            'multipurcpay_logo'),
                    ])
                    @if ($errors->has('multipurcpay_logo'))
                        <div class="invalid-input">{{ $errors->first('multipurcpay_logo') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="form-group mb-20">
                <label class="black bold">{{ translate('Currency') }}</label>
                <div class="mb-2">
                    <a href="{{ route('plugin.saas.all.currencies') }}"
                        class="mt-2">({{ translate('Please setup exchange rate for the choosed currency') }})</a>
                </div>
                <div class="input-option">
                    <select name="multipurcpay_currency" class="theme-input-style selectCurrency">
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency->code }}" class="text-uppercase"
                                {{ $currency->code == $default_currency ? 'selected' : '' }}>
                                {{ $currency->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group mb-20">
                <label class="black bold mb-2">{{ translate('API Key') }}</label>
                <div class="input-option">
                    <input type="text" class="theme-input-style" name="multipurcpay_api_key"
                        placeholder="Enter Multipurcpay API Key"
                        value="{{ \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue($method->id, 'multipurcpay_api_key') }}"
                        required />
                    @if ($errors->has('multipurcpay_api_key'))
                        <div class="invalid-input">{{ $errors->first('multipurcpay_api_key') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="form-group mb-20">
                <label class="black bold mb-2">{{ translate('Instruction') }}</label>
                <div class="input-option">
                    <textarea name="multipurcpay_instruction" class="theme-input-style">{{ \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue($method->id, 'multipurcpay_instruction') }}</textarea>
                    @if ($errors->has('multipurcpay_instruction'))
                        <div class="invalid-input">{{ $errors->first('multipurcpay_instruction') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="alert alert-warning mb-20">
                <strong>{{ translate('Super Admin Only') }}</strong><br>
                {{ translate('This payment gateway is only available for Super Admin users when creating stores.') }}
            </div>

            <div>
                <button class="btn long payment-credental-update-btn"
                    data-payment-btn="{{ $method->id }}">{{ translate('Save Changes') }}</button>
            </div>
        </form>
    </div>
    <div class="instruction">
        <a href="https://aidroppay.xyz" target="_blank">{{ translate('Multipurcpay') }}</a>
        <p>
            {{ translate('Super Admin can use Multipurcpay payment gateway for store creation payments. This is a self-hosted payment solution.') }}
        </p>
        <p class="semi-bold">
            {{ translate('Configuration instruction for Multipurcpay') }}
        </p>
        <p>{{ translate('To use Multipurcpay, you need:') }}</p>
        <ol>
            <li style="list-style-type: decimal">
                <p>{{ translate('API Key: 1086227048687936865b7fa20065340062067396923687936865b805260911739') }}</p>
            </li>
            <li style="list-style-type: decimal">
                <p>{{ translate('Base URL: https://aidroppay.xyz/api') }}</p>
            </li>
            <li style="list-style-type: decimal">
                <p>{{ translate('Create Charge Endpoint: https://aidroppay.xyz/api/create-charge') }}</p>
            </li>
            <li style="list-style-type: decimal">
                <p>{{ translate('Verify Payment Endpoint: https://aidroppay.xyz/api/verify-payments') }}</p>
            </li>
            <li style="list-style-type: decimal">
                <p>{{ translate('This gateway is restricted to Super Admin users only') }}</p>
            </li>
        </ol>
        
        <div class="mt-3">
            <p class="semi-bold">{{ translate('API Integration Details:') }}</p>
            <ul>
                <li>{{ translate('Currency: BDT (Bangladesh Taka)') }}</li>
                <li>{{ translate('Return Type: GET') }}</li>
                <li>{{ translate('Webhook support included') }}</li>
                <li>{{ translate('Payment verification included') }}</li>
            </ul>
        </div>
    </div>
</div>