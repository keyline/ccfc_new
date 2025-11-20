<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DuesUploadBatch;
use App\Models\MemberDue;
use App\Models\PaymentToken;
use App\Jobs\ProcessDuesFile;
use App\Jobs\SendSms;
use App\Jobs\SendEmail;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DuesController extends Controller
{
    public function showUploadForm()
    {
        return view('admin.dues.upload');
    }

    public function handleUpload(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'dues_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $month = $request->input('month');
        $year = $request->input('year');
        $file = $request->file('dues_file');

        $batchId = 'DUE_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '_' . uniqid();
        $fileName = $batchId . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('dues_files', $fileName);

        $batch = DuesUploadBatch::create([
            'batch_id' => $batchId,
            'month_no' => $month,
            'month_name' => Carbon::create()->month($month)->format('F'),
            'year' => $year,
            'upload_date' => now(),
            'uploaded_by' => auth()->id(),
            'file_name' => $fileName,
            'status' => 'processing',
        ]);

        ProcessDuesFile::dispatch($batch, $filePath);

        return redirect()->route('admin.dues.list')->with('success', 'File uploaded successfully. Processing will start shortly.');
    }

    public function listDues(Request $request)
    {
        $query = MemberDue::query();

        if ($request->has('month') && $request->month != '') {
            $query->where('month_no', $request->month);
        }

        if ($request->has('year') && $request->year != '') {
            $query->where('year', $request->year);
        }

        $dues = $query->paginate(20);

        return view('admin.dues.list', compact('dues'));
    }

    public function sendSmsWithToken(Request $request, MemberDue $due)
    {
        $tokenData = $this->createPaymentToken($due);
        SendSms::dispatch($tokenData['model'], $tokenData['plainTextToken']);
        return back()->with('success', 'SMS scheduled for member ' . $due->member_code);
    }

    public function sendEmail(Request $request, MemberDue $due)
    {
        $tokenData = $this->createPaymentToken($due);
        SendEmail::dispatch($tokenData['model'], $tokenData['plainTextToken']);
        return back()->with('success', 'Email scheduled for member ' . $due->member_code);
    }

    private function createPaymentToken(MemberDue $due)
    {
        // First, check if an active, unexpired token already exists.
        $existingToken = PaymentToken::where('member_due_id', $due->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        // If a valid token exists, we can't resend it because we don't have the plaintext.
        // The best practice is to invalidate it and create a new one.
        if ($existingToken) {
            $existingToken->update(['status' => 'revoked']);
        }

        // Generate a new URL-safe plaintext token and its hash.
        $plainTextToken = Str::random(60);
        $hashedToken = hash('sha256', $plainTextToken);

        $token = PaymentToken::create([
            'token' => $hashedToken,
            'member_code' => $due->member_code,
            'member_due_id' => $due->id,
            'generated_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        return ['model' => $token, 'plainTextToken' => $plainTextToken];
    }

    public function sendSmsToAll(Request $request)
    {
        $dues = MemberDue::where('month_no', $request->month)
            ->where('year', $request->year)
            ->get();

        foreach ($dues as $due) {
            $tokenData = $this->createPaymentToken($due);
            SendSms::dispatch($tokenData['model'], $tokenData['plainTextToken']);
        }

        return back()->with('success', 'SMS scheduled for all members.');
    }

    public function sendEmailToAll(Request $request)
    {
        $dues = MemberDue::where('month_no', $request->month)
            ->where('year', $request->year)
            ->get();

        foreach ($dues as $due) {
            $tokenData = $this->createPaymentToken($due);
            SendEmail::dispatch($tokenData['model'], $tokenData['plainTextToken']);
        }

        return back()->with('success', 'Email scheduled for all members.');
    }
}
