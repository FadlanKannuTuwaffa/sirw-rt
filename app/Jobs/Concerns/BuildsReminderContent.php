<?php

namespace App\Jobs\Concerns;

use App\Models\Bill;
use App\Models\Event;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Collection;

trait BuildsReminderContent
{
    protected array $templateCache = [];

    protected function buildSubject(Bill|Event $model, ?User $user = null): string
    {
        $default = $model instanceof Bill
            ? 'Reminder Tagihan: ' . $model->title
            : 'Reminder Agenda: ' . $model->title;

        $key = $model instanceof Bill ? 'reminder_bill_subject' : 'reminder_event_subject';
        $template = $this->getTemplate($key, $default);

        return $this->replaceTokens($template, $model, $user);
    }

    protected function buildBody(Bill|Event $model, ?User $user = null): string
    {
        $default = $model instanceof Bill
            ? 'Segera lunasi tagihan sebesar :amount sebelum jatuh tempo pada :due_date.'
            : 'Agenda :title akan berlangsung pada :start_at di :location.';

        $key = $model instanceof Bill ? 'reminder_bill_body' : 'reminder_event_body';
        $template = $this->getTemplate($key, $default);

        return $this->replaceTokens($template, $model, $user);
    }

    protected function buildMetadata(Bill|Event $model): array
    {
        if ($model instanceof Bill) {
            return [
                'Invoice' => $model->invoice_number,
                'Jenis' => ucfirst($model->type),
                'Status' => ucfirst($model->status ?? ''),
            ];
        }

        return [
            'Lokasi' => $model->location ?? 'Akan diinformasikan',
            'Mulai' => optional($model->start_at)->translatedFormat('d F Y H:i') ?? '-',
            'Selesai' => optional($model->end_at)->translatedFormat('d F Y H:i') ?? '-',
        ];
    }

    protected function billRecipients(Bill $bill): Collection
    {
        return collect([$bill->user])->filter(fn ($user) => $user instanceof User);
    }

    protected function eventRecipients(Event $event): Collection
    {
        return User::residents()
            ->where('status', 'aktif')
            ->with('telegramAccount')
            ->get()
            ->filter(function (User $user) {
                $telegram = $user->telegramAccount;

                $telegramEnabled = $telegram && is_null($telegram->unlinked_at) && $telegram->receive_notifications;

                return ($user->email !== null && $user->email !== '') || $telegramEnabled;
            });
    }

    protected function getTemplate(string $key, string $default): string
    {
        if (array_key_exists($key, $this->templateCache)) {
            return $this->templateCache[$key];
        }

        $value = SiteSetting::where('key', $key)->first()?->value;

        return $this->templateCache[$key] = $value ?: $default;
    }

    protected function replaceTokens(string $template, Bill|Event $model, ?User $user = null): string
    {
        $replacements = [
            ':title' => $model->title ?? '-',
            ':user_name' => $user?->name ?? 'Warga',
        ];

        if ($model instanceof Bill) {
            $replacements += [
                ':amount' => 'Rp ' . number_format($model->amount),
                ':due_date' => optional($model->due_date)->translatedFormat('d F Y') ?? '-',
                ':invoice' => $model->invoice_number ?? '-',
                ':status' => ucfirst($model->status ?? ''),
            ];
        } else {
            $replacements += [
                ':start_at' => optional($model->start_at)->translatedFormat('d F Y H:i') ?? '-',
                ':end_at' => optional($model->end_at)->translatedFormat('d F Y H:i') ?? '-',
                ':location' => $model->location ?? '-',
            ];
        }

        return strtr($template, $replacements);
    }
}
