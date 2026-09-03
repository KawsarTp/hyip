<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CmsPageRequest;
use App\Models\CmsPage;
use App\Services\EmailNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CmsPageController extends Controller
{
    public function index(): View
    {
        $pages = CmsPage::query()->with('updatedBy')->latest('updated_at')->get();

        return view('admin.cms.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.cms.create');
    }

    public function store(CmsPageRequest $request, EmailNotificationService $emailNotificationService): RedirectResponse
    {
        $page = CmsPage::query()->create($this->attributes($request));
        $this->queueLegalUpdate($page, $emailNotificationService);

        return redirect()->route('admin.cms.edit', $page)->with('status', 'CMS page created.');
    }

    public function edit(CmsPage $cmsPage): View
    {
        return view('admin.cms.edit', ['page' => $cmsPage]);
    }

    public function update(CmsPageRequest $request, CmsPage $cmsPage, EmailNotificationService $emailNotificationService): RedirectResponse
    {
        $cmsPage->update($this->attributes($request));
        $this->queueLegalUpdate($cmsPage, $emailNotificationService);

        return back()->with('status', 'CMS page updated.');
    }

    private function queueLegalUpdate(CmsPage $page, EmailNotificationService $emailNotificationService): void
    {
        if ($page->status !== 'published' || ! str($page->slug)->contains(['legal', 'terms', 'privacy', 'risk'])) {
            return;
        }

        $emailNotificationService->queueForActiveUsers(
            'legal_update',
            [
                'page_title' => $page->title,
                'page_url' => route('cms.pages.show', $page),
            ],
            "cms:legal-update:{$page->getKey()}:".Str::uuid(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(CmsPageRequest $request): array
    {
        $validated = $request->validated();

        return [
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $validated['status'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'published_at' => $validated['status'] === 'published' ? now() : null,
            'updated_by' => $request->user()->id,
        ];
    }
}
