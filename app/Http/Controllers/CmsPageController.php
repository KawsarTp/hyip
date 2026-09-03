<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Services\TemplateRenderer;
use Illuminate\Contracts\View\View;

class CmsPageController extends Controller
{
    public function show(CmsPage $cmsPage, TemplateRenderer $renderer): View
    {
        abort_unless($cmsPage->status === 'published' && ($cmsPage->published_at === null || $cmsPage->published_at->isPast()), 404);

        return view('cms.show', [
            'page' => $cmsPage,
            'body' => $renderer->render($cmsPage->body),
        ]);
    }
}
