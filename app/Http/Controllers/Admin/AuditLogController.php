<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category')->toString();
        $search = $request->string('search')->trim()->toString();
        $categories = ['compliance', 'financial', 'security', 'operations', 'system'];

        $query = AuditLog::query()->with('actor')->latest('created_at');

        if (in_array($category, $categories, true)) {
            $query->where('category', $category);
        } else {
            $category = '';
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $like = "%{$search}%";

                $searchQuery
                    ->where('action', 'like', $like)
                    ->orWhere('route', 'like', $like)
                    ->orWhere('auditable_type', 'like', $like)
                    ->orWhere('auditable_id', 'like', $like)
                    ->orWhereHas('actor', function ($actorQuery) use ($like): void {
                        $actorQuery->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    });
            });
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('admin.audit.index', compact('logs', 'category', 'categories', 'search'));
    }
}
