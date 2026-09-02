<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestmentReturnReviewRequest;
use App\InvestmentStatus;
use App\Models\Investment;
use App\Models\InvestmentReturn;
use App\Services\InvestmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

class InvestmentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $query = Investment::query()->with(['user', 'investmentPlan'])->latest();
        $validStatuses = array_map(fn (InvestmentStatus $investmentStatus): string => $investmentStatus->value, InvestmentStatus::cases());

        if (in_array($status, $validStatuses, true)) {
            $query->where('status', $status);
        }

        $investments = $query->paginate(20)->withQueryString();

        return view('admin.investments.index', compact('investments', 'status'));
    }

    public function show(Investment $investment): View
    {
        $investment->load(['user', 'investmentPlan', 'returns.approver']);

        return view('admin.investments.show', compact('investment'));
    }

    public function calculate(Investment $investment, InvestmentService $investmentService): RedirectResponse
    {
        try {
            $return = $investmentService->calculateDueReturn($investment);
        } catch (LogicException $exception) {
            return back()->withErrors(['investment' => $exception->getMessage()]);
        }

        return back()->with('status', $return === null ? 'No ROI period is due yet.' : 'ROI snapshot created and awaiting approval.');
    }

    public function reviewReturn(InvestmentReturnReviewRequest $request, InvestmentReturn $investmentReturn, InvestmentService $investmentService): RedirectResponse
    {
        try {
            if ($request->validated('decision') === 'approved') {
                $investmentService->approveReturn($investmentReturn, $request->user()->id);
            } else {
                $investmentService->releaseReturn($investmentReturn, $request->user()->id);
            }
        } catch (LogicException $exception) {
            return back()->withErrors(['investment' => $exception->getMessage()]);
        }

        return back()->with('status', 'ROI review saved.');
    }

    public function mature(Request $request, Investment $investment, InvestmentService $investmentService): RedirectResponse
    {
        try {
            $investmentService->mature($investment, $request->user()->id);
        } catch (LogicException $exception) {
            return back()->withErrors(['investment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Investment marked as matured. Release all approved returns before completing it.');
    }

    public function complete(Request $request, Investment $investment, InvestmentService $investmentService): RedirectResponse
    {
        try {
            $investmentService->complete($investment, $request->user()->id);
        } catch (LogicException $exception) {
            return back()->withErrors(['investment' => $exception->getMessage()]);
        }

        return back()->with('status', 'Investment completed and principal released.');
    }
}
