<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithdrawalReviewRequest;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use App\WithdrawalStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $query = Withdrawal::query()->with(['user', 'withdrawalMethod'])->latest();
        $validStatuses = array_map(fn (WithdrawalStatus $withdrawalStatus): string => $withdrawalStatus->value, WithdrawalStatus::cases());

        if (in_array($status, $validStatuses, true)) {
            $query->where('status', $status);
        }

        $withdrawals = $query->paginate(20)->withQueryString();

        return view('admin.withdrawals.index', compact('withdrawals', 'status'));
    }

    public function show(Withdrawal $withdrawal): View
    {
        $withdrawal->load(['user', 'withdrawalMethod', 'reviewer']);

        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    public function review(WithdrawalReviewRequest $request, Withdrawal $withdrawal, WithdrawalService $withdrawalService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            match ($validated['decision']) {
                'approved' => $withdrawalService->approve($withdrawal, $request->user()->id, $validated['review_notes']),
                'completed' => $withdrawalService->complete($withdrawal, $validated['external_reference'], $request->user()->id),
                'rejected' => $withdrawalService->reject($withdrawal, $validated['review_notes'], $request->user()->id),
            };
        } catch (LogicException $exception) {
            return back()->withInput()->withErrors(['withdrawal' => $exception->getMessage()]);
        }

        return redirect()->route('admin.withdrawals.show', $withdrawal)->with('status', 'Withdrawal review saved.');
    }
}
