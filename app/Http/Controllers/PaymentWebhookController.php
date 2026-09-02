<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentWebhookRequest;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function __invoke(PaymentWebhookRequest $request, DepositService $depositService, string $provider): JsonResponse
    {
        $event = $depositService->processWebhook(
            $request->validated(),
            $provider,
        );

        return response()->json([
            'event_id' => $event->provider_event_id,
            'status' => $event->status,
        ], $event->status === 'failed' ? 422 : 200);
    }
}
