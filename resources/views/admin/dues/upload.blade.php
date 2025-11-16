@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Upload Dues File</h2>
    <form action="{{ route('admin.dues.upload.handle') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="month">Month</label>
            <select name="month" id="month" class="form-control">
                @foreach (range(1, 12) as $month)
                    <option value="{{ $month }}">{{ date('F', mktime(0, 0, 0, $month, 10)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="year">Year</label>
            <input type="number" name="year" id="year" class="form-control" value="{{ date('Y') }}">
        </div>
        <div class="form-group">
            <label for="dues_file">Dues File (Excel)</label>
            <input type="file" name="dues_file" id="dues_file" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</div>
@endsection
