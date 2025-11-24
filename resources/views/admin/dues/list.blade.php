@extends('layouts.admin')

@section('content')
<div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <form action="{{ route('admin.dues.send.sms.all') }}" method="POST" style="display: inline-block;">
            @csrf
            <input type="hidden" name="month" value="{{ request('month') }}">
            <input type="hidden" name="year" value="{{ request('year') }}">
            <button type="submit" class="btn btn-primary">Schedule SMS for All</button>
            </form>
            <form action="{{ route('admin.dues.send.email.all') }}" method="POST" style="display: inline-block;">
                @csrf
                <input type="hidden" name="month" value="{{ request('month') }}">
                <input type="hidden" name="year" value="{{ request('year') }}">
                <button type="submit" class="btn btn-primary">Schedule Email for All</button>
            </form>
        </div>
    </div>
<div class="card">
    <div class="card-header">
        Member Dues List
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-MemberDuesList">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            #
                        </th>
                        <th>Member Code</th>
                        <th>Outstanding Balance</th>
                        <th>Paid Amount</th>
                        <th>Status</th>
                        
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dues as $key => $due)
                        <tr data-entry-id="{{ $due->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $due->id ?? '' }}
                            </td>
                            <td>
                                {{ $due->member_code ?? '' }}
                            </td>
                            <td>{{ $due->outstanding_balance }}</td>
                            <td>{{ $due->paid_amount }}</td>
                            <td>{{ $due->status }}</td>
                            <td>
                                <form action="{{ route('admin.dues.send.sms', $due) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-primary">Send SMS</button>
                                </form>
                                <form action="{{ route('admin.dues.send.email', $due) }}" method="POST" style="display: inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-info">Send Email</button>
                                </form>

                                
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)


  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-MemberDuesList:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection
