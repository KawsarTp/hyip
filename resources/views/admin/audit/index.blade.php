@component('layouts.shell', ['admin' => true, 'active' => 'audit', 'title' => 'Audit logs'])
    <div class="mx-auto max-w-[1440px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-[#7b879a]">A traceable record of operational and compliance activity.</p>
                <h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Every action accounted for.</h2>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-[#cdebd8] bg-[#eaf9f0] px-3.5 py-3 text-[11px] font-semibold text-[#2f8d63]"><span class="h-2 w-2 rounded-full bg-[#48c58b]"></span> Immutable activity trail</span>
        </div>

        <section class="mt-8 rounded-[24px] border border-[#e2e7ef] bg-white p-5 sm:p-6">
            <form action="{{ route('admin.audit.index') }}" method="get" class="grid gap-3 sm:grid-cols-[1fr_190px_auto]">
                <label class="flex items-center gap-2 rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 focus-within:border-[#7668f6]">
                    <x-icon name="search" size="15" class="text-[#9aa5b7]" />
                    <input name="search" value="{{ $search }}" class="min-w-0 flex-1 border-0 bg-transparent py-3 text-xs outline-none ring-0" placeholder="Search actor, route, action, or record">
                </label>
                <select name="category" class="rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-xs font-medium text-[#536078] outline-none focus:border-[#7668f6]">
                    <option value="">All categories</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item }}" @selected($category === $item)>{{ str($item)->title() }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-[#0a1324] px-5 py-3 text-xs font-semibold text-white">Filter activity</button>
            </form>
        </section>

        <section class="app-surface mt-6 overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
            <div class="flex flex-col justify-between gap-2 border-b border-[#edf0f4] px-6 py-5 sm:flex-row sm:items-center sm:px-7">
                <div>
                    <h3 class="text-sm font-semibold">Activity history</h3>
                    <p class="mt-1 text-[11px] text-[#9aa5b7]">Sensitive values and private document paths are never shown here.</p>
                </div>
                <span class="rounded-lg bg-[#f0efff] px-2.5 py-2 text-[10px] font-semibold text-[#7668f6]">{{ number_format($logs->total()) }} records</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-[.14em] text-[#a3adbd]">
                            <th class="px-6 py-4 font-semibold sm:px-7">Time</th>
                            <th class="px-4 py-4 font-semibold">Actor</th>
                            <th class="px-4 py-4 font-semibold">Action</th>
                            <th class="px-4 py-4 font-semibold">Record</th>
                            <th class="px-4 py-4 font-semibold">Request</th>
                            <th class="px-6 py-4 text-right font-semibold sm:px-7">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f2f6]">
                        @forelse ($logs as $log)
                            <tr class="transition hover:bg-[#fbfcfe]">
                                <td class="px-6 py-4 sm:px-7">
                                    <p class="text-xs font-semibold">{{ $log->created_at?->format('M d, Y') }}</p>
                                    <p class="mt-1 font-mono text-[10px] text-[#a0aabb]">{{ $log->created_at?->format('H:i:s') }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-xs font-semibold">{{ $log->actor?->name ?? 'System' }}</p>
                                    <p class="mt-1 text-[10px] text-[#a0aabb]">{{ $log->actor?->email ?? 'Automated process' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full {{ $log->action === 'viewed' ? 'bg-[#f0efff] text-[#7668f6]' : ($log->action === 'deleted' ? 'bg-[#fff0ec] text-[#ef735c]' : 'bg-[#eaf9f0] text-[#36a675]') }} px-2.5 py-1 text-[10px] font-semibold">{{ str($log->action)->title() }}</span>
                                    <p class="mt-2 text-[10px] capitalize text-[#a0aabb]">{{ $log->category }}</p>
                                </td>
                                <td class="px-4 py-4 text-[11px] text-[#536078]">
                                    {{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : 'Route request' }}
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-mono text-[10px] text-[#7668f6]">{{ $log->route ?? '—' }}</p>
                                    <p class="mt-1 text-[10px] text-[#a0aabb]">{{ $log->method }} · {{ $log->ip_address ?? '—' }}</p>
                                </td>
                                <td class="px-6 py-4 text-right sm:px-7">
                                    <span class="rounded-full {{ ($log->metadata['response_status'] ?? 500) < 400 ? 'bg-[#eaf9f0] text-[#36a675]' : 'bg-[#fff0ec] text-[#ef735c]' }} px-2.5 py-1 text-[10px] font-semibold">{{ $log->metadata['response_status'] ?? '—' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-[#f0efff] text-[#7668f6]"><x-icon name="shield" size="18" /></span>
                                    <p class="mt-4 text-sm font-semibold">No audit activity found</p>
                                    <p class="mt-1 text-xs text-[#9aa5b7]">Admin actions will appear here after the audit migration is applied.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="border-t border-[#edf0f4] px-6 py-4">{{ $logs->links() }}</div>
            @endif
        </section>
    </div>
@endcomponent
