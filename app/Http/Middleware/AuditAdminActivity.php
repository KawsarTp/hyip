<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditAdminActivity
{
    public function __construct(private readonly AuditService $auditService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->auditService->record(
                $this->actionFor($request),
                $this->subjectFrom($request),
                metadata: [
                    'response_status' => $response->getStatusCode(),
                    'parameters' => $this->routeParameters($request),
                    'decision' => $this->safeInput($request, 'decision'),
                    'status' => $this->safeInput($request, 'status'),
                ],
                reason: $this->safeInput($request, 'review_notes'),
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function actionFor(Request $request): string
    {
        return match ($request->method()) {
            'GET', 'HEAD' => 'viewed',
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => strtolower($request->method()),
        };
    }

    private function subjectFrom(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return $parameter;
            }
        }

        return null;
    }

    /**
     * @return array<string, int|string>
     */
    private function routeParameters(Request $request): array
    {
        $parameters = [];

        foreach ($request->route()?->parameters() ?? [] as $name => $parameter) {
            if ($parameter instanceof Model) {
                $parameters[$name] = (int) $parameter->getKey();
            } elseif (is_int($parameter) || is_string($parameter)) {
                $parameters[$name] = $parameter;
            }
        }

        return $parameters;
    }

    private function safeInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : null;
    }
}
