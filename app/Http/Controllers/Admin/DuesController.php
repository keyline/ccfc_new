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
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DuesPaymentMail;
use App\Models\NotificationLog;

class DuesController extends Controller
{
    public function showUploadForm()
    {
        return view('admin.dues.upload');
    }

    public function index(Request $request)
    {
        $query = \App\Models\MemberDue::query();

        // Apply search filter
        if ($request->filled('member_code')) {
            $query->where('member_code', 'LIKE', '%' . $request->member_code . '%');
        }

        // Paginate with query string
        $dues = $query->paginate(20)->withQueryString();

        return view('admin.dues.list', compact('dues'));
    }

    public function handleUpload(Request $request)
    {
        try {

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

            //start transaction
            DB::beginTransaction();

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

            //ProcessDuesFile::dispatch($batch, $filePath);
            //if you want to use non facade version
            //then app(\Maatwebsite\Excel\Excel::class)->import(new UsersImport(), $file);
            //resolve it via the service container

            Excel::import(new \App\Imports\DuesImport($batch), $filePath);

            DuesUploadBatch::where('id', $batch->id)->update(['status' => 'completed']);

            DB::commit();

            return redirect()->route('admin.dues.list')->with('success', 'File uploaded successfully. Processing will start shortly.');

        } catch (\Exception $e) {
            //throw $th;
            DB::rollBack();
            return back()->with('error', 'There was an error uploading the file: ' . $e->getMessage());
        }

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

        //$dues = $query->paginate(20);
        $dues = $query->get();


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
        //SendEmail::dispatch($tokenData['model'], $tokenData['plainTextToken']);

        $member = \App\Models\User::where('user_code', $tokenData['model']->member_code)->first();


        if (!$member) {
            Log::error("Member not found for member code: {$tokenData['model']->member_code}");
            return;
        }


        Mail::to($member->email)->send(new DuesPaymentMail($tokenData['model'], $tokenData['plainTextToken']));

        // Log the notification
        NotificationLog::create([
            'token_id' => $tokenData['model']->id,
            'member_code' => $tokenData['model']->member_code,
            'notification_type' => 'email',
            'recipient' => $member->email,
            'subject' => 'Your monthly due payment link',
            'message_body' => 'Dear Member, your due payment link is: ' . url('/payment/' . $tokenData['plainTextToken']),
            'status' => 'sent',
        ]);



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
