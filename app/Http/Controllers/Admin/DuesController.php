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


        // Get the most recent upload batch for smart defaults
        $latestBatch = DuesUploadBatch::where('status', 'completed')
            ->orderBy('year', 'desc')
            ->orderBy('month_no', 'desc')
            ->first();


        $defaultMonth = null;
        $defaultYear = null;
        $isDefaultView = false;


        if (!$request->hasAny(['month', 'year', 'member_code', 'status', 'min_balance', 'max_balance'])) {
            if ($latestBatch) {
                $defaultMonth = $latestBatch->month_no;
                $defaultYear = $latestBatch->year;
                $isDefaultView = true;

                $query->where('month_no', $defaultMonth)
                      ->where('year', $defaultYear);
            }
            // else: no data uploaded yet, show empty list
        } else {

            // Month filter
            if ($request->filled('month')) {
                $query->where('month_no', $request->month);
            }

            // Year filter
            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            // Member code search filter
            if ($request->filled('member_code')) {
                $query->where('member_code', 'LIKE', '%' . $request->member_code . '%');
            }

            // Status filter (Advanced)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Min balance filter (Advanced)
            if ($request->filled('min_balance')) {
                $query->where('outstanding_balance', '>=', $request->min_balance);
            }

            // Max balance filter (Advanced)
            if ($request->filled('max_balance')) {
                $query->where('outstanding_balance', '<=', $request->max_balance);
            }


        }


        /**
         * stats improvements can be done here
         */

        // Get available months and years from actual uploads
        /*$availableMonthsYears = DuesUploadBatch::select('month_no', 'year', 'month_name')
            ->where('status', 'completed')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month_no', 'desc')
            ->get()
            ->groupBy('year');

        // Get statistics for the current view
        $stats = [
            'total_members' => $dues->count(),
            'total_outstanding' => $dues->sum('outstanding_balance'),
            'pending_count' => $dues->where('status', 'pending')->count(),
            'paid_count' => $dues->where('status', 'paid')->count(),
        ];
        */


        // Order by ID descending
        $query->orderBy('id', 'desc');


        //$dues = $query->paginate(20);
        $dues = $query->get();
        print_r($dues->toArray()); exit;


        return view('admin.dues.list_v2', compact('dues'));
    }

    public function sendSmsWithToken(Request $request, MemberDue $due)
    {
        try {
            $body = "";
            $tokenData = $this->createPaymentToken($due);


            $member = \App\Models\User::where('user_code', $tokenData['model']->member_code)->first();

            if (!$member) {
                Log::error("Member not found for member code: {$tokenData['model']->member_code}");
                return;
            }


            $body .= 'Dear Member, your due payment link is: ' . url('/payment/' . $tokenData['plainTextToken']);

            $this->sendSMS($member->phone_number_1, $body);

            // Log the notification
            NotificationLog::create([
                'token_id' => $tokenData['model']->id,
                'member_code' => $tokenData['model']->member_code,
                'notification_type' => 'sms',
                'recipient' => $member->phone_number_1,
                'message_body' => 'Dear Member, your due payment link is: ' . url('/payment/' . $tokenData['plainTextToken']),
                'status' => 'sent',
            ]);

            $tokenData['model']->update(['sms_sent' => true, 'sms_sent_at' => now()]);

            //SendSms::dispatch($tokenData['model'], $tokenData['plainTextToken']);
            return back()->with('success', 'SMS scheduled for member ' . $due->user_code);

        } catch (\Exception $ex) {

            // Plain text log entry
            Log::error('SMS send failed: ' . $ex->getMessage());

            // Optional: in dev, you might want to see it on screen too
            if (app()->environment('local')) {
                dd('SMS error: ' . $ex->getMessage());
            }

        }

    }

    public function sendEmail(Request $request, MemberDue $due)
    {
        try {

            //start transaction
            //DB::beginTransaction();

            $body = "";

            $tokenData = $this->createPaymentToken($due);
            //SendEmail::dispatch($tokenData['model'], $tokenData['plainTextToken']);

            $member = \App\Models\User::where('user_code', $tokenData['model']->member_code)->first();


            if (!$member) {
                Log::error("Member not found for member code: {$tokenData['model']->member_code}");
                throw new \Exception("Member not found for member code: {$tokenData['model']->member_code}");
            }


            $body .= 'Dear Member, your due payment link is: ' . url('/payment/' . $tokenData['plainTextToken']);

            Mail::to($member->email)->send(new DuesPaymentMail($tokenData['model'], $tokenData['plainTextToken']));

            // Log the notification
            NotificationLog::create([
                'token_id' => $tokenData['model']->id,
                'member_code' => $tokenData['model']->member_code,
                'notification_type' => 'email',
                'recipient' => $member->email,
                'subject' => 'Your monthly due payment link',
                'message_body' => $body,
                'status' => 'sent',
            ]);



            //DB::commit();

            return back()->with('success', 'Email scheduled for member ' . $due->member_code);

        } catch (\Exception $ex) {
            //DB::rollBack();
            // Plain text log entry
            Log::error('Mail send failed: ' . $ex->getMessage() . $body);

            // Optional: in dev, you might want to see it on screen too
            if (app()->environment('local')) {
                dd('Mail error: ' . $ex->getMessage());
            }

        }

        return back()->with('error', 'There was an error sending the email to member ' . $due->member_code);

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

        try {
            // Build the same query as listDues to get filtered results
            $query = MemberDue::query();

            // Apply all filters
            if ($request->filled('month')) {
                $query->where('month_no', $request->month);
            }

            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            if ($request->filled('member_code')) {
                $query->where('member_code', 'LIKE', '%' . $request->member_code . '%');
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('min_balance')) {
                $query->where('outstanding_balance', '>=', $request->min_balance);
            }

            if ($request->filled('max_balance')) {
                $query->where('outstanding_balance', '<=', $request->max_balance);
            }

            $dues = $query->get();

            if ($dues->isEmpty()) {
                return back()->with('warning', 'No members found matching the selected filters.');
            }

            $successCount = 0;
            $errorCount = 0;

            foreach ($dues as $due) {
                try {
                    $tokenData = $this->createPaymentToken($due);
                    SendSms::dispatch($tokenData['model'], $tokenData['plainTextToken']);
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to schedule SMS for member {$due->member_code}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            $message = "SMS scheduled for {$successCount} member(s).";
            if ($errorCount > 0) {
                $message .= " {$errorCount} failed.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Bulk SMS send failed: ' . $e->getMessage());
            return back()->with('error', 'There was an error scheduling SMS: ' . $e->getMessage());
        }

    }

    public function sendEmailToAll(Request $request)
    {

        try {
            // Build the same query as listDues to get filtered results
            $query = MemberDue::query();

            // Apply all filters
            if ($request->filled('month')) {
                $query->where('month_no', $request->month);
            }

            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            if ($request->filled('member_code')) {
                $query->where('member_code', 'LIKE', '%' . $request->member_code . '%');
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('min_balance')) {
                $query->where('outstanding_balance', '>=', $request->min_balance);
            }

            if ($request->filled('max_balance')) {
                $query->where('outstanding_balance', '<=', $request->max_balance);
            }

            $dues = $query->get();

            if ($dues->isEmpty()) {
                return back()->with('warning', 'No members found matching the selected filters.');
            }

            $successCount = 0;
            $errorCount = 0;

            foreach ($dues as $due) {
                try {
                    $tokenData = $this->createPaymentToken($due);
                    SendEmail::dispatch($tokenData['model'], $tokenData['plainTextToken']);
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to schedule email for member {$due->member_code}: " . $e->getMessage());
                    $errorCount++;
                }
            }

            $message = "Email scheduled for {$successCount} member(s).";
            if ($errorCount > 0) {
                $message .= " {$errorCount} failed.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Bulk email send failed: ' . $e->getMessage());
            return back()->with('error', 'There was an error scheduling emails: ' . $e->getMessage());
        }

    }
}
