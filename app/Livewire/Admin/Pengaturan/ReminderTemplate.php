<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\SiteSetting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ReminderTemplate extends Component
{
    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public string $bill_subject = 'Reminder Tagihan: :title';
    public string $bill_body = 'Segera lunasi tagihan sebesar :amount sebelum jatuh tempo pada :due_date.';
    public string $event_subject = 'Reminder Agenda: :title';
    public string $event_body = 'Agenda :title akan berlangsung pada :start_at di :location.';

    public function mount(): void
    {
        $this->bill_subject = $this->getSetting('reminder_bill_subject', $this->bill_subject);
        $this->bill_body = $this->getSetting('reminder_bill_body', $this->bill_body);
        $this->event_subject = $this->getSetting('reminder_event_subject', $this->event_subject);
        $this->event_body = $this->getSetting('reminder_event_body', $this->event_body);
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.reminder-template', [
            'tokens' => [
                ':title',
                ':user_name',
                ':amount',
                ':due_date',
                ':invoice',
                ':status',
                ':start_at',
                ':end_at',
                ':location',
            ],
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'bill_subject' => ['required', 'string', 'max:160'],
            'bill_body' => ['required', 'string'],
            'event_subject' => ['required', 'string', 'max:160'],
            'event_body' => ['required', 'string'],
        ]);

        $remappedKeys = [
            'bill_subject' => 'reminder_bill_subject',
            'bill_body' => 'reminder_bill_body',
            'event_subject' => 'reminder_event_subject',
            'event_body' => 'reminder_event_body',
        ];

        foreach ($data as $key => $value) {
            $settingKey = $remappedKeys[$key] ?? $key;
            $this->storeSetting($settingKey, $value);
        }

        session()->flash('status', 'Template email reminder berhasil diperbarui.');
    }

    private function storeSetting(string $key, mixed $value): void
    {
        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['group' => 'mail_templates', 'value' => $value]
        );
    }

    private function getSetting(string $key, mixed $default = null): mixed
    {
        return optional(SiteSetting::where('key', $key)->first())->value ?? $default;
    }
}
