@extends('layouts.admin')
@section('styles')
@parent
<style>
    .datatable-Payment .clubman-retry-form { display: inline-block; margin-left: 10px; vertical-align: middle; }
    .datatable-Payment .clubman-retry {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 6px;
        background: #fff; color: #334155; font-size: 12px; font-weight: 600;
        line-height: 1.5; white-space: nowrap; cursor: pointer;
    }
    .datatable-Payment .clubman-retry:hover { background: #f1f5f9; border-color: #94a3b8; }
    .datatable-Payment .clubman-retry:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
    .datatable-Payment .clubman-retry:disabled { opacity: .65; cursor: wait; }
</style>
@endsection
@section('content')
@if(session('clubman_retry_error'))
    <div class="alert alert-danger">{{ session('clubman_retry_error') }}</div>
@endif
@can('payment_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('admin.payments.create') }}">
                {{ trans('global.add') }} {{ trans('cruds.payment.title_singular') }}
            </a>
        </div>
    </div>
@endcan
<div class="card">
    <div class="card-header">
        {{ trans('cruds.payment.title_singular') }} {{ trans('global.list') }}
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="example" class="table table-bordered table-striped table-hover datatable datatable-Payment">
                <thead>
                    <tr>
                        <th width="10">
                            {{ trans('cruds.payment.fields.id') }}
                        </th>
                        
                        <th>
                            {{ trans('cruds.payment.fields.member') }}
                        </th>
                        <th>
                            {{ trans('cruds.payment.fields.amount_paid') }}
                        </th>
                        <th>
                            {{ trans('cruds.payment.fields.gateway_name') }}
                        </th>
                        <th class="notexport">
                            {{ trans('cruds.payment.fields.comment') }}
                        </th>
                        <th>
                            {{ trans('cruds.payment.fields.status') }}
                        </th>
                        <th>Payment Datetime</th>
                        <th>Source</th>
                        <th>Clubman Status</th>
                        <th class="notexport">Status Update</th>
                        <th class="notexport">
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                    @php $gateway= basename(parse_url($payment->destination, PHP_URL_PATH)); @endphp                                                            
                    
                        <tr data-entry-id="{{ $payment->paid_for_id }}">
                            
                            <td>
                                {{ $payment->id ?? '' }}
                            </td>
                            
                            <td>
                                {{ $payment->member_name ?? '' }} {{ $payment->member_code ?? '' }}
                            </td>
                            <td>
                                @php
                                    $amountFormatMap = [
                                        'status'            => number_format($payment->response('amount'), 2, '.', ''),
                                        'axisstatus'        => number_format($payment->response('amount') / 100, 2, '.', ''),
                                        'razorpaycallback'  => number_format($payment->response('amount') / 100, 2, '.', ''),
                                        'hdfcsmartpaycallback' => number_format($payment->response('amount'), 2, '.', ''),
                                    ];
                                @endphp

                                {{ $amountFormatMap[$gateway] ?? number_format($payment->response('txn_amount'), 2, '.', '') }}
                            </td>
                            <td>
                                {{ 
                                    $gateway == 'status' ? 'PayUMoney' : 
                                    ($gateway == 'axisstatus' ? 'AXIS' : 
                                    ($gateway == 'razorpaycallback' ? 'Razorpay' : 
                                    ($gateway == 'hdfcstatus' ? 'HDFC VAS' :
                                    ($gateway == 'hdfcsmartpaycallback' ? 'HDFC SMART Pay' : '')))) 
                                }}
                                
                            </td>
                            <td>
                                {{ $payment->response('comment') ?? '' }}
                            </td>
                            <td>
                                {{ ( $gateway == 'status') ? $payment->response('status') : $payment->status }}
                            </td>
                            <td data-order="{{ $payment->updated_at ?? '' }}">{{ $payment->updated_at ? $payment->updated_at->format('Y-m-d h:i:s A') : '' }}</td>
                            <td>{{ \App\Services\PaymentSource::label($payment->payment_source) }}</td>
                            <td>
                                {{ ['success' => 'Success', 'failure' => 'Failure'][$payment->clubman_status] ?? '-' }}
                                @can('payment_edit')
                                    @if($payment->clubman_status === 'failure')
                                        <form method="POST" action="{{ route('admin.payments.retryClubman', $payment->id) }}" class="clubman-retry-form" onsubmit="event.stopPropagation(); this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Retrying...';">
                                            @csrf
                                            <button type="submit" class="clubman-retry" aria-label="Retry Clubman posting for payment {{ $payment->id }}" onclick="event.stopPropagation()"><i class="fas fa-redo-alt" aria-hidden="true"></i> Retry</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                            <td>
                                <span style="display:none">{{ $payment->response('status') ?? '' }}</span>
                                <input type="checkbox" disabled="disabled" {{ $payment->status ? 'checked' : '' }}>
                            </td>
                            <td></td>

                        </tr>
                    @endforeach
                    
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
            <span>Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} payments</span>
            {{ $payments->onEachSide(1)->links() }}
        </div>
        <small class="text-muted">Search applies to this page only.</small>
    </div>
</div>



@endsection
@section('scripts')
@parent
<script>
    $(function () {
        $('.datatable-Payment:not(.ajaxTable)').DataTable({
            orderCellsTop: true,
            order: [],
            paging: false,
            info: false,
            select: false,
            columnDefs: [
                { orderable: false, targets: 0 },
                { orderable: false, searchable: false, targets: -1 }
            ],
            dom: 'frt',
            buttons: []
        });
        $('a[data-toggle="tab"]').on('shown.bs.tab click', function () {
            $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        });
    });
</script>
@endsection
