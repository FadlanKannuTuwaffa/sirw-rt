<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $manager = PaymentGatewayManager::resolve();
        $service = new PaymentService();
        $logContext = [
            'path' => $request->path(),
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
        ];

        if (app()->environment('local', 'testing')) {
            $logContext['raw_body'] = $request->getContent();
        } else {
            $logContext['raw_body_length'] = strlen((string) $request->getContent());
        }

        Log::debug('Webhook received', $logContext);
        $payload = $request->all();

        if ($request->isMethod('get')) {
            return response()->json([
                'success' => true,
                'message' => 'Payment webhook endpoint reachable. Send POST notifications from Tripay.',
            ]);
        }

        if ($this->isTripay($request, $payload)) {
            return $this->handleTripay($request, $payload, $manager, $service);
        }

        Log::warning('Unrecognized payment webhook payload', ['payload' => $payload]);

        return response()->json([
            'success' => false,
            'message' => 'Payload tidak dikenali',
        ], Response::HTTP_BAD_REQUEST);
    }

    private function handleTripay(Request $request, array $payload, PaymentGatewayManager $manager, PaymentService $service): JsonResponse
    {
        $context = $this->buildTripayContext($request, $payload, $manager);

        if ($response = $this->validateTripayContext($request, $payload, $context)) {
            return $response;
        }

        $candidates = $this->buildTripaySignatureCandidates($payload, $context);
        if (! $this->verifyTripaySignature($context, $candidates, $payload)) {
            return response()->json(['success' => false, 'message' => 'invalid-signature'], Response::HTTP_FORBIDDEN);
        }

        $payment = $this->findTripayPaymentByReference($context['merchant_ref'], true);

        if (! $payment) {
            Log::warning('Tripay payment not found', [
                'merchant_ref' => $context['merchant_ref'],
                'tripay_reference' => $context['reference'],
            ]);

            return response()->json(['success' => false, 'message' => 'not-found'], Response::HTTP_NOT_FOUND);
        }

        $paidAt = $this->resolveTripayPaidAt($payload);

        if ($this->isSettledStatus($context['status']) && ! $this->verifyTripayAmount($payment, $context, $payload)) {
            Log::warning('Tripay amount mismatch', [
                'merchant_ref' => $context['merchant_ref'],
                'tripay_reference' => $context['reference'],
                'expected_total' => $payment->customer_total ?? ($payment->amount + ($payment->fee_amount ?? 0)),
                'received_amount' => $context['amount'],
                'payload_amount' => Arr::get($payload, 'amount'),
                'payload_total_amount' => Arr::get($payload, 'total_amount'),
            ]);

            return response()->json(['success' => false, 'message' => 'amount-mismatch'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->applyTripayStatus($service, $payment, $context['status'], $paidAt, $payload);

        return response()->json(['success' => true]);
    }

    private function isTripay(Request $request, array $payload): bool
    {
        return Arr::has($payload, ['merchant_ref']) && $request->hasHeader('x-callback-signature');
    }

    /**
     * @return array{
     *     private_key: string|null,
     *     merchant_code: string|null,
     *     merchant_ref: string|null,
     *     reference: string|null,
     *     status: string,
     *     raw_status: string|null,
     *     amount: mixed,
     *     signature: string|null,
     *     event: string|null
     * }
     */
    private function buildTripayContext(Request $request, array $payload, PaymentGatewayManager $manager): array
    {
        return [
            'private_key' => $manager->config('tripay_private_key'),
            'merchant_code' => $manager->config('tripay_merchant_code'),
            'merchant_ref' => Arr::get($payload, 'merchant_ref'),
            'reference' => Arr::get($payload, 'reference'),
            'status' => strtoupper((string) Arr::get($payload, 'status', 'UNPAID')),
            'raw_status' => Arr::get($payload, 'status'),
            'amount' => Arr::get($payload, 'total_amount', Arr::get($payload, 'amount')),
            'signature' => $request->header('x-callback-signature'),
            'event' => $request->header('x-callback-event'),
        ];
    }

    private function validateTripayContext(Request $request, array $payload, array $context): ?JsonResponse
    {
        if (! $context['private_key'] || ! $context['merchant_code'] || ! $context['merchant_ref'] || ! $context['signature']) {
            Log::warning('Tripay webhook missing credentials', [
                'payload' => $payload,
                'headers' => $request->headers->all(),
            ]);

            return response()->json(['success' => false, 'message' => 'invalid'], Response::HTTP_BAD_REQUEST);
        }

        if ($context['event'] && $context['event'] !== 'payment_status') {
            Log::info('Tripay webhook ignored non payment_status event', ['event' => $context['event']]);

            return response()->json(['success' => true, 'message' => 'ignored']);
        }

        return null;
    }

    private function buildTripaySignatureCandidates(array $payload, array $context): Collection
    {
        $status = $context['raw_status'];
        $reference = $context['reference'];
        $merchantCode = $context['merchant_code'];
        $merchantRef = $context['merchant_ref'];

        $candidates = [];

        foreach ($this->normalizeTripayAmounts($context['amount']) as $amountVariant) {
            $candidates[] = $merchantCode . $merchantRef . $amountVariant;
            $candidates[] = $merchantCode . $merchantRef . ($status ?? '') . $amountVariant;
            $candidates[] = $merchantCode . $reference . $amountVariant;
            $candidates[] = $merchantCode . $reference . ($status ?? '') . $amountVariant;
            $candidates[] = $merchantRef . ($status ?? '') . $amountVariant;
            $candidates[] = $reference . ($status ?? '') . $amountVariant;
            $candidates[] = $merchantCode . $merchantRef . $reference . ($status ?? '') . $amountVariant;
            $candidates[] = $merchantCode . $reference . $merchantRef . ($status ?? '') . $amountVariant;
        }

        $jsonPayloads = [
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK),
            json_encode($payload),
        ];

        foreach ($jsonPayloads as $encoded) {
            if ($encoded) {
                $candidates[] = $encoded;
            }
        }

        return collect($candidates)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values();
    }

    private function verifyTripaySignature(array $context, Collection $candidates, array $payload): bool
    {
        $matchingPayload = $candidates->first(function ($candidate) use ($context) {
            $candidateSignature = hash_hmac('sha256', $candidate, $context['private_key']);

            return hash_equals($candidateSignature, (string) $context['signature']);
        });

        if ($matchingPayload) {
            return true;
        }

        $logContext = [
            'merchant_ref' => $context['merchant_ref'],
            'tripay_reference' => $context['reference'],
            'expected_signature' => $candidates->map(fn ($candidate) => hash_hmac('sha256', $candidate, (string) $context['private_key']))->all(),
            'received_signature' => $context['signature'],
            'signature_candidates' => $candidates->all(),
        ];

        if (app()->environment('local', 'testing')) {
            $logContext['raw_payload'] = $payload;
        }

        Log::warning('Tripay signature mismatch', $logContext);

        return false;
    }

    /**
     * @return list<string>
     */
    private function normalizeTripayAmounts(mixed $amount): array
    {
        return collect([$amount])
            ->filter(fn ($value) => ! is_null($value))
            ->flatMap(function ($value) {
                if (is_numeric($value)) {
                    $base = (float) $value;

                    return [
                        (string) $base,
                        number_format($base, 0, '.', ''),
                        number_format($base, 2, '.', ''),
                    ];
                }

                return [(string) $value];
            })
            ->unique()
            ->values()
            ->all();
    }

    private function findTripayPaymentByReference(string $reference, bool $enforceGateway = false): ?Payment
    {
        $query = Payment::query()->where('reference', $reference);

        if ($enforceGateway) {
            $query->where('gateway', 'tripay');
        }

        return $query->first();
    }

    private function isSettledStatus(string $status): bool
    {
        return in_array($status, ['PAID', 'SUCCESS', 'COMPLETED'], true);
    }

    private function verifyTripayAmount(Payment $payment, array $context, array $payload): bool
    {
        $receivedCandidates = $this->normalizeTripayAmounts($context['amount'] ?? Arr::get($payload, 'total_amount'));
        if (empty($receivedCandidates)) {
            return false;
        }

        $expectedTotal = $payment->customer_total;
        if ($expectedTotal === null || $expectedTotal <= 0) {
            $expectedTotal = (int) $payment->amount + (int) ($payment->fee_amount ?? 0);
        }

        $expectedCandidates = $this->normalizeTripayAmounts($expectedTotal);

        return ! empty(array_intersect($receivedCandidates, $expectedCandidates));
    }

    private function resolveTripayPaidAt(array $payload): Carbon
    {
        $paidAtRaw = Arr::get($payload, 'paid_at');

        $timezone = config('app.timezone', 'Asia/Jakarta');

        if (is_numeric($paidAtRaw)) {
            return Carbon::createFromTimestamp((int) $paidAtRaw, 'UTC')->setTimezone($timezone);
        }

        if (! empty($paidAtRaw)) {
            try {
                return Carbon::parse($paidAtRaw)->setTimezone($timezone);
            } catch (\Throwable) {
                // fall through to now()
            }
        }

        return now($timezone);
    }

    private function applyTripayStatus(PaymentService $service, Payment $payment, string $status, Carbon $paidAt, array $payload): void
    {
        if (in_array($status, ['PAID', 'SUCCESS', 'COMPLETED'], true)) {
            $service->markPaid($payment, $paidAt, $payload);

            return;
        }

        if ($status === 'EXPIRED') {
            $service->markFailed($payment, 'expired', $payload);

            return;
        }

        if (in_array($status, ['FAILED', 'CANCELLED'], true)) {
            $service->markFailed($payment, 'failed', $payload);

            return;
        }

        Log::info('Tripay status pending', [
            'merchant_ref' => $payment->reference,
            'status' => $status,
        ]);
    }
}
