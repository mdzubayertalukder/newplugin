@extends('core::base.layouts.master')

@section('title')
Dropshipping Order Details
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ translate('Dropshipping Order Details') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.dropshipping.orders.index') }}" class="btn btn-secondary">
                            <i class="icofont-arrow-left"></i> {{ translate('Back to Orders') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Basic Order Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Order Information') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>{{ translate('Order Number') }}</th>
                                            <td>{{ $order->order_number ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Status') }}</th>
                                            <td>
                                                <span class="badge badge-{{ $order->status == 'pending' ? 'warning' : ($order->status == 'approved' ? 'success' : 'danger') }}">
                                                    {{ ucfirst($order->status ?? 'Unknown') }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Tenant ID') }}</th>
                                            <td>{{ $order->tenant_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Created At') }}</th>
                                            <td>{{ $order->created_at ? date('Y-m-d H:i:s', strtotime($order->created_at)) : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Database') }}</th>
                                            <td>{{ $order->tenant_database ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Product Information') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>{{ translate('Product Name') }}</th>
                                            <td>{{ $order->product_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Product SKU') }}</th>
                                            <td>{{ $order->product_sku ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Quantity') }}</th>
                                            <td>{{ $order->quantity ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Unit Price') }}</th>
                                            <td>${{ number_format($order->unit_price ?? 0, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Total Amount') }}</th>
                                            <td>${{ number_format($order->total_amount ?? 0, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Commission') }}</th>
                                            <td>${{ number_format($order->commission_amount ?? 0, 2) }} ({{ $order->commission_rate ?? 0 }}%)</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Tenant Earning') }}</th>
                                            <td>${{ number_format($order->tenant_earning ?? 0, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Customer Information') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>{{ translate('Customer Name') }}</th>
                                            <td>{{ $order->customer_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Customer Email') }}</th>
                                            <td>{{ $order->customer_email ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Customer Phone') }}</th>
                                            <td>{{ $order->customer_phone ?? 'N/A' }}</td>
                                        </tr>
                                        @if(isset($order->customer_info))
                                        <tr>
                                            <th>{{ translate('Customer Type') }}</th>
                                            <td>{{ translate('Registered Customer') }}</td>
                                        </tr>
                                        @elseif(isset($order->guest_customer_info))
                                        <tr>
                                            <th>{{ translate('Customer Type') }}</th>
                                            <td>{{ translate('Guest Customer') }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Notes & Comments') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>{{ translate('Fulfillment Note') }}</th>
                                            <td>{{ $order->fulfillment_note ?? 'No notes' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Admin Notes') }}</th>
                                            <td>{{ $order->admin_notes ?? 'No admin notes' }}</td>
                                        </tr>
                                        @if($order->status == 'rejected')
                                        <tr>
                                            <th>{{ translate('Rejection Reason') }}</th>
                                            <td>{{ $order->rejection_reason ?? 'No reason provided' }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    @if(isset($order->shipping_info) && $order->shipping_info)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Shipping Address') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>{{ translate('Name') }}</th>
                                            <td>{{ $order->shipping_info->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Address') }}</th>
                                            <td>{{ $order->shipping_info->address ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Phone') }}</th>
                                            <td>{{ $order->shipping_info->phone ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('City') }}</th>
                                            <td>{{ $order->shipping_info->city ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('State') }}</th>
                                            <td>{{ $order->shipping_info->state ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('Country') }}</th>
                                            <td>{{ $order->shipping_info->country ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ translate('ZIP Code') }}</th>
                                            <td>{{ $order->shipping_info->zip_code ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Shipping Address') }}</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">{{ translate('No shipping information found for this order.') }}</p>
                                    @if($order->shipping_address)
                                    <p class="text-info">{{ translate('Shipping address from order:') }} {{ $order->shipping_address }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Order Products -->
                    @if(isset($order->order_products) && count($order->order_products) > 0)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Order Products') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>{{ translate('Product Name') }}</th>
                                                    <th>{{ translate('SKU') }}</th>
                                                    <th>{{ translate('Quantity') }}</th>
                                                    <th>{{ translate('Unit Price') }}</th>
                                                    <th>{{ translate('Total') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($order->order_products as $product)
                                                <tr>
                                                    <td>{{ $product->product_name ?? 'N/A' }}</td>
                                                    <td>{{ $product->product_sku ?? 'N/A' }}</td>
                                                    <td>{{ $product->quantity ?? 'N/A' }}</td>
                                                    <td>${{ number_format($product->unit_price ?? 0, 2) }}</td>
                                                    <td>${{ number_format(($product->unit_price ?? 0) * ($product->quantity ?? 1), 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Debug Information -->
                    @if(isset($order->debug_info))
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ translate('Debug Information') }}</h5>
                                </div>
                                <div class="card-body">
                                    <pre>{{ print_r($order->debug_info, true) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection