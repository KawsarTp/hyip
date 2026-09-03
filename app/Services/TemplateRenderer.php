<?php

namespace App\Services;

use App\Models\SiteSetting;

class TemplateRenderer
{
    /**
     * Render only approved {{ variable }} placeholders; never execute submitted PHP or Blade.
     *
     * @param  array<string, scalar|null>  $variables
     */
    public function render(string $content, array $variables = []): string
    {
        $settings = SiteSetting::query()->pluck('value', 'key')->all();
        $values = array_merge($settings, $variables);

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $matches) use ($values): string {
            $value = $values[$matches[1]] ?? '';

            return is_scalar($value) ? (string) $value : '';
        }, $content) ?? $content;
    }
}
