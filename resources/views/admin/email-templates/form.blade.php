@php
    $bodyHtml = old('body_html', $template?->body_html ?? '');
    $bodyText = old('body_text', $template?->body_text ?? '');
    $safeBodyHtml = strip_tags($bodyHtml, '<p><br><strong><em><u><a><ul><ol><li><h2><h3><blockquote>');
    $safeBodyHtml = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/i', '', $safeBodyHtml) ?? $safeBodyHtml;
    $safeBodyHtml = preg_replace('/javascript\s*:/i', '', $safeBodyHtml) ?? $safeBodyHtml;
@endphp

@if ($errors->any())
    <div class="mt-6 rounded-xl border border-[#ffd8cf] bg-[#fff0ec] px-4 py-3 text-xs text-[#b94f3a]">
        <ul class="grid gap-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="post" class="mt-8 grid gap-5" data-email-template-form>
    @csrf
    @if ($httpMethod !== 'post')
        @method($httpMethod)
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <p class="text-[11px] font-semibold text-[#536078]">Template key</p>
            <p class="mt-2 rounded-xl border border-[#edf0f4] bg-[#f7f8fb] px-3 py-3 font-mono text-xs text-[#7b879a]">{{ $template->key }}</p>
        </div>
        <div>
            <p class="text-[11px] font-semibold text-[#536078]">Template name</p>
            <p class="mt-2 rounded-xl border border-[#edf0f4] bg-[#f7f8fb] px-3 py-3 text-sm text-[#7b879a]">{{ $template->name }}</p>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-[1fr_100px]">
        <div>
            <label for="subject" class="text-[11px] font-semibold text-[#536078]">Subject</label>
            <input id="subject" name="subject" value="{{ old('subject', $template->subject) }}" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm outline-none focus:border-[#7668f6]" placeholder="Your &#123;&#123;site_name&#125;&#125; update">
        </div>
        <div>
            <label for="locale" class="text-[11px] font-semibold text-[#536078]">Locale</label>
            <input id="locale" name="locale" value="{{ old('locale', $template->locale) }}" required maxlength="10" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-3 py-3 text-sm uppercase outline-none focus:border-[#7668f6]">
        </div>
    </div>

    <div>
        <label for="body_html" class="text-[11px] font-semibold text-[#536078]">Email body editor</label>
        <textarea id="body_html" name="body_html" required class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-white px-4 py-4 text-sm leading-6 outline-none focus:border-[#7668f6]">{{ $safeBodyHtml }}</textarea>
        <p class="mt-2 text-[10px] text-[#9aa5b7]">Use placeholders such as &#123;&#123;user_name&#125;&#125;, &#123;&#123;amount&#125;&#125;, and &#123;&#123;reference&#125;&#125;. Only predefined templates are editable.</p>
    </div>

    <div>
        <label for="body_text" class="text-[11px] font-semibold text-[#536078]">Plain-text fallback</label>
        <textarea id="body_text" name="body_text" rows="6" class="mt-2 w-full rounded-xl border border-[#dfe5ec] bg-[#fbfcfe] px-4 py-3 font-mono text-xs leading-5 outline-none focus:border-[#7668f6]">{{ $bodyText }}</textarea>
    </div>

    <label class="flex items-center gap-2 text-xs font-semibold text-[#536078]">
        <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $template->is_enabled)) class="rounded border-[#cfd7e4] text-[#7668f6]">
        Enable this template
    </label>

    <button class="rounded-xl bg-[#0a1324] px-4 py-3.5 text-xs font-semibold text-white">Save new version</button>
</form>

<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.css">
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<style>
    .email-template-editor .ck-editor__editable_inline {
        min-height: 280px;
    }

    .email-template-editor .ck.ck-editor__main > .ck-editor__editable {
        border-color: #dfe5ec;
        box-shadow: none;
    }

    .email-template-editor .ck.ck-editor__main > .ck-editor__editable.ck-focused {
        border-color: #7668f6;
        box-shadow: none;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-email-template-form]');
        const source = form?.querySelector('#body_html');

        if (!form || !source || typeof ClassicEditor === 'undefined') {
            return;
        }

        source.closest('div')?.classList.add('email-template-editor');

        ClassicEditor.create(source, {
            toolbar: {
                items: [
                    'undo',
                    'redo',
                    '|',
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    'link',
                    '|',
                    'bulletedList',
                    'numberedList',
                    'blockQuote',
                ],
                shouldNotGroupWhenFull: true,
            },
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                ],
            },
            link: {
                addTargetToExternalLinks: true,
            },
        }).then((editor) => {
            form.addEventListener('submit', () => {
                source.value = editor.getData().trim();
            });
        }).catch((error) => {
            console.error('Email template editor could not be initialized.', error);
        });
    });
</script>
