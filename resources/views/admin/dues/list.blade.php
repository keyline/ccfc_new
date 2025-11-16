@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Monthly Dues List</h2>
    <div class="mb-3">
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
    <table class="table">
        <thead>
            <tr>
                <th>Member Code</th>
                <th>Outstanding Balance</th>
                <th>Paid Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dues as $due)
                <tr>
                    <td>{{ $due->member_code }}</td>
                    <td>{{ $due->outstanding_balance }}</td>
                    <td>{{ $due->paid_amount }}</td>
                    <td>{{ $due->status }}</td>
                    <td>
                        <form action="{{ route('admin.dues.send.sms', $due) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info">Send SMS</button>
                        </form>
                        <form action="{{ route('admin.dues.send.email', $due) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info">Send Email</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No dues found for the selected month and year.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
