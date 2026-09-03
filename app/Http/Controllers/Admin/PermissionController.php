<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RolePermission;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()->orderBy('group_name')->orderBy('name')->get()->groupBy('group_name');
        $roles = UserRole::cases();
        $assigned = RolePermission::query()->get()->groupBy('role')->map(fn ($items) => $items->pluck('permission_id')->all());

        return view('admin.roles.index', compact('permissions', 'roles', 'assigned'));
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, array_map(fn (UserRole $item): string => $item->value, UserRole::cases()), true), 404);
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        DB::transaction(function () use ($role, $validated): void {
            RolePermission::query()->where('role', $role)->delete();
            foreach (array_unique($validated['permissions'] ?? []) as $permissionId) {
                RolePermission::query()->create(['role' => $role, 'permission_id' => $permissionId]);
            }
        });

        return back()->with('status', ucfirst($role).' permissions updated.');
    }
}
