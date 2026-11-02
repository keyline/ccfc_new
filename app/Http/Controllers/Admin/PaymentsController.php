<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\ClubmanPaymentPosting;
use Tzsk\Payu\Models\PayuTransaction;

class PaymentsController extends Controller
{
    public function retryClubman($transaction, ClubmanPaymentPosting $posting)
    {
        abort_if(Gate::denies('payment_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            // Serialize retries for this row and recheck its latest status.
            DB::transaction(function () use ($transaction, $posting) {
                $payment = PayuTransaction::whereKey($transaction)->lockForUpdate()->firstOrFail();
                if ($payment->clubman_status !== 'failure') {
                    throw new \RuntimeException('Only failed Clubman postings can be retried.');
                }

                $user = User::findOrFail($payment->paid_for_id);
                $posting->retry($payment, (string) $user->user_code);
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('admin.payments.index')->with('clubman_retry_error', $exception->getMessage());
        }

        return redirect()->route('admin.payments.index')->with('message', 'Payment posted to Clubman successfully.');
    }

    public function index()
    {
        abort_if(Gate::denies('payment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        //$payments = User::with(['member'])->transactions()->get();
        //$payments= USER::find(1952)->transactions()->get()->first();
        $users = DB::table('payu_transactions')
                        ->join('users', 'users.id', '=', 'payu_transactions.paid_for_id')
                        ->distinct()
                        ->get(['users.id']);
        
        $payments=  $users->map(function ($item) {
            return USER::find($item->id)->transactions()->get();
        });
        // dd($payments);

        
        return view('admin.payments.index', compact('payments', 'users'));
    }

    public function create()
    {
        abort_if(Gate::denies('payment_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.payments.create');
    }

    public function store(StorePaymentRequest $request)
    {
        $payment = Payment::create($request->all());

        return redirect()->route('admin.payments.index');
    }

    public function edit(Payment $payment)
    {
        abort_if(Gate::denies('payment_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $payment->load('member');

        return view('admin.payments.edit', compact('payment'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $payment->update($request->all());

        return redirect()->route('admin.payments.index');
    }

    public function show(Payment $payment)
    {
        abort_if(Gate::denies('payment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $payment->load('member');

        return view('admin.payments.show', compact('payment'));
    }

    public function destroy(Payment $payment)
    {
        abort_if(Gate::denies('payment_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $payment->delete();

        return back();
    }

    public function massDestroy(MassDestroyPaymentRequest $request)
    {
        Payment::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
