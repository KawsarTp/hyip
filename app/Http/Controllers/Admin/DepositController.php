<?php

namespace App\Http\Controllers\Admin;

use App\DepositStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DepositReviewRequest;
use App\Models\Deposit;
use App\Services\DepositService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $query = Deposit::query()->with(['user', 'paymentIntent'])->latest();

        $validStatuses = array_map(fn (DepositStatus $depositStatus): string => $depositStatus->value, DepositStatus::cases());

        if (in_array($status, $validStatuses, true)) {
            $query->where('status', $status);
        }

        $deposits = $query->paginate(20)->withQueryString();

        return view('admin.deposits.index', compact('deposits', 'status'));
    }

    public function show(Deposit $deposit): View
    {
        $deposit->load(['user', 'paymentIntent', 'events']);

        return view('admin.deposits.show', compact('deposit'));
    }

    public function review(DepositReviewRequest $request, Deposit $deposit, DepositService $depositService): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['decision'] === 'completed') {
            $depositService->settle($deposit, $validated['external_reference'], $request->user()->id);
        } else {
            $depositService->reject($deposit, $validated['review_notes'], $request->user()->id);
        }

        return redirect()->route('admin.deposits.show', $deposit)->with('status', 'Deposit review saved.');
    }
}
