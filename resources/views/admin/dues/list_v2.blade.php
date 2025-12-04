@extends('layouts.admin')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Member Dues Management</h5>
    </div>

    <!-- Filter Section -->
    <div class="card-body border-bottom bg-light">
        <form action="{{ route('admin.dues.list') }}" method="GET" id="filterForm">
            <div class="row align-items-end">
                <!-- Month Filter -->
                <div class="col-md-3">
                    <label for="month" class="form-label font-weight-bold">Month</label>
                    <select name="month" id="month" class="form-control">
                        <option value="">All Months</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Year Filter -->
                <div class="col-md-3">
                    <label for="year" class="form-label font-weight-bold">Year</label>
                    <select name="year" id="year" class="form-control">
                        <option value="">All Years</option>
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Member Search -->
                <div class="col-md-4">
                    <label for="member_code" class="form-label font-weight-bold">Member Code</label>
                    <input 
                        type="text" 
                        name="member_code" 
                        id="member_code" 
                        class="form-control" 
                        placeholder="Search by member code..."
                        value="{{ request('member_code') }}"
                    >
                </div>

                <!-- Action Buttons -->
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block d-block w-100">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                </div>
            </div>

            <!-- Advanced Filters (Collapsible) -->
            <div class="mt-3">
                <a class="text-decoration-none" data-toggle="collapse" href="#advancedFilters" role="button" aria-expanded="false" aria-controls="advancedFilters">
                    <i class="fas fa-chevron-down" id="advancedFilterIcon"></i> Advanced Filters
                </a>
                <div class="collapse mt-3" id="advancedFilters">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="min_balance" class="form-label">Min Balance</label>
                            <input type="number" name="min_balance" id="min_balance" class="form-control" value="{{ request('min_balance') }}" step="0.01" placeholder="e.g. 1000">
                        </div>
                        <div class="col-md-3">
                            <label for="max_balance" class="form-label">Max Balance</label>
                            <input type="number" name="max_balance" id="max_balance" class="form-control" value="{{ request('max_balance') }}" step="0.01" placeholder="e.g. 10000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <a href="{{ route('admin.dues.list') }}" class="btn btn-secondary btn-block d-block">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filters Display -->
    @if(request('month') || request('year') || request('member_code') || request('status'))
    <div class="card-body border-bottom py-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="text-muted small">Active Filters:</span>
            @if(request('month'))
                <span class="badge bg-primary">
                    Month: {{ date('F', mktime(0, 0, 0, request('month'), 1)) }}
                    <a href="{{ route('admin.dues.list', array_merge(request()->except('month'))) }}" class="text-white ms-1">&times;</a>
                </span>
            @endif
            @if(request('year'))
                <span class="badge bg-primary">
                    Year: {{ request('year') }}
                    <a href="{{ route('admin.dues.list', array_merge(request()->except('year'))) }}" class="text-white ms-1">&times;</a>
                </span>
            @endif
            @if(request('member_code'))
                <span class="badge bg-primary">
                    Member: {{ request('member_code') }}
                    <a href="{{ route('admin.dues.list', array_merge(request()->except('member_code'))) }}" class="text-white ms-1">&times;</a>
                </span>
            @endif
            @if(request('status'))
                <span class="badge bg-primary">
                    Status: {{ ucfirst(request('status')) }}
                    <a href="{{ route('admin.dues.list', array_merge(request()->except('status'))) }}" class="text-white ms-1">&times;</a>
                </span>
            @endif
        </div>
    </div>
    @endif

    <!-- Bulk Actions -->
    <div class="card-body border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted">
                    Showing <strong>{{ $dues->count() }}</strong> entries
                </span>
            </div>
            <div class="d-flex gap-2">
                <form action="{{ route('admin.dues.send.sms.all') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="month" value="{{ request('month') }}">
                    <input type="hidden" name="year" value="{{ request('year') }}">
                    <input type="hidden" name="member_code" value="{{ request('member_code') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Send SMS to all filtered members?')">
                        <i class="fas fa-sms"></i> Schedule SMS for All
                    </button>
                </form>
                <form action="{{ route('admin.dues.send.email.all') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="month" value="{{ request('month') }}">
                    <input type="hidden" name="year" value="{{ request('year') }}">
                    <input type="hidden" name="member_code" value="{{ request('member_code') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <button type="submit" class="btn btn-outline-info" onclick="return confirm('Send Email to all filtered members?')">
                        <i class="fas fa-envelope"></i> Schedule Email for All
                    </button>
                </form>
            </div>
            <div class="mb-2">
                <button type="button" id="resetSelection" class="btn btn-warning btn-sm">Reset Selection</button>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover datatable datatable-MemberDuesList">
                <thead class="table-light">
                    <tr>
                        <th width="10"></th>
                        <th>#</th>
                        <th>
                            Selected
                        </th>
                        <th>Member Code</th>
                        <th>Outstanding Balance</th>
                        <th>Paid Amount</th>
                        <th>Status</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dues as $key => $due)
                        <tr data-entry-id="{{ $due->id }}">
                            <td></td>
                            <td>{{ $due->id ?? '' }}</td>
                            <td>
                            <input type="checkbox" class="rowCheck" name="selected_ids[]" value="{{ $due->id }}" data-id="{{ $due->id }}">
                            </td>
                            <td><strong>{{ $due->member_code ?? '' }}</strong></td>
                            <td>₹{{ number_format($due->outstanding_balance, 2) }}</td>
                            <td>₹{{ number_format($due->paid_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $due->status == 'pending' ? 'warning' : ($due->status == 'paid' ? 'success' : 'danger') }}">
                                    {{ ucfirst($due->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <form action="{{ route('admin.dues.send.sms', $due) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary" title="Send SMS">
                                            <i class="fas fa-sms"></i> SMS
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.dues.send.email', $due) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-info" title="Send Email">
                                            <i class="fas fa-envelope"></i> Email
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No dues found matching your criteria</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add custom CSS -->
<style>
    .font-weight-bold {
        font-weight: 600;
    }
    .badge a {
        text-decoration: none;
    }
    .badge a:hover {
        opacity: 0.8;
    }
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
    }
    #advancedFilters {
        padding-top: 1rem;
    }
    #advancedFilterIcon {
        transition: transform 0.3s ease;
    }
    .collapsed #advancedFilterIcon {
        transform: rotate(-90deg);
    }
</style>
@endsection

@section('scripts')
@parent
<script>
$(function () {
    let selectedIds = [];

    let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons);

    $.extend(true, $.fn.dataTable.defaults, {
        orderCellsTop: true,
        order: [[ 1, 'desc' ]],
        pageLength: 100,
    });
    
    let table = $('.datatable-MemberDuesList:not(.ajaxTable)').DataTable({ 
        buttons: dtButtons,
        language: {
            emptyTable: "No dues found"
        }
    });
    
    $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
    });

    // Toggle icon rotation for advanced filters
    $('#advancedFilters').on('show.bs.collapse', function () {
        $('#advancedFilterIcon').css('transform', 'rotate(0deg)');
    });
    
    $('#advancedFilters').on('hide.bs.collapse', function () {
        $('#advancedFilterIcon').css('transform', 'rotate(-90deg)');
    });

    // Auto-submit on filter change (optional)
    $('#month, #year, #status').on('change', function() {
        // Uncomment to enable auto-submit
        // $('#filterForm').submit();
    });

    

    $('#MemberDuesList').on('draw.dt', function () {
        reCheckBoxes();
    });

        // When row checkbox clicked
    $(document).on('change', '.rowCheck', function () {
        let id = $(this).data('id');

        if (this.checked) {
            if (!selectedIds.includes(id)) {
                selectedIds.push(id);
            }
        } else {
            selectedIds = selectedIds.filter(x => x !== id);
        }
    });

    $('#resetSelection').on('click', function () {
    // Clear global selection
    selectedIds = [];

    // Uncheck all currently displayed rows
    $('.rowCheck').prop('checked', false);

    alert("All selections have been reset.");
    });
});

function reCheckBoxes() {
    debugger;
    $('.rowCheck').each(function () {
        let id = $(this).data('id');
        $(this).prop('checked', selectedIds.includes(id));
    });
}
</script>
@endsection