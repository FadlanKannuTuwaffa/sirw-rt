<?php

namespace App\Http\Controllers\Livewire;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\FilePreviewController as BaseFilePreviewController;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FilePreviewController extends BaseFilePreviewController
{
    public function handle($filename)
    {
        $request = request();
        $this->synchroniseForwardedRequest($request);

        try {
            return parent::handle($filename);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 401) {
                $this->logSignatureFailure($request, $filename);
            }

            throw $exception;
        }
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

    private function logSignatureFailure(Request $request, string $filename): void
    {
        Log::warning('Livewire preview signature invalid after sync', [
            'filename' => $filename,
            'full_url' => $request->fullUrl(),
            'path' => $request->path(),
            'query' => $request->query(),
            'scheme' => $request->getScheme(),
            'host' => $request->getHost(),
            'headers' => [
                'x-forwarded-proto' => $request->headers->get('x-forwarded-proto'),
                'x-forwarded-host' => $request->headers->get('x-forwarded-host'),
                'x-forwarded-port' => $request->headers->get('x-forwarded-port'),
                'x-forwarded-for' => $request->headers->get('x-forwarded-for'),
                'origin' => $request->headers->get('origin'),
                'referer' => $request->headers->get('referer'),
            ],
        ]);
    }
}
