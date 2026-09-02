<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Record an immutable activity entry without persisting secrets or private file paths.
     *
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        ?Model $subject = null,
        ?string $category = null,
        array $metadata = [],
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
    ): AuditLog {
        $request = app()->bound('request') ? request() : null;
        $route = $request?->route();

        return AuditLog::query()->create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'category' => $category ?? $this->categoryFor($route instanceof Route ? $route->getName() : null),
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'route' => $route instanceof Route ? $route->getName() : null,
            'method' => $request?->method(),
            'request_id' => $request?->header('X-Request-ID') ?? Str::uuid()->toString(),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 1000, ''),
            'metadata' => $this->sanitize($metadata),
            'before' => $before === null ? null : $this->sanitize($before),
            'after' => $after === null ? null : $this->sanitize($after),
            'reason' => $reason === null ? null : Str::limit($reason, 2000, ''),
        ]);
    }

    private function categoryFor(?string $route): string
    {
        return match (true) {
            $route !== null && str($route)->contains('kyc') => 'compliance',
            $route !== null && str($route)->contains(['deposit', 'withdrawal', 'payment', 'ledger']) => 'financial',
            $route !== null && str($route)->contains(['user', 'role', 'security']) => 'security',
            $route !== null && str($route)->contains(['referral', 'investment', 'plan']) => 'operations',
            default => 'system',
        };
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (str($normalizedKey)->contains(['password', 'token', 'secret', 'api_key', 'client_secret', 'path', 'payload'])) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $sanitized;
    }
}
