@component('layouts.shell', ['admin' => true, 'active' => 'email-templates', 'title' => 'Email templates'])
    <div class="mx-auto max-w-[1250px]">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-[#7b879a]">Predefined, versioned messages for customer communications.</p>
                <h2 class="display-copy mt-2 text-3xl font-semibold sm:text-4xl">Message library.</h2>
            </div>

            <span class="inline-flex w-fit items-center gap-2 rounded-xl border border-[#dfe4ec] bg-white px-4 py-3 text-xs font-semibold text-[#536078]">
                <span class="h-2 w-2 rounded-full bg-[#36a675]"></span>
                Edit only
            </span>
        </div>

        @if (session('status'))
            <div class="mt-7 rounded-2xl border border-[#cdebd8] bg-[#eaf9f0] px-4 py-3 text-xs font-medium text-[#2f8d63]">
                {{ session('status') }}
            </div>
        @endif

        <section class="app-surface mt-8 overflow-hidden rounded-[24px] border border-[#e2e7ef] bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-[.14em] text-[#a3adbd]">
                            <th class="px-6 py-4 font-semibold sm:px-7">Template</th>
                            <th class="px-4 py-4 font-semibold">Subject</th>
                            <th class="px-4 py-4 font-semibold">Locale</th>
                            <th class="px-4 py-4 font-semibold">Version</th>
                            <th class="px-4 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 text-right font-semibold sm:px-7">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f2f6]">
                        @forelse ($templates as $template)
                            <tr class="transition hover:bg-[#fbfcfe]">
                                <td class="px-6 py-4 sm:px-7">
                                    <p class="text-xs font-semibold">{{ $template->name }}</p>
                                    <p class="mt-1 font-mono text-[10px] text-[#a0aabb]">{{ $template->key }}</p>
                                </td>
                                <td class="px-4 py-4 text-xs text-[#536078]">{{ $template->subject }}</td>
                                <td class="px-4 py-4 text-[11px] uppercase text-[#7b879a]">{{ $template->locale }}</td>
                                <td class="px-4 py-4 text-xs font-semibold">v{{ $template->version }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full {{ $template->is_enabled ? 'bg-[#eaf9f0] text-[#36a675]' : 'bg-[#f1f3f7] text-[#8b96a8]' }} px-2.5 py-1 text-[10px] font-semibold">
                                        {{ $template->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right sm:px-7">
                                    <a href="{{ route('admin.email-templates.preview', $template) }}" class="mr-3 text-[11px] font-semibold text-[#536078]">Preview</a>
                                    <a href="{{ route('admin.email-templates.edit', $template) }}" class="text-[11px] font-semibold text-[#7668f6]">Edit ↗</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-xs text-[#9aa5b7]">No email templates configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endcomponent
