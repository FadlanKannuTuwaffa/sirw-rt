<?php

namespace App\Notifications;

use App\Models\Bill;
use App\Models\Payment;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentPaidNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment
    ) {
        $this->payment->loadMissing(['bill', 'user']);
    }

    public function via(object $notifiable): array
    {
        return ['mail', TelegramChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->payment;
        $bill = $payment->bill;

        $timezone = config('app.timezone', 'Asia/Jakarta');
        $paidAt = optional($payment->paid_at)->copy();

        if ($paidAt instanceof Carbon) {
            $paidAt = $paidAt->setTimezone($timezone);
        }

        $data = [
            'user' => $notifiable,
            'payment' => $payment,
            'bill' => $bill,
            'paidAtFormatted' => $paidAt ? $paidAt->translatedFormat('d M Y H:i') . ' ' . $this->timezoneLabel($timezone) : '-',
            'totalFormatted' => $this->formatCurrency($payment->customer_total),
            'amountFormatted' => $this->formatCurrency($payment->amount),
            'feeFormatted' => $payment->fee_amount ? $this->formatCurrency($payment->fee_amount) : null,
            'invoice' => $this->invoiceNumber($bill),
            'channel' => $this->paymentChannel($payment),
            'reference' => $payment->reference,
        ];

        return (new MailMessage())
            ->subject('Pembayaran Tagihan Berhasil')
            ->view('mail.payment-paid', $data);
    }

    public function toTelegram(object $notifiable): array
    {
        $payment = $this->payment;
        $bill = $payment->bill;
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $paidAt = optional($payment->paid_at)->copy();

        if ($paidAt instanceof Carbon) {
            $paidAt = $paidAt->setTimezone($timezone);
        }

        $lines = [
            '<b>&#9989; Pembayaran Terkonfirmasi</b>',
            '',
            sprintf('<b>Total</b>: %s', $this->escape($this->formatCurrency($payment->customer_total))),
        ];

        if ($bill) {
            $lines[] = sprintf('<b>Tagihan</b>: %s', $this->escape($bill->title ?: 'Tagihan Warga'));
        }

        $lines[] = sprintf('<b>Nomor</b>: %s', $this->escape($this->invoiceNumber($bill)));

        if ($paidAt instanceof Carbon) {
            $lines[] = sprintf(
                '<b>Tanggal</b>: %s %s',
                $this->escape($paidAt->translatedFormat('d M Y H:i')),
                $this->escape($this->timezoneLabel($timezone))
            );
        }

        $lines[] = sprintf('<b>Metode</b>: %s', $this->escape($this->paymentChannel($payment)));

        if ($payment->reference) {
            $lines[] = sprintf('<b>Ref</b>: %s', $this->escape($payment->reference));
        }

        $lines[] = '';
        $lines[] = 'Terima kasih sudah melakukan pembayaran tepat waktu. Mari terus jaga lingkungan kita bersama. &#128588;';

        return [
            'message' => implode("\n", $lines),
        ];
    }

    private function formatCurrency(int|float|null $amount): string
    {
        $value = (int) ($amount ?? 0);

        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    private function invoiceNumber(?Bill $bill): string
    {
        if (! $bill) {
            return '#-' . str_pad((string) $this->payment->id, 4, '0', STR_PAD_LEFT);
        }

        if ($bill->invoice_number) {
            return $bill->invoice_number;
        }

        return '#' . str_pad((string) $bill->id, 4, '0', STR_PAD_LEFT);
    }

    private function paymentChannel(Payment $payment): string
    {
        if ($payment->manual_channel) {
            return ucfirst($payment->manual_channel);
        }

        if ($payment->gateway) {
            return strtoupper($payment->gateway);
        }

        return 'Pembayaran Warga';
    }

    private function timezoneLabel(string $timezone): string
    {
        if (str_contains($timezone, '/')) {
            return strtoupper(Str::afterLast($timezone, '/'));
        }

        return strtoupper($timezone);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
