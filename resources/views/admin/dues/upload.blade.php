{{-- @extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Upload Dues File</h2>
    <h3>Template file link: </h3> <span><a href="{{ asset('dues_files/Template_Due_List.xlsx') }}" target="_blank">Template File</a></span>
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
@endsection --}}

@extends('layouts.admin')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Upload Dues File</h5>
                </div>

                <div class="card-body">

                    <!-- Template Link -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Template File</label><br>
                        <a href="{{ asset('dues_files/Template_Due_List.xlsx') }}"
                           target="_blank"
                           class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-download me-1"></i> Download Template
                        </a>
                    </div>

                    <form action="{{ route('admin.dues.upload.handle') }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf

                        <!-- Month -->
                        <div class="mb-3">
                            <label for="month" class="form-label">Month</label>
                            <select name="month" id="month" class="form-select" required>
                                @foreach (range(1, 12) as $month)
                                    <option value="{{ $month }}">
                                        {{ date('F', mktime(0, 0, 0, $month, 10)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year -->
                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number"
                                   name="year"
                                   id="year"
                                   class="form-control"
                                   value="{{ date('Y') }}"
                                   required>
                        </div>

                        <!-- File -->
                        <div class="mb-4">
                            <label for="dues_file" class="form-label">
                                Dues File <small class="text-muted">(Excel only)</small>
                            </label>
                            <input type="file"
                                   name="dues_file"
                                   id="dues_file"
                                   class="form-control"
                                   accept=".xls,.xlsx"
                                   required>
                        </div>

                        <!-- Submit -->
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa fa-upload me-1"></i> Upload
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
