<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserLoginDevice;
use App\Notifications\Security\NewDeviceLoginNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AccountSecurityService
{
    private const ALERT_COOLDOWN_MINUTES = 60;

    public function handleLogin(User $user, Request $request): void
    {
        $fingerprint = $this->generateFingerprint($request);
        $now = now();

        $device = UserLoginDevice::query()->where([
            'user_id' => $user->id,
            'device_fingerprint' => $fingerprint,
        ])->first();

        $ipAddress = $this->sanitizeIp($request->ip());
        $agent = $this->sanitizeUserAgent($request->userAgent());
        $label = $this->resolveDeviceLabel($agent);

        $isNewDevice = false;
        $ipChanged = false;

        if (! $device) {
            $isNewDevice = true;
            $device = new UserLoginDevice([
                'user_id' => $user->id,
                'device_fingerprint' => $fingerprint,
            ]);
            $device->first_seen_at = $now;
        } elseif ($device->ip_address !== $ipAddress) {
            $ipChanged = true;
        }

        $previousAlertedAt = $device->last_alerted_at;

        $device->fill([
            'device_label' => $label,
            'ip_address' => $ipAddress,
            'user_agent' => $agent,
            'last_used_at' => $now,
        ]);

        $device->save();

        if (! ($isNewDevice || $ipChanged)) {
            return;
        }

        if (! $this->shouldAlert($previousAlertedAt, $now)) {
            return;
        }

        $device->forceFill(['last_alerted_at' => $now])->save();

        $context = [
            'device_label' => $label,
            'ip_address' => $ipAddress,
            'user_agent' => $agent,
            'detected_at' => $now,
            'location_hint' => $this->resolveLocationHint($request),
            'is_new_device' => $isNewDevice,
        ];

        try {
            Notification::send(
                $user,
                new NewDeviceLoginNotification($user, $context, false)
            );
        } catch (\Throwable $exception) {
            Log::warning('Failed sending device alert to account owner', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function invalidateSessions(User $user, ?string $exceptSessionId = null): void
    {
        $query = DB::table('sessions')->where('user_id', $user->id);

        if ($exceptSessionId !== null) {
            $query->where('id', '!=', $exceptSessionId);
        }

        $query->delete();

        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();
    }

    private function sanitizeIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        return substr($ip, 0, 45);
    }

    private function sanitizeUserAgent(?string $agent): ?string
    {
        if ($agent === null) {
            return null;
        }

        return Str::limit($agent, 500);
    }

    private function resolveDeviceLabel(?string $agent): string
    {
        $agent = $agent ?? '';
        $normalized = strtolower($agent);

        $platform = 'Perangkat Tidak Dikenal';

        if (Str::contains($normalized, 'windows')) {
            $platform = 'Windows';
        } elseif (Str::contains($normalized, 'mac os') || Str::contains($normalized, 'macintosh')) {
            $platform = 'macOS';
        } elseif (Str::contains($normalized, 'android')) {
            $platform = 'Android';
        } elseif (Str::contains($normalized, 'iphone') || Str::contains($normalized, 'ipad')) {
            $platform = 'iOS';
        } elseif (Str::contains($normalized, 'linux')) {
            $platform = 'Linux';
        }

        $browser = 'Browser tidak diketahui';

        if (Str::contains($normalized, 'chrome')) {
            $browser = 'Chrome';
        } elseif (Str::contains($normalized, 'safari') && ! Str::contains($normalized, 'chrome')) {
            $browser = 'Safari';
        } elseif (Str::contains($normalized, 'firefox')) {
            $browser = 'Firefox';
        } elseif (Str::contains($normalized, 'edg')) {
            $browser = 'Edge';
        } elseif (Str::contains($normalized, 'opera') || Str::contains($normalized, 'opr/')) {
            $browser = 'Opera';
        }

        return trim(sprintf('%s · %s', $platform, $browser));
    }

    private function resolveLocationHint(Request $request): ?string
    {
        $country = $request->headers->get('CF-IPCountry')
            ?? $request->headers->get('X-Country-Code')
            ?? null;

        if ($country) {
            return strtoupper($country);
        }

        return null;
    }

    private function generateFingerprint(Request $request): string
    {
        $segments = [
            Str::lower($request->header('User-Agent', 'unknown')),
            Str::lower($request->header('Accept-Language', '')),
            $this->sanitizeIp($request->ip()) ?? '0.0.0.0',
        ];

        return hash('sha256', implode('|', $segments));
    }

    private function shouldAlert(?Carbon $lastAlertedAt, Carbon $now): bool
    {
        if ($lastAlertedAt === null) {
            return true;
        }

        return $lastAlertedAt->diffInMinutes($now) >= self::ALERT_COOLDOWN_MINUTES;
    }
}
