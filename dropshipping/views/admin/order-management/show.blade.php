@extends('core::base.layouts.master')

@section('title')
{{ translate('Order Details') }} - {{ $order->order_number }}
@endsection

@section('main_content')
<div class="row">
    <div class="col-12">
        <div class="card mb-30">
            <div class="card-body border-bottom2 mb-20">
                <div class="d-sm-flex justify-content-between align-items-center">
                    <h4 class="font-20">{{ translate('Order Details') }}: {{ $order->order_number }}</h4>
                    <div>
                        <a href="{{ route('admin.dropshipping.orders.index') }}" class="btn btn-outline-primary">
                            <i class="icofont-arrow-left"></i> {{ translate('Back to Orders') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Order Summary --}}
                <div class="col-lg-6 mb-4">
                    <h5>{{ translate('Order Information') }}</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>{{ translate('Order Number') }}:</strong></td>
                            <td>{{ $order->order_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ translate('Status') }}:</strong></td>
                            <td>
                                @php
                                $statusClasses = [
                                'pending' => 'badge-warning',
                                'approved' => 'badge-success',
                                'rejected' => 'badge-danger',
                                'processing' => 'badge-info',
                                'shipped' => 'badge-primary',
                                'delivered' => 'badge-success',
                                'cancelled' => 'badge-secondary'
                                ];
                                $statusClass = $statusClasses[$order->status] ?? 'badge-secondary';
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{{ translate('Tenant ID') }}:</strong></td>
                            <td>{{ $order->tenant_id }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ translate('Submitted Date') }}:</strong></td>
                            <td>{{ $order->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @if($order->approved_at)
                        <tr>
                            <td><strong>{{ translate('Approved Date') }}:</strong></td>
                            <td>{{ $order->approved_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endif
                        @if($order->shipped_at)
                        <tr>
                            <td><strong>{{ translate('Shipped Date') }}:</strong></td>
                            <td>{{ $order->shipped_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endif
                        @if($order->delivered_at)
                        <tr>
                            <td><strong>{{ translate('Delivered Date') }}:</strong></td>
                            <td>{{ $order->delivered_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- Customer Information --}}
                <div class="col-lg-6 mb-4">
                    <h5>{{ translate('Customer Information') }}</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>{{ translate('Name') }}:</strong></td>
                            <td>{{ $order->customer_name }}</td>
                        </tr>
                        @if($order->customer_email)
                        <tr>
                            <td><strong>{{ translate('Email') }}:</strong></td>
                            <td>{{ $order->customer_email }}</td>
                        </tr>
                        @endif
                        @if($order->customer_phone)
                        <tr>
                            <td><strong>{{ translate('Phone') }}:</strong></td>
                            <td>{{ $order->customer_phone }}</td>
                        </tr>
                        @endif
                        @if($order->shipping_address)
                        <tr>
                            <td><strong>{{ translate('Shipping Address') }}:</strong></td>
                            <td>{{ $order->shipping_address }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="row">
                {{-- Product Information --}}
                <div class="col-lg-6 mb-4">
                    <h5>{{ translate('Product Information') }}</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>{{ translate('Product Name') }}:</strong></td>
                            <td>{{ $order->product_name }}</td>
                        </tr>
                        @if($order->product_sku)
                        <tr>
                            <td><strong>{{ translate('SKU') }}:</strong></td>
                            <td>{{ $order->product_sku }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>{{ translate('Quantity') }}:</strong></td>
                            <td>{{ $order->quantity }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ translate('Unit Price') }}:</strong></td>
                            <td>${{ number_format($order->unit_price, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ translate('Total Amount') }}:</strong></td>
                            <td><strong>${{ number_format($order->total_amount, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>

                {{-- Commission Information --}}
                <div class="col-lg-6 mb-4">
                    <h5>{{ translate('Commission Information') }}</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>{{ translate('Commission Rate') }}:</strong></td>
                            <td>{{ $order->commission_rate }}%</td>
                        </tr>
                        <tr>
                            <td><strong>{{ translate('Commission Amount') }}:</strong></td>
                            <td>${{ number_format($order->commission_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>{{ translate('Tenant Earning') }}:</strong></td>
                            <td><strong class="text-success">${{ number_format($order->tenant_earning, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Notes Section --}}
            @if($order->fulfillment_note || $order->admin_notes || $order->rejection_reason)
            <div class="row">
                <div class="col-12 mb-4">
                    <h5>{{ translate('Notes') }}</h5>
                    @if($order->fulfillment_note)
                    <div class="alert alert-info">
                        <strong>{{ translate('Fulfillment Note') }}:</strong><br>
                        {{ $order->fulfillment_note }}
                    </div>
                    @endif
                    @if($order->admin_notes)
                    <div class="alert alert-success">
                        <strong>{{ translate('Admin Notes') }}:</strong><br>
                        {{ $order->admin_notes }}
                    </div>
                    @endif
                    @if($order->rejection_reason)
                    <div class="alert alert-danger">
                        <strong>{{ translate('Rejection Reason') }}:</strong><br>
                        {{ $order->rejection_reason }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Action Buttons --}}
            @if($order->status === 'pending')
            <div class="row">
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6>{{ translate('Order Actions') }}</h6>
                            <div class="btn-group">
                                <button class="btn btn-success" onclick="approveOrder()">
                                    <i class="icofont-check"></i> {{ translate('Approve Order') }}
                                </button>
                                <button class="btn btn-danger" onclick="rejectOrder()">
                                    <i class="icofont-close"></i> {{ translate('Reject Order') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@if($order->status === 'pending')
<script>
    function approveOrder() {
        const notes = prompt('{{ translate("Add any notes for approval (optional):") }}') || '';
        if (confirm('{{ translate("Are you sure you want to approve this order?") }}')) {
            fetch('/admin/dropshipping/orders/{{ $order->id }}/approve', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        admin_notes: notes
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || '{{ translate("Error approving order") }}');
                    }
                });
        }
    }

    function rejectOrder() {
        const reason = prompt('{{ translate("Please provide a reason for rejection:") }}');
        if (reason && reason.trim()) {
            fetch('/admin/dropshipping/orders/{{ $order->id }}/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        rejection_reason: reason.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || '{{ translate("Error rejecting order") }}');
                    }
                });
        }
    }
</script>
@endif
@endsection