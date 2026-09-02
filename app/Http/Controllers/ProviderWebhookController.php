<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentProviderException;
use App\Models\PaymentGateway;
use App\Services\DepositService;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $paymentGateway, PaymentGatewayManager $gatewayManager, DepositService $depositService): JsonResponse
    {
        try {
            $normalizedPayload = $gatewayManager->normalizeWebhook(
                $paymentGateway,
                $request->getContent(),
                $request->headers->all(),
            );
        } catch (PaymentProviderException $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
        $event = $depositService->processWebhook($normalizedPayload, $paymentGateway->driver);

        return response()->json([
            'event_id' => $event->provider_event_id,
            'status' => $event->status,
        ], $event->status === 'failed' ? 422 : 200);
    }
}
