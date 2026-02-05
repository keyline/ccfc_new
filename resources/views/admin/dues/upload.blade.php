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
<style>
    .dues-wrapper {
        max-width: 900px;
        margin: auto;
    }
    .dues-card {
        border-radius: 16px;
        border: none;
        background: #ffffff;
        box-shadow: 0 20px 40px rgba(0,0,0,.08);
        overflow: hidden;
    }
    .dues-header {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        padding: 24px 32px;
    }
    .dues-section {
        padding: 28px 32px;
    }
    .template-box {
        background: #f8fafc;
        border: 1px dashed #c7d2fe;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .template-box i {
        font-size: 32px;
        color: #4f46e5;
    }
    .form-icon {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    .form-control-icon {
        padding-left: 38px;
    }
    .upload-btn {
        border-radius: 999px;
        padding: 10px 32px;
    }
</style>

<div class="dues-wrapper mt-4">
    <div class="dues-card">

        <!-- Header -->
        <div class="dues-header">
            <h4 class="mb-1">Monthly Dues Upload</h4>
            <small>Upload and manage monthly dues data</small>
        </div>

        <!-- Body -->
        <div class="dues-section">

            <!-- Template -->
            <div class="template-box mb-4">
                <div>
                    <strong>Excel Template</strong>
                    <div class="text-muted small">
                        Use this format to upload dues correctly
                    </div>
                </div>

                <a href="{{ asset('dues_files/Template_Due_List.xlsx') }}"
                   target="_blank"
                   class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-file-excel me-1"></i> Download
                </a>
            </div>

            <form action="{{ route('admin.dues.upload.handle') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                <div class="row g-4">

                    <!-- Month -->
                    <div class="col-md-6 position-relative">                        
                        <label class="form-label">Month</label>
                        <select name="month"
                                class="form-select form-control-icon"
                                required>
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}">
                                    {{ date('F', mktime(0, 0, 0, $month, 10)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Year -->
                    <div class="col-md-6 position-relative">                        
                        <label class="form-label">Year</label>
                        <input type="number"
                               name="year"
                               class="form-control form-control-icon"
                               value="{{ date('Y') }}"
                               required>
                    </div>

                    <!-- File -->
                    <div class="col-12">
                        <label class="form-label">Dues Excel File</label>
                        <input type="file"
                               name="dues_file"
                               class="form-control"
                               accept=".xls,.xlsx"
                               required>
                    </div>

                </div>

                <!-- Submit -->
                <div class="text-end mt-5">
                    <button type="submit"
                            class="btn btn-primary upload-btn">
                        <i class="fa fa-cloud-upload-alt me-1"></i>
                        Upload Dues File
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>
@endsection

