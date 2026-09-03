<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Services\TemplateRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = EmailTemplate::query()->with('updatedBy')->orderBy('name')->get();

        return view('admin.email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.email-templates.edit', ['template' => $emailTemplate]);
    }

    public function update(EmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update(array_merge($this->attributes($request), ['version' => $emailTemplate->version + 1]));

        return back()->with('status', 'Email template version saved.');
    }

    public function preview(EmailTemplate $emailTemplate, TemplateRenderer $renderer): View
    {
        $variables = ['user_name' => 'Jordan Davis', 'site_name' => 'Arcadia', 'support_email' => 'support@example.com', 'amount' => '100.00', 'reference' => 'PREVIEW-001'];

        return view('admin.email-templates.preview', [
            'template' => $emailTemplate,
            'subject' => $renderer->render($emailTemplate->subject, $variables),
            'body' => $renderer->render($emailTemplate->body_html, $variables),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(EmailTemplateRequest $request): array
    {
        $validated = $request->validated();

        return [
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? null,
            'locale' => $validated['locale'],
            'is_enabled' => $request->boolean('is_enabled'),
            'updated_by' => $request->user()->id,
        ];
    }
}
