@extends('core::base.layouts.master')

@section('title')
{{ translate('Order Details') }} - {{ $order->order_number }}
@endsection

@section('style')
<style>
    /* Modern Page Layout */
    .page-header-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .page-header-modern h1 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .page-header-modern p {
        opacity: 0.9;
        margin: 5px 0 0 0;
        font-size: 1rem;
    }

    /* Enhanced Cards */
    .info-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .info-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    .info-card h5 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .info-card h5 i {
        margin-right: 10px;
        color: #667eea;
        background: rgba(102, 126, 234, 0.1);
        padding: 10px;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .info-table {
        margin: 0;
    }

    .info-table td {
        padding: 12px 0;
        border: none;
        border-bottom: 1px solid #f1f3f4;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        color: #495057;
        width: 140px;
    }

    .info-table td:last-child {
        color: #2c3e50;
    }

    /* Status Badge Enhancement */
    .status-badge {
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        display: inline-flex;
        align-items: center;
    }

    .status-badge i {
        margin-right: 5px;
        font-size: 1rem;
    }

    .status-pending {
        background: linear-gradient(135deg, #ffc107, #ffb000);
        color: #fff;
        box-shadow: 0 3px 10px rgba(255, 193, 7, 0.3);
    }

    .status-approved {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: #fff;
        box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
    }

    .status-processing {
        background: linear-gradient(135deg, #17a2b8, #007bff);
        color: #fff;
        box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
    }

    .status-shipped {
        background: linear-gradient(135deg, #007bff, #6610f2);
        color: #fff;
        box-shadow: 0 3px 10px rgba(0, 123, 255, 0.3);
    }

    .status-delivered {
        background: linear-gradient(135deg, #28a745, #34ce57);
        color: #fff;
        box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
    }

    .status-cancelled {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: #fff;
        box-shadow: 0 3px 10px rgba(108, 117, 125, 0.3);
    }

    .status-rejected {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: #fff;
        box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
    }

    /* Enhanced Action Buttons */
    .action-card {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        text-align: center;
    }

    .btn-action-modern {
        border-radius: 12px;
        padding: 15px 30px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
        margin: 0 10px;
        min-width: 150px;
    }

    .btn-action-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .btn-action-modern.btn-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-action-modern.btn-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .btn-back {
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        background: linear-gradient(135deg, #6c757d, #495057);
        border: none;
        color: white;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-back i {
        margin-right: 8px;
    }

    /* Notes Enhancement */
    .note-alert {
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .note-alert::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
    }

    .note-alert.alert-info::before {
        background: linear-gradient(180deg, #17a2b8, #007bff);
    }

    .note-alert.alert-success::before {
        background: linear-gradient(180deg, #28a745, #20c997);
    }

    .note-alert.alert-danger::before {
        background: linear-gradient(180deg, #dc3545, #c82333);
    }

    /* Value Highlighting */
    .value-highlight {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .value-success {
        color: #28a745 !important;
        font-weight: 700;
    }

    .value-amount {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
    }

    /* Timeline for status */
    .status-timeline {
        position: relative;
        padding-left: 30px;
    }

    .status-timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 20px;
        margin-bottom: 15px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -22px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #667eea;
        border: 3px solid white;
        box-shadow: 0 0 0 2px #667eea;
    }

    .timeline-date {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 5px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-header-modern {
            padding: 20px;
            text-align: center;
        }

        .info-card {
            padding: 20px;
            margin-bottom: 20px;
        }

        .btn-action-modern {
            margin: 5px;
            min-width: 120px;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fadeInUp 0.6s ease forwards;
    }
</style>
@endsection

@section('main_content')
<div class="container-fluid">
    <!-- Modern Page Header -->
    <div class="page-header-modern animate-fade-in">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1><i class="icofont-file-document"></i> {{ translate('Order Details') }}</h1>
                <p>{{ $order->order_number }} - {{ translate('Submitted by Tenant') }} {{ $order->tenant_id }}</p>
            </div>
            <div class="d-flex mt-3 mt-md-0">
                <a href="{{ route('admin.dropshipping.orders.index') }}" class="btn-back">
                    <i class="icofont-arrow-left"></i> {{ translate('Back to Orders') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Order Summary --}}
        <div class="col-lg-6">
            <div class="info-card animate-fade-in" style="animation-delay: 0.1s">
                <h5><i class="icofont-info-circle"></i> {{ translate('Order Information') }}</h5>
                <table class="table info-table">
                    <tr>
                        <td>{{ translate('Order Number') }}:</td>
                        <td><span class="value-highlight">{{ $order->order_number }}</span></td>
                    </tr>
                    <tr>
                        <td>{{ translate('Status') }}:</td>
                        <td>
                            <span class="status-badge status-{{ $order->status }}">
                                @if($order->status === 'pending')
                                <i class="icofont-clock-time"></i>
                                @elseif($order->status === 'approved')
                                <i class="icofont-check-circled"></i>
                                @elseif($order->status === 'rejected')
                                <i class="icofont-close-circled"></i>
                                @elseif($order->status === 'processing')
                                <i class="icofont-gear"></i>
                                @elseif($order->status === 'shipped')
                                <i class="icofont-truck"></i>
                                @elseif($order->status === 'delivered')
                                <i class="icofont-check-alt"></i>
                                @endif
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>{{ translate('Tenant ID') }}:</td>
                        <td><span class="badge bg-info text-white">{{ $order->tenant_id }}</span></td>
                    </tr>
                    <tr>
                        <td>{{ translate('Submitted By') }}:</td>
                        <td>
                            @if($order->submittedBy)
                            <strong>{{ $order->submittedBy->name }}</strong>
                            <br><small class="text-muted">{{ $order->submittedBy->email }}</small>
                            @else
                            <span class="text-muted">{{ translate('User not found') }}</span>
                            @endif
                        </td>
                    </tr>
                    @if($order->approvedBy)
                    <tr>
                        <td>{{ translate('Approved By') }}:</td>
                        <td>
                            <strong>{{ $order->approvedBy->name }}</strong>
                            <br><small class="text-muted">{{ $order->approvedBy->email }}</small>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Customer Information --}}
        <div class="col-lg-6">
            <div class="info-card animate-fade-in" style="animation-delay: 0.2s">
                <h5><i class="icofont-user"></i> {{ translate('Customer Information') }}</h5>
                <table class="table info-table">
                    <tr>
                        <td>{{ translate('Name') }}:</td>
                        <td><span class="value-highlight">{{ $order->customer_name }}</span></td>
                    </tr>
                    @if($order->customer_email)
                    <tr>
                        <td>{{ translate('Email') }}:</td>
                        <td>
                            <a href="mailto:{{ $order->customer_email }}" class="text-primary">
                                {{ $order->customer_email }}
                            </a>
                        </td>
                    </tr>
                    @endif
                    @if($order->customer_phone)
                    <tr>
                        <td>{{ translate('Phone') }}:</td>
                        <td>
                            <a href="tel:{{ $order->customer_phone }}" class="text-primary">
                                {{ $order->customer_phone }}
                            </a>
                        </td>
                    </tr>
                    @endif
                    @if($order->shipping_address)
                    <tr>
                        <td>{{ translate('Address') }}:</td>
                        <td>{{ $order->shipping_address }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Product Information --}}
        <div class="col-lg-6">
            <div class="info-card animate-fade-in" style="animation-delay: 0.3s">
                <h5><i class="icofont-box"></i> {{ translate('Product Information') }}</h5>
                <table class="table info-table">
                    <tr>
                        <td>{{ translate('Product') }}:</td>
                        <td><span class="value-highlight">{{ $order->product_name }}</span></td>
                    </tr>
                    @if($order->product_sku)
                    <tr>
                        <td>{{ translate('SKU') }}:</td>
                        <td><code>{{ $order->product_sku }}</code></td>
                    </tr>
                    @endif
                    <tr>
                        <td>{{ translate('Quantity') }}:</td>
                        <td><span class="badge bg-light text-dark">{{ $order->quantity }}</span></td>
                    </tr>
                    <tr>
                        <td>{{ translate('Unit Price') }}:</td>
                        <td><span class="value-amount">${{ number_format($order->unit_price, 2) }}</span></td>
                    </tr>
                    <tr>
                        <td>{{ translate('Total Amount') }}:</td>
                        <td><span class="value-amount text-primary">${{ number_format($order->total_amount, 2) }}</span></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Commission Information --}}
        <div class="col-lg-6">
            <div class="info-card animate-fade-in" style="animation-delay: 0.4s">
                <h5><i class="icofont-money"></i> {{ translate('Commission Information') }}</h5>
                <table class="table info-table">
                    <tr>
                        <td>{{ translate('Commission Rate') }}:</td>
                        <td><span class="badge bg-info text-white">{{ $order->commission_rate }}%</span></td>
                    </tr>
                    <tr>
                        <td>{{ translate('Commission') }}:</td>
                        <td><span class="value-amount">${{ number_format($order->commission_amount, 2) }}</span></td>
                    </tr>
                    <tr>
                        <td>{{ translate('Tenant Earning') }}:</td>
                        <td><span class="value-amount value-success">${{ number_format($order->tenant_earning, 2) }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Timeline Section --}}
    <div class="row">
        <div class="col-12">
            <div class="info-card animate-fade-in" style="animation-delay: 0.5s">
                <h5><i class="icofont-calendar"></i> {{ translate('Order Timeline') }}</h5>
                <div class="status-timeline">
                    <div class="timeline-item">
                        <strong>{{ translate('Order Submitted') }}</strong>
                        <div class="timeline-date">{{ $order->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @if($order->approved_at)
                    <div class="timeline-item">
                        <strong>{{ translate('Order Approved') }}</strong>
                        <div class="timeline-date">{{ $order->approved_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                    @if($order->shipped_at)
                    <div class="timeline-item">
                        <strong>{{ translate('Order Shipped') }}</strong>
                        <div class="timeline-date">{{ $order->shipped_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                    @if($order->delivered_at)
                    <div class="timeline-item">
                        <strong>{{ translate('Order Delivered') }}</strong>
                        <div class="timeline-date">{{ $order->delivered_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Notes Section --}}
    @if($order->fulfillment_note || $order->admin_notes || $order->rejection_reason)
    <div class="row">
        <div class="col-12">
            <div class="info-card animate-fade-in" style="animation-delay: 0.6s">
                <h5><i class="icofont-comment"></i> {{ translate('Notes & Comments') }}</h5>
                @if($order->fulfillment_note)
                <div class="note-alert alert-info">
                    <strong>{{ translate('Fulfillment Note') }}:</strong><br>
                    {{ $order->fulfillment_note }}
                </div>
                @endif
                @if($order->admin_notes)
                <div class="note-alert alert-success">
                    <strong>{{ translate('Admin Notes') }}:</strong><br>
                    {{ $order->admin_notes }}
                </div>
                @endif
                @if($order->rejection_reason)
                <div class="note-alert alert-danger">
                    <strong>{{ translate('Rejection Reason') }}:</strong><br>
                    {{ $order->rejection_reason }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Action Buttons --}}
    @if($order->status === 'pending')
    <div class="row">
        <div class="col-12">
            <div class="action-card animate-fade-in" style="animation-delay: 0.7s">
                <h6 class="mb-4">{{ translate('Order Actions') }}</h6>
                <div class="d-flex justify-content-center flex-wrap">
                    <button class="btn btn-action-modern btn-success" onclick="approveOrder()">
                        <i class="icofont-check"></i> {{ translate('Approve Order') }}
                    </button>
                    <button class="btn btn-action-modern btn-danger" onclick="rejectOrder()">
                        <i class="icofont-close"></i> {{ translate('Reject Order') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Enhanced Modal for Order Actions -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Approve Order') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Admin Notes (Optional)') }}</label>
                        <textarea class="form-control" id="approveNotes" rows="3"
                            placeholder="{{ translate('Add any notes for this approval...') }}"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-success" onclick="confirmApprove()">{{ translate('Approve Order') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Reject Order') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <div class="mb-3">
                        <label class="form-label">{{ translate('Rejection Reason') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectReason" rows="3" required
                            placeholder="{{ translate('Please provide a reason for rejecting this order...') }}"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">{{ translate('Reject Order') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    function approveOrder() {
        const modal = new bootstrap.Modal(document.getElementById('approveModal'));
        modal.show();
    }

    function rejectOrder() {
        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    }

    function confirmApprove() {
        const notes = document.getElementById('approveNotes').value;

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
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ translate("Error approving order") }}');
            });
    }

    function confirmReject() {
        const reason = document.getElementById('rejectReason').value.trim();
        if (!reason) {
            alert('{{ translate("Please provide a rejection reason") }}');
            return;
        }

        fetch('/admin/dropshipping/orders/{{ $order->id }}/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    rejection_reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '{{ translate("Error rejecting order") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ translate("Error rejecting order") }}');
            });
    }

    // Add smooth animations on page load
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.animate-fade-in');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';

            setTimeout(() => {
                el.style.transition = 'all 0.6s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
@endsection