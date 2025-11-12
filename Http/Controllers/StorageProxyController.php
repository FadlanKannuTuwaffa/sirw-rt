<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageProxyController
{
    public function __invoke(Request $request, string $path)
    {
        $this->synchroniseForwardedRequest($request);

        $absoluteValid = $request->hasValidSignature();
        $relativeValid = $request->hasValidSignature(false);

        if (! ($absoluteValid || $relativeValid)) {
            if (! Auth::check()) {
                abort(403);
            }

            Log::warning('Storage proxy served without valid signature for authenticated user', [
                'path' => $path,
                'full_url' => $request->fullUrl(),
                'headers' => [
                    'x-forwarded-proto' => $request->headers->get('x-forwarded-proto'),
                    'x-forwarded-host' => $request->headers->get('x-forwarded-host'),
                ],
            ]);
        }

        $path = str_replace('..', '', $path);

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    private function synchroniseForwardedRequest(Request $request): void
    {
        $proto = $request->headers->get('x-forwarded-proto');
        if ($proto && $proto !== $request->getScheme()) {
            $request->server->set('HTTPS', $proto === 'https' ? 'on' : null);
            $request->server->set('REQUEST_SCHEME', $proto);
            $request->server->set('SERVER_PORT', $proto === 'https' ? 443 : 80);
        }

        if ($host = $request->headers->get('x-forwarded-host')) {
            $request->server->set('HTTP_HOST', $host);
            $request->server->set('SERVER_NAME', $host);
        }
    }
}
