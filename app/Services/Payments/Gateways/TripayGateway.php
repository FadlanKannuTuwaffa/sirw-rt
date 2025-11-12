<?php

namespace App\Services\Payments\Gateways;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Payments\PaymentFeeEstimator;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class TripayGateway implements PaymentGatewayContract
{
    public function __construct(private array $config = [])
    {
    }

    public function initiate(Bill $bill, User $user): array
    {
        if ($reuse = $this->reuseExistingPayment($bill, $user)) {
            $this->cancelPendingManualPayments($bill, $user);
            return $reuse;
        }

        $apiKey = Arr::get($this->config, 'tripay_api_key');
        $privateKey = Arr::get($this->config, 'tripay_private_key');
        $merchantCode = Arr::get($this->config, 'tripay_merchant_code');
        $channel = Arr::get($this->config, 'tripay_channel');
        $mode = Arr::get($this->config, 'tripay_mode', 'sandbox');
        $callbackUrl = Arr::get($this->config, 'payment_callback_url') ?? URL::route('payments.webhook');

        if (!$apiKey || !$privateKey || !$merchantCode || !$channel) {
            throw new PaymentException('Pengaturan Tripay belum lengkap.');
        }

        $merchantRef = $this->generateMerchantRef($bill);
        $amount = (int) $bill->amount;
        $expiresAt = now()->addHours(24);

        $signature = hash_hmac('sha256', $merchantCode . $merchantRef . $amount, $privateKey);

        $payload = [
            'method' => $channel,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => Str::limit($user->name ?? 'Warga', 60),
            'customer_email' => $user->email ?: 'user-' . $user->id . '@example.com',
            'customer_phone' => $user->phone ?: '0800000000',
            'order_items' => [[
                'sku' => 'BILL-' . $bill->id,
                'name' => Str::limit($bill->title, 60),
                'price' => $amount,
                'quantity' => 1,
            ]],
            'expired_time' => $expiresAt->getTimestamp(),
            'callback_url' => $callbackUrl,
            'return_url' => URL::route('resident.bills'),
            'signature' => $signature,
        ];

        $endpoint = $mode === 'production'
            ? 'https://tripay.co.id/api/transaction/create'
            : 'https://tripay.co.id/api-sandbox/transaction/create';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->acceptJson()
                ->post($endpoint, $payload)
                ->throw()
                ->json();
        } catch (Throwable $e) {
            throw new PaymentException('Gagal membuat transaksi Tripay.', previous: $e);
        }

        if (!Arr::get($response, 'success', false)) {
            $message = Arr::get($response, 'message', 'Transaksi Tripay gagal dibuat.');
            throw new PaymentException('Gagal membuat transaksi Tripay: ' . $message);
        }

        $data = Arr::get($response, 'data');
        if (!$data) {
            throw new PaymentException('Tripay tidak mengembalikan data transaksi.');
        }

        if (!Arr::get($data, 'checkout_url')) {
            throw new PaymentException('Tripay tidak memberikan tautan checkout.');
        }

        [$customerTotal, $feeAmount] = $this->resolveTotalsFromTripay($bill, $data);

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'user_id' => $user->id,
            'gateway' => 'tripay',
            'status' => 'pending',
            'amount' => $bill->amount,
            'fee_amount' => $feeAmount,
            'customer_total' => $customerTotal,
            'reference' => $merchantRef,
            'raw_payload' => [
                'request' => $payload,
                'response' => $response,
            ],
        ]);

        $this->cancelPendingManualPayments($bill, $user);

        $bill->update([
            'gateway_fee' => $feeAmount,
            'total_amount' => $customerTotal,
        ]);

        $expiresAtIso = Arr::get($data, 'expired_time')
            ? Carbon::createFromTimestamp(Arr::get($data, 'expired_time'))->toIso8601String()
            : $expiresAt->toIso8601String();

        return [
            'payment' => $payment,
            'checkout' => [
                'provider' => 'tripay',
                'checkout_url' => Arr::get($data, 'checkout_url'),
                'tripay_reference' => Arr::get($data, 'reference'),
                'merchant_ref' => $merchantRef,
                'method' => Arr::get($data, 'payment_name', $channel),
                'expires_at' => $expiresAtIso,
                'amount' => $payment->customer_total,
                'fee_amount' => $payment->fee_amount,
            ],
        ];
    }

    private function generateMerchantRef(Bill $bill): string
    {
        return Str::of($bill->invoice_number ?? 'INV-' . $bill->id)
            ->slug('-')
            ->upper()
            ->append('-' . Str::upper(Str::random(6)))
            ->value();
    }

    private function reuseExistingPayment(Bill $bill, User $user): ?array
    {
        $existing = Payment::query()
            ->where('bill_id', $bill->id)
            ->where('user_id', $user->id)
            ->where('gateway', 'tripay')
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        if (! $existing) {
            return null;
        }

        $requestedChannel = strtoupper((string) Arr::get($this->config, 'tripay_channel'));
        $existingChannel = strtoupper((string) data_get($existing->raw_payload, 'request.method'));

        if ($requestedChannel && $existingChannel && $existingChannel !== $requestedChannel) {
            $raw = $existing->raw_payload ?? [];
            $raw['expired_locally_at'] = now()->toIso8601String();

            $existing->update([
                'status' => 'expired',
                'raw_payload' => $raw,
            ]);

            return null;
        }

        $checkout = $this->buildCheckoutPayload($existing);

        if ($checkout) {
            $this->expireDuplicatePayments($bill, $user, $existing->id);

            return [
                'payment' => $existing,
                'checkout' => $checkout,
            ];
        }

        $raw = $existing->raw_payload ?? [];
        $raw['expired_locally_at'] = now()->toIso8601String();

        $existing->update([
            'status' => 'expired',
            'raw_payload' => $raw,
        ]);

        return null;
    }

    private function buildCheckoutPayload(Payment $payment): ?array
    {
        $payload = $payment->raw_payload ?? [];
        $data = Arr::get($payload, 'response.data', []);

        $checkoutUrl = Arr::get($data, 'checkout_url');
        if (! $checkoutUrl) {
            return null;
        }

        $expiresTimestamp = Arr::get($data, 'expired_time');
        $expiresAt = $expiresTimestamp ? Carbon::createFromTimestamp((int) $expiresTimestamp) : null;

        if ($expiresAt && $expiresAt->isPast()) {
            return null;
        }

        $bill = $payment->relationLoaded('bill') ? $payment->bill : $payment->bill()->first();
        if ($bill) {
            [$customerTotal, $feeAmount] = $this->resolveTotalsFromTripay($bill, $data);
            $this->syncPaymentTotals($payment, $customerTotal, $feeAmount, $bill);
        }

        return [
            'provider' => 'tripay',
            'checkout_url' => $checkoutUrl,
            'tripay_reference' => Arr::get($data, 'reference'),
            'merchant_ref' => $payment->reference,
            'method' => Arr::get($data, 'payment_name', Arr::get($data, 'payment_method', 'Tripay')),
            'expires_at' => $expiresAt?->toIso8601String(),
            'amount' => $payment->customer_total,
            'fee_amount' => $payment->fee_amount,
        ];
    }

    private function expireDuplicatePayments(Bill $bill, User $user, int $keepId): void
    {
        $duplicates = Payment::query()
            ->where('bill_id', $bill->id)
            ->where('user_id', $user->id)
            ->where('gateway', 'tripay')
            ->where('status', 'pending')
            ->where('id', '!=', $keepId)
            ->get();

        foreach ($duplicates as $duplicate) {
            $raw = $duplicate->raw_payload ?? [];
            $raw['expired_locally_at'] = now()->toIso8601String();

            $duplicate->update([
                'status' => 'expired',
                'raw_payload' => $raw,
            ]);
        }
    }

    /**
     * Determine customer total and fee amount based on Tripay response and stored bill data.
     *
     * @return array{0:int,1:int}
     */
    private function resolveTotalsFromTripay(Bill $bill, array $responseData = []): array
    {
        $baseAmount = (int) $bill->amount;
        $total = $this->normalizeAmount(Arr::get($responseData, 'total_amount'));
        $feeCustomer = $this->normalizeAmount(Arr::get($responseData, 'fee_customer'));

        if ($feeCustomer <= 0) {
            $feeCustomer = $this->normalizeAmount(Arr::get($responseData, 'fee', 0));
        }

        if ($total <= 0) {
            $total = $this->normalizeAmount(Arr::get($responseData, 'amount', 0));
        }

        if ($total <= 0 && $feeCustomer > 0) {
            $total = $baseAmount + $feeCustomer;
        }

        if ($feeCustomer <= 0 && $total > 0) {
            $feeCustomer = max($total - $baseAmount, 0);
        }

        // Fallback to stored bill data if API response is missing fee information
        if ($total <= 0 || $feeCustomer < 0) {
            $storedTotal = (int) ($bill->total_amount ?? 0);
            $storedFee = (int) ($bill->gateway_fee ?? 0);
            if ($storedTotal > 0) {
                $total = $storedTotal;
                $feeCustomer = max($storedFee, $storedTotal - $baseAmount);
            } else {
                $estimate = PaymentFeeEstimator::resolve()->estimate($baseAmount);
                $total = $estimate['total'];
                $feeCustomer = $estimate['fee'];
            }
        }

        $total = max($total, $baseAmount);
        $feeCustomer = max($feeCustomer, $total - $baseAmount);

        return [$total, $feeCustomer];
    }

    private function syncPaymentTotals(Payment $payment, int $customerTotal, int $feeAmount, ?Bill $bill = null): void
    {
        $updates = [];

        if ($payment->customer_total !== $customerTotal) {
            $updates['customer_total'] = $customerTotal;
        }

        if ($payment->fee_amount !== $feeAmount) {
            $updates['fee_amount'] = $feeAmount;
        }

        if (!empty($updates)) {
            $payment->update($updates);
        }

            $bill ??= $payment->relationLoaded('bill') ? $payment->bill : $payment->bill()->first();
            if ($bill) {
                $billUpdates = [];

                if ((int) $bill->gateway_fee !== $feeAmount) {
                $billUpdates['gateway_fee'] = $feeAmount;
            }

            if ((int) $bill->total_amount !== $customerTotal) {
                $billUpdates['total_amount'] = $customerTotal;
            }

            if (!empty($billUpdates)) {
                $bill->update($billUpdates);
            }
        }
    }

    private function normalizeAmount(mixed $value): int
    {
        if (is_null($value)) {
            return 0;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return (int) round((float) $value, 0, PHP_ROUND_HALF_UP);
    }

    private function cancelPendingManualPayments(Bill $bill, User $user): void
    {
        $pendingManual = Payment::query()
            ->where('bill_id', $bill->id)
            ->where('user_id', $user->id)
            ->where('gateway', 'manual')
            ->where('status', 'pending')
            ->get();

        if ($pendingManual->isEmpty()) {
            return;
        }

        foreach ($pendingManual as $manualPayment) {
            $raw = $manualPayment->raw_payload ?? [];
            $raw['cancelled_at'] = now()->toIso8601String();
            $raw['cancelled_reason'] = 'Pembayaran dialihkan ke Tripay.';

            $manualPayment->update([
                'status' => 'cancelled',
                'raw_payload' => $raw,
            ]);
        }
    }
}
