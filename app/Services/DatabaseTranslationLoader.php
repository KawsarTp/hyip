<?php

namespace App\Services;

use App\Models\Language;
use App\Models\TranslationLine;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Arr;
use Throwable;

class DatabaseTranslationLoader implements Loader
{
    public function __construct(private readonly Loader $fileLoader) {}

    public function load($locale, $group, $namespace = null): array
    {
        $messages = $this->fileLoader->load($locale, $group, $namespace);

        if ($namespace !== null && $namespace !== '*') {
            return $messages;
        }

        try {
            $languageId = Language::query()
                ->where('code', $locale)
                ->where('is_enabled', true)
                ->value('id');

            if ($languageId === null) {
                return $messages;
            }

            $lines = TranslationLine::query()
                ->where('language_id', $languageId)
                ->where('group_name', $group)
                ->get(['key', 'value']);

            foreach ($lines as $line) {
                Arr::set($messages, $line->key, $line->value);
            }
        } catch (Throwable) {
            return $messages;
        }

        return $messages;
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->fileLoader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->fileLoader->addJsonPath($path);
    }

    public function namespaces(): array
    {
        return $this->fileLoader->namespaces();
    }
}
