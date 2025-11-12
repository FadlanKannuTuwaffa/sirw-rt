<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Binding disediakan oleh AppServiceProvider melalui FallbackLLMClient.
    }
}
