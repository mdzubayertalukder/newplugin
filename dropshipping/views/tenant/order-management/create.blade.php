@extends('core::base.layouts.master')

@section('title')
{{__('Submit New Order')}}
@endsection

@section('custom_css')
<style>
    .order-selection-card {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .order-selection-card:hover {
        border-color: #6045e2;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .order-selection-card.selected {
        border-color: #6045e2;
        background-color: #f8f9ff;
    }

    .order-selection-card .selection-indicator {
        position: absolute;
        top: 10px;
        right: 15px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 2px solid #dee2e6;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .order-selection-card.selected .selection-indicator {
        border-color: #6045e2;
        background: #6045e2;
        color: white;
    }

    .order-amount {
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
    }

    .order-earning {
        font-size: 14px;
        color: #28a745;
        font-weight: 600;
    }

    .order-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-processing {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-delivered {
        background: #d4edda;
        color: #155724;
    }

    .commission-info {
        background: linear-gradient(135deg, #6045e2, #7c5ce8);
        color: white;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        display: none;
    }

    .commission-stat {
        text-align: center;
        margin-bottom: 10px;
    }

    .commission-stat h5 {
        margin: 0 0 5px 0;
        font-size: 14px;
        opacity: 0.9;
    }

    .commission-stat h4 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        width: fit-content;
    }

    .quantity-btn {
        width: 35px;
        height: 35px;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: 600;
        color: #6045e2;
    }

    .quantity-btn:hover {
        background: #6045e2;
        color: white;
        border-color: #6045e2;
    }

    .quantity-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .quantity-input {
        width: 60px;
        text-align: center;
        border: 1px solid #dee2e6;
        border-left: none;
        border-right: none;
        height: 35px;
        margin: 0;
    }

    .order-summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .summary-row:last-child {
        border-bottom: none;
        font-weight: 600;
        font-size: 16px;
        margin-top: 10px;
        padding-top: 15px;
        border-top: 2px solid #dee2e6;
    }

    .summary-row.earning {
        color: #28a745;
    }

    .breadcrumb {
        background: transparent;
        padding: 0;
        margin: 0 0 20px 0;
    }

    .breadcrumb-item a {
        color: #6045e2;
        text-decoration: none;
    }

    .alert-empty {
        text-align: center;
        padding: 40px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .alert-empty i {
        font-size: 48px;
        color: #dee2e6;
        margin-bottom: 15px;
    }
</style>
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dropshipping.order.management') }}">{{__('Order Management')}}</a></li>
                <li class="breadcrumb-item active">{{__('Submit New Order')}}</li>
            </ol>
        </nav>

        <!-- Page Title -->
        <div class="form-element py-30 mb-30">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="font-20 mb-2">{{__('Convert Order to Dropshipping')}}</h4>
                    <p class="text-muted mb-0">{{__('Select an existing order to convert it to a dropshipping order')}}</p>
                </div>
                <a href="{{ route('dropshipping.order.management') }}" class="btn btn-outline-primary">
                    <i class="icofont-arrow-left"></i> {{__('Back to Orders')}}
                </a>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('dropshipping.order.store') }}" method="POST" id="orderForm">
    @csrf

    <div class="row">
        <!-- Order Selection -->
        <div class="col-lg-8">
            <div class="card mb-30">
                <div class="card-header bg-white py-3">
                    <h5 class="font-18 mb-0">
                        <i class="icofont-shopping-cart"></i> {{__('Step 1: Select Order')}}
                    </h5>
                </div>
                <div class="card-body">
                    @if($inhouseOrders->count() > 0)
                    <p class="text-muted mb-3">{{__('Choose an existing inhouse order to convert to dropshipping')}}</p>

                    <div class="row">
                        @foreach($inhouseOrders as $order)
                        <div class="col-md-6">
                            <div class="order-selection-card" onclick="selectOrder('{{ $order->id }}')">
                                <input type="radio" name="order_id" value="{{ $order->id }}"
                                    id="order_{{ $order->id }}" style="display: none;" required>

                                <div class="selection-indicator">
                                    <i class="icofont-check"></i>
                                </div>

                                <h6 class="mb-2">{{__('Order')}} #{{ $order->order_code }}</h6>

                                <small class="text-muted d-block mb-2">
                                    {{__('Date')}}: {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}
                                </small>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="order-amount">${{ number_format($order->total_payable_amount, 2) }}</span>
                                    <span class="order-earning">{{__('Earn')}}: ${{ number_format($order->total_payable_amount * 0.2, 2) }}</span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">{{__('Customer')}}: {{ $order->customer_name ?? $order->guest_customer ?? 'Guest' }}</small>
                                    <span class="order-status status-{{ strtolower($order->delivery_status ?? 'pending') }}">
                                        {{ ucfirst($order->delivery_status ?? 'Pending') }}
                                    </span>
                                </div>

                                <small class="text-muted">{{__('Products')}}: {{ $order->total_product ?? 0 }} {{__('item(s)')}}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($inhouseOrders->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $inhouseOrders->links() }}
                    </div>
                    @endif
                    @else
                    <div class="alert-empty">
                        <i class="icofont-shopping-cart"></i>
                        <h5>{{__('No Orders Available')}}</h5>
                        <p class="text-muted">{{__('No inhouse orders found to convert to dropshipping.')}}</p>
                        <a href="{{ route('plugin.tlcommercecore.orders.inhouse') }}" class="btn btn-primary">
                            <i class="icofont-eye"></i> {{__('View Orders')}}
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Commission Information -->
            <div class="commission-info" id="commissionInfo">
                <h6 class="mb-3"><i class="icofont-chart-line"></i> {{__('Earnings Overview')}}</h6>
                <div class="row">
                    <div class="col-3">
                        <div class="commission-stat">
                            <h5>{{__('Order Amount')}}</h5>
                            <h4 id="orderAmount">$0.00</h4>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="commission-stat">
                            <h5>{{__('Commission')}}</h5>
                            <h4>20%</h4>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="commission-stat">
                            <h5>{{__('Your Earning')}}</h5>
                            <h4 id="totalEarning">$0.00</h4>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="commission-stat">
                            <h5>{{__('Status')}}</h5>
                            <h4 id="orderStatus">-</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dropshipping Details -->
            <div class="card mb-30">
                <div class="card-header bg-white py-3">
                    <h5 class="font-18 mb-0">
                        <i class="icofont-truck"></i> {{__('Step 2: Dropshipping Details')}}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="form-row mb-20">
                        <div class="col-sm-4">
                            <label class="font-14 bold black">{{__('Fulfillment Note')}}</label>
                        </div>
                        <div class="col-sm-8">
                            <textarea class="theme-input-style" id="fulfillment_note" name="fulfillment_note"
                                rows="3" placeholder="{{__('Add any special instructions for dropshipping fulfillment (optional)')}}">{{ old('fulfillment_note') }}</textarea>
                            @error('fulfillment_note')
                            <div class="invalid-input">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-30" id="orderSummaryCard" style="display: none;">
                <div class="card-header bg-white py-3">
                    <h5 class="font-18 mb-0">
                        <i class="icofont-receipt"></i> {{__('Conversion Summary')}}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>{{__('Order Code')}}</span>
                            <span id="summaryOrderCode">-</span>
                        </div>
                        <div class="summary-row">
                            <span>{{__('Customer')}}</span>
                            <span id="summaryCustomer">-</span>
                        </div>
                        <div class="summary-row">
                            <span>{{__('Order Amount')}}</span>
                            <span id="summaryAmount">-</span>
                        </div>
                        <div class="summary-row">
                            <span>{{__('Products')}}</span>
                            <span id="summaryProducts">-</span>
                        </div>
                        <div class="summary-row earning">
                            <span>{{__('Your Earning')}}</span>
                            <span id="summaryEarning">-</span>
                        </div>
                    </div>

                    <button type="submit" class="btn long" id="submitBtn" disabled>
                        <i class="icofont-truck"></i> {{__('Convert to Dropshipping')}}
                    </button>
                </div>
            </div>

            <!-- Help Information -->
            <div class="card mb-30">
                <div class="card-header bg-white py-3">
                    <h5 class="font-18 mb-0">
                        <i class="icofont-info-circle"></i> {{__('Information')}}
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="icofont-check text-success"></i> {{__('Earn 20% commission on each converted order')}}</li>
                        <li class="mb-2"><i class="icofont-check text-success"></i> {{__('Orders are processed and fulfilled via dropshipping')}}</li>
                        <li class="mb-2"><i class="icofont-check text-success"></i> {{__('Customer will receive tracking information')}}</li>
                        <li class="mb-0"><i class="icofont-check text-success"></i> {{__('Payments processed after delivery')}}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('custom_scripts')
<script>
    let selectedOrder = null;

    function selectOrder(orderId) {
        // Remove selection from all orders
        document.querySelectorAll('.order-selection-card').forEach(card => {
            card.classList.remove('selected');
        });

        // Add selection to clicked order
        const selectedCard = document.querySelector(`input[value="${orderId}"]`).closest('.order-selection-card');
        selectedCard.classList.add('selected');

        // Check the radio button
        document.getElementById(`order_${orderId}`).checked = true;

        // Get order details from the card
        const orderCode = selectedCard.querySelector('h6').textContent.trim();
        const orderAmount = parseFloat(selectedCard.querySelector('.order-amount').textContent.replace('$', ''));
        const customerName = selectedCard.querySelector('small:nth-of-type(2)').textContent.replace('Customer: ', '');
        const productCount = selectedCard.querySelector('small:last-of-type').textContent;
        const status = selectedCard.querySelector('.order-status').textContent.trim();

        selectedOrder = {
            id: orderId,
            code: orderCode,
            amount: orderAmount,
            customer: customerName,
            products: productCount,
            status: status
        };

        // Show commission info and summary
        document.getElementById('commissionInfo').style.display = 'block';
        document.getElementById('orderSummaryCard').style.display = 'block';

        // Update displays
        updateEarnings();
        updateOrderSummary();
        checkFormCompletion();
    }

    function updateEarnings() {
        if (!selectedOrder) return;

        const totalEarning = selectedOrder.amount * 0.2; // 20% commission

        document.getElementById('orderAmount').textContent = `$${selectedOrder.amount.toFixed(2)}`;
        document.getElementById('totalEarning').textContent = `$${totalEarning.toFixed(2)}`;
        document.getElementById('orderStatus').textContent = selectedOrder.status;
    }

    function updateOrderSummary() {
        if (!selectedOrder) return;

        const totalEarning = selectedOrder.amount * 0.2;

        document.getElementById('summaryOrderCode').textContent = selectedOrder.code;
        document.getElementById('summaryCustomer').textContent = selectedOrder.customer;
        document.getElementById('summaryAmount').textContent = `$${selectedOrder.amount.toFixed(2)}`;
        document.getElementById('summaryProducts').textContent = selectedOrder.products;
        document.getElementById('summaryEarning').textContent = `$${totalEarning.toFixed(2)}`;
    }

    function checkFormCompletion() {
        const hasSelectedOrder = selectedOrder !== null;
        document.getElementById('submitBtn').disabled = !hasSelectedOrder;
    }

    // Form validation
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        if (!selectedOrder) {
            e.preventDefault();
            alert('{{__("Please select an order first")}}');
            return;
        }
    });
</script>
@endsection