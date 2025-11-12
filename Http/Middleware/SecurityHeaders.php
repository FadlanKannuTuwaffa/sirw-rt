<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = $this->generateNonce();
        app()->instance('cspNonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $this->applyStandardHeaders($request, $response);
        $this->applyContentSecurityPolicy($response, $nonce);

        return $response;
    }

    private function applyStandardHeaders(Request $request, Response $response): void
    {
        $response->headers->set('X-Frame-Options', 'DENY', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none', false);
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin', false);
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin', false);
        $response->headers->set('Permissions-Policy', config('security.permissions_policy'), false);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload', false);
        }
    }

    private function applyContentSecurityPolicy(Response $response, ?string $nonce = null): void
    {
        if (! config('security.csp.enabled', true)) {
            return;
        }

        $directives = config('security.csp.directives', []);

        if (empty($directives)) {
            return;
        }

        $segments = [];

        foreach ($directives as $directive => $sources) {
            if (empty($sources)) {
                continue;
            }

            $resolvedSources = [];

            foreach ($sources as $source) {
                if ($nonce) {
                    $resolved = str_replace('{nonce}', $nonce, $source);

                    if (str_contains($resolved, '{nonce}')) {
                        // nonce placeholder without value, skip this source
                        continue;
                    }

                    $resolvedSources[] = $resolved;
                    continue;
                }

                if (str_contains($source, '{nonce}')) {
                    continue;
                }

                $resolvedSources[] = $source;
            }

            if (empty($resolvedSources)) {
                continue;
            }

            $segments[] = trim($directive . ' ' . implode(' ', array_unique($resolvedSources)));
        }

        if (! empty($segments)) {
            $response->headers->set('Content-Security-Policy', implode('; ', $segments), false);
        }
    }

    private function generateNonce(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }
}
