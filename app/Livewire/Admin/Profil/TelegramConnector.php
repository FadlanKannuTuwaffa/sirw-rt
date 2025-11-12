<?php

namespace App\Livewire\Admin\Profil;

use App\Services\Telegram\TelegramLinkService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TelegramConnector extends Component
{
    public bool $isConnected = false;
    public ?array $account = null;
    public ?string $linkToken = null;
    public bool $notificationsEnabled = false;

    public function mount(): void
    {
        $this->refreshState();
    }

    public function render()
    {
        return view('livewire.admin.profil.telegram-connector');
    }

    public function generateToken(TelegramLinkService $linkService): void
    {
        $user = Auth::user();

        $token = $linkService->generateToken($user);

        $this->linkToken = $token->token;
        $this->refreshState();
    }

    public function disconnect(TelegramLinkService $linkService): void
    {
        if (! $this->isConnected) {
            return;
        }

        $linkService->unlink(Auth::user());
        $this->linkToken = null;

        $this->refreshState();

        session()->flash('status', 'Koneksi Telegram berhasil diputus.');
    }

    public function enableNotifications(TelegramLinkService $linkService): void
    {
        if (! $this->isConnected) {
            return;
        }

        $linkService->toggleSubscription(Auth::user(), true);
        $this->refreshState();

        session()->flash('status', 'Notifikasi Telegram diaktifkan kembali.');
    }

    private function refreshState(): void
    {
        $user = Auth::user()->load('telegramAccount');
        $account = $user->telegramAccount;

        if ($account && ! $account->unlinked_at) {
            $this->isConnected = true;
            $this->account = [
                'username' => $account->username,
                'first_name' => $account->first_name,
                'linked_at' => optional($account->linked_at)->translatedFormat('d M Y H:i'),
            ];
            $this->notificationsEnabled = (bool) $account->receive_notifications;
        } else {
            $this->isConnected = false;
            $this->account = null;
            $this->notificationsEnabled = false;
        }

        if ($account && $account->telegram_user_id && $this->linkToken === null) {
            $activeToken = $user->telegramLinkTokens()->active()->latest()->first();
            $this->linkToken = $activeToken?->token;
        }
    }
}
