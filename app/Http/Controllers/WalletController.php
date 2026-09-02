<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request, WalletService $walletService): View
    {
        /** @var User $user */
        $user = $request->user();
        $wallets = $walletService->walletsFor($user);
        $entries = LedgerEntry::query()
            ->whereHas('lines.account', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey());
            })
            ->with('lines.account')
            ->latest('posted_at')
            ->limit(8)
            ->get();

        return view('wallet.index', compact('wallets', 'entries'));
    }
}
