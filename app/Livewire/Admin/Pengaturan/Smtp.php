<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Smtp extends Component
{
    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public string $mailer = 'smtp';
    public ?string $host = null;
    public ?int $port = null;
    public ?string $username = null;
    public ?string $password = null;
    public ?string $encryption = null;
    public ?string $from_address = null;
    public ?string $from_name = null;
    public ?int $timeout = null;

    protected ?string $storedPassword = null;

    public function mount(): void
    {
        $settings = SiteSetting::keyValue('smtp');

        if ($settings->isEmpty()) {
            $settings = SiteSetting::keyValue('mail');
        }

        $this->mailer = $settings->get('mailer', $settings->get('mail_mailer', Config::get('mail.default', 'smtp')));
        $this->host = $settings->get('host', $settings->get('mail_host', Config::get('mail.mailers.smtp.host')));

        $mailPort = $settings->get('port', $settings->get('mail_port', Config::get('mail.mailers.smtp.port')));
        $this->port = $mailPort !== null ? (int) $mailPort : null;

        $this->username = $settings->get('username', $settings->get('mail_username', Config::get('mail.mailers.smtp.username')));
        $this->storedPassword = $settings->get('password', $settings->get('mail_password'));
        $this->encryption = $settings->get('encryption', $settings->get('mail_scheme', Config::get('mail.mailers.smtp.encryption')));
        $this->from_address = $settings->get('from_address', $settings->get('mail_from_address', Config::get('mail.from.address')));
        $this->from_name = $settings->get('from_name', $settings->get('mail_from_name', Config::get('mail.from.name')));

        $mailTimeout = $settings->get('timeout', $settings->get('mail_timeout', Config::get('mail.mailers.smtp.timeout')));
        $this->timeout = $mailTimeout !== null ? (int) $mailTimeout : null;
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.smtp');
    }

    public function save(): void
    {
        $rules = [
            'mailer' => ['required', Rule::in(['smtp', 'sendmail', 'log', 'ses', 'postmark', 'resend'])],
            'host' => [Rule::requiredIf($this->mailer === 'smtp'), 'nullable', 'string', 'max:160'],
            'port' => [Rule::requiredIf($this->mailer === 'smtp'), 'nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:160'],
            'password' => ['nullable', 'string', 'max:160'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'from_address' => ['required', 'email', 'max:160'],
            'from_name' => ['nullable', 'string', 'max:160'],
            'timeout' => ['nullable', 'integer', 'min:0', 'max:600'],
        ];

        $validated = $this->validate($rules);

        $passwordToStore = $this->password !== null && $this->password !== ''
            ? $this->password
            : $this->storedPassword;

        $this->persist('mailer', $validated['mailer']);
        $this->persist('host', $validated['host']);
        $this->persist('port', $validated['port']);
        $this->persist('username', $validated['username']);
        $this->persist('encryption', $validated['encryption']);
        $this->persist('from_address', $validated['from_address']);
        $this->persist('from_name', $validated['from_name']);
        $this->persist('timeout', $validated['timeout']);

        if ($passwordToStore !== null) {
            $this->persist('password', $passwordToStore);
        }

        $this->storedPassword = $passwordToStore;
        $this->password = null;

        $this->applyRuntimeConfig($validated, $passwordToStore);

        session()->flash('status', 'Pengaturan SMTP berhasil disimpan.');
    }

    private function persist(string $key, $value): void
    {
        SiteSetting::updateOrCreate(
            ['group' => 'smtp', 'key' => $key],
            ['value' => $value]
        );
    }

    private function applyRuntimeConfig(array $validated, ?string $password): void
    {
        Config::set('mail.default', $validated['mailer']);
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $validated['host']);
        Config::set('mail.mailers.smtp.port', $validated['port']);
        Config::set('mail.mailers.smtp.username', $validated['username']);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.encryption', $validated['encryption']);
        Config::set('mail.mailers.smtp.timeout', $validated['timeout']);

        Config::set('mail.from.address', $validated['from_address']);
        Config::set('mail.from.name', $validated['from_name']);
    }
}
