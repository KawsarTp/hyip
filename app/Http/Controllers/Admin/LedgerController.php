<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Contracts\View\View;

class LedgerController extends Controller
{
    public function index(): View
    {
        $entries = LedgerEntry::query()
            ->with(['lines.account', 'actor'])
            ->latest('posted_at')
            ->latest('id')
            ->paginate(20);

        return view('admin.ledger.index', compact('entries'));
    }
}
