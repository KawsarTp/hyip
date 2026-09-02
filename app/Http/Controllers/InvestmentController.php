<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientWalletBalance;
use App\Http\Requests\InvestmentRequest;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\User;
use App\Services\InvestmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use LogicException;

class InvestmentController extends Controller
{
    public function plans(): View
    {
        $plans = InvestmentPlan::query()->where('status', 'published')->where('currency', 'USD')->orderBy('sort_order')->get();

        return view('investments.plans', compact('plans'));
    }

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $investments = $user->investments()->with('investmentPlan')->latest()->paginate(12);

        return view('investments.index', compact('investments'));
    }

    public function create(InvestmentPlan $investmentPlan): View
    {
        abort_unless($investmentPlan->status->value === 'published', 404);

        return view('investments.create', ['plan' => $investmentPlan]);
    }

    public function store(InvestmentRequest $request, InvestmentService $investmentService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $plan = InvestmentPlan::query()->whereKey($validated['investment_plan_id'])->firstOrFail();

        try {
            $investment = $investmentService->create($user, $plan, $validated['amount']);
        } catch (InsufficientWalletBalance|InvalidArgumentException|LogicException $exception) {
            return back()->withInput()->withErrors(['investment' => $exception->getMessage()]);
        }

        return redirect()->route('investments.show', $investment)->with('status', 'Investment funded and moved to your locked wallet.');
    }

    public function show(Request $request, Investment $investment): View
    {
        abort_unless($investment->user_id === $request->user()->id, 403);
        $investment->load(['investmentPlan', 'returns']);

        return view('investments.show', compact('investment'));
    }
}
