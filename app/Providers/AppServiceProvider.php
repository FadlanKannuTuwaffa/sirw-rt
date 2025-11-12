<?php

namespace App\Providers;

use App\Http\Middleware\ResolveAssistantThread;
use App\Models\KbChunk;
use App\Models\SiteSetting;
use App\Services\Assistant\AssistantMetrics;
use App\Services\Assistant\ClassifierService;
use App\Services\Assistant\ComplexMultiIntentHandler;
use App\Services\Assistant\CohereClient;
use App\Services\Assistant\DummyClient;
use App\Services\Assistant\FallbackLLMClient;
use App\Services\Assistant\GeminiClient;
use App\Services\Assistant\GroqClient;
use App\Services\Assistant\HuggingFaceClient;
use App\Services\Assistant\Intent\ExternalIntentClient;
use App\Services\Assistant\LangDBClient;
use App\Services\Assistant\LLMClient;
use App\Services\Assistant\MLIntentClassifier;
use App\Services\Assistant\MistralClient;
use App\Services\Assistant\Ner\NerService;
use App\Services\Assistant\OpenRouterClient;
use App\Support\Assistant\ProviderManager;
use App\Support\Assistant\TemporalInterpreter;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Telegram\TelegramSettings;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Observers\KbChunkObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Livewire\Features\SupportFileUploads\FileUploadController::class,
            \App\Http\Controllers\Livewire\FileUploadController::class
        );

        $this->app->bind(
            \Livewire\Features\SupportFileUploads\FilePreviewController::class,
            \App\Http\Controllers\Livewire\FilePreviewController::class
        );

        $this->app->singleton(ClassifierService::class, function ($app) {
            $config = config('assistant.classifier', []);

            $mlEndpoint = data_get($config, 'ml.endpoint');
            $mlClient = $mlEndpoint
                ? new ExternalIntentClient(
                    $mlEndpoint,
                    data_get($config, 'ml.token'),
                    (float) (data_get($config, 'ml.timeout') ?? 3.0),
                    'ml'
                )
                : null;

            $llmEndpoint = data_get($config, 'llm.endpoint');
            $llmPayload = [];
            if ($model = data_get($config, 'llm.model')) {
                $llmPayload['model'] = $model;
            }

            $llmClient = $llmEndpoint
                ? new ExternalIntentClient(
                    $llmEndpoint,
                    data_get($config, 'llm.token'),
                    (float) (data_get($config, 'llm.timeout') ?? 6.0),
                    'llm',
                    $llmPayload
                )
                : null;

            $ner = new NerService(
                data_get($config, 'ner.duckling_endpoint'),
                (float) (data_get($config, 'ner.duckling_timeout') ?? 2.5)
            );

            return new ClassifierService(
                $app->make(TemporalInterpreter::class),
                $app->make(AssistantMetrics::class),
                $mlClient,
                $llmClient,
                $ner,
                $app->make(MLIntentClassifier::class),
                $app->make(ComplexMultiIntentHandler::class)
            );
        });

        $this->app->singleton(LLMClient::class, function ($app) {
            $definitions = [
                'groq' => [GroqClient::class, 'services.groq.api_key', 'Groq'],
                'gemini' => [GeminiClient::class, 'services.gemini.api_key', 'Gemini'],
                'openrouter' => [OpenRouterClient::class, 'services.openrouter.api_key', 'OpenRouter'],
                'mistral' => [MistralClient::class, 'services.mistral.api_key', 'Mistral'],
                'huggingface' => [HuggingFaceClient::class, 'services.huggingface.api_key', 'HuggingFace'],
                'cohere' => [CohereClient::class, 'services.cohere.api_key', 'Cohere'],
                'langdb' => [LangDBClient::class, 'services.langdb.api_key', 'LangDB'],
            ];

            $forcedClient = Str::lower((string) env('ASSISTANT_CLIENT'));

            if ($forcedClient !== '') {
                if (!array_key_exists($forcedClient, $definitions)) {
                    throw new \RuntimeException("Unsupported ASSISTANT_CLIENT value: {$forcedClient}");
                }

                [$class, $configKey, $name] = $definitions[$forcedClient];
                $states = ProviderManager::stateMap();
                if (!($states[$name] ?? true)) {
                    throw new \RuntimeException("{$name} sedang dinonaktifkan oleh admin. Aktifkan kembali sebelum memaksa penggunaan.");
                }
                return $this->resolveClient($app, $class, $configKey, $name);
            }

            $clients = [];
            $states = ProviderManager::stateMap();

            foreach ($definitions as [$class, $configKey, $name]) {
                if (!($states[$name] ?? true)) {
                    continue;
                }

                if (config($configKey)) {
                    $clients[] = $app->make($class);
                }
            }

            $fallback = $app->make(DummyClient::class);

            if ($clients === []) {
                return $fallback;
            }

            return new FallbackLLMClient($clients, $fallback);
        });
    }

    private function resolveClient($app, string $class, string $configKey, string $serviceName): LLMClient
    {
        if (!config($configKey)) {
            throw new \RuntimeException("{$serviceName} API key is not configured but ASSISTANT_CLIENT={$serviceName} was requested.");
        }

        return $app->make($class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureApplicationLocale();

        $this->app->bind('assistant.thread', function ($app) {
            return $app->make(ResolveAssistantThread::class);
        });

        if (config('rag.enabled') && Schema::hasTable('kb_chunks')) {
            KbChunk::observe($this->app->make(KbChunkObserver::class));
        }

        if (! Schema::hasTable('site_settings')) {
            $this->forceHttpsSchemeIfNecessary();
            return;
        }

        $this->bootMailSettingsFromDatabase();
        $this->bootTelegramSettingsFromDatabase();
        $this->forceHttpsSchemeIfNecessary();

        $paymentSettings = SiteSetting::keyValue('payment');

        if ($paymentSettings->isNotEmpty()) {
            Config::set('services.payment.provider', $paymentSettings->get('payment_provider', 'manual'));
            Config::set('services.payment.callback_url', $paymentSettings->get('payment_callback_url'));
            Config::set('services.tripay.api_key', $paymentSettings->get('tripay_api_key'));
            Config::set('services.tripay.private_key', $paymentSettings->get('tripay_private_key'));
            Config::set('services.tripay.merchant_code', $paymentSettings->get('tripay_merchant_code'));
            Config::set('services.tripay.channel', PaymentGatewayManager::normalizeTripayChannel($paymentSettings->get('tripay_channel')));
            Config::set('services.tripay.channels', PaymentGatewayManager::normalizeTripayChannels($paymentSettings->get('tripay_channels')));
            Config::set('services.tripay.mode', $paymentSettings->get('tripay_mode', 'sandbox'));
            Config::set('services.tripay.callback_url', $paymentSettings->get('payment_callback_url'));
            Config::set('services.tripay.fee_percent', (float) $paymentSettings->get('tripay_fee_percent', Config::get('services.tripay.fee_percent', 0)));
            Config::set('services.tripay.fee_flat', (float) $paymentSettings->get('tripay_fee_flat', Config::get('services.tripay.fee_flat', 0)));
            Config::set('services.tripay.min_fee', (float) $paymentSettings->get('tripay_min_fee', Config::get('services.tripay.min_fee', 0)));
            Config::set('services.tripay.channel_fees', PaymentGatewayManager::normalizeTripayChannelFees($paymentSettings->get('tripay_channel_fees')));
        }

        $paymentManager = PaymentGatewayManager::resolve();
        Config::set('services.payment.manual_destinations', $paymentManager->manualDestinations());

        $telegramSettings = app(TelegramSettings::class)->toArray();
        $telegramKeys = [
            'bot_token',
            'webhook_url',
            'webhook_secret',
            'default_language',
            'contact_email',
            'contact_whatsapp',
        ];

        foreach ($telegramKeys as $key) {
            if (array_key_exists($key, $telegramSettings) && $telegramSettings[$key] !== null) {
                Config::set('services.telegram.' . $key, $telegramSettings[$key]);
            }
        }
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $identifier = Str::lower((string) $request->input('identifier', ''));
            $ip = (string) $request->ip();

            return Limit::perMinute(5)->by(hash('sha256', $identifier . '|' . $ip));
        });
    }

    protected function configureApplicationLocale(): void
    {
        $locale = Config::get('app.locale', 'id');
        App::setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        $phpLocales = match ($locale) {
            'id' => ['id_ID.UTF-8', 'id_ID', 'id', 'IND'],
            'en' => ['en_US.UTF-8', 'en_US', 'en', 'ENG'],
            default => [$locale . '.UTF-8', $locale],
        };

        // Attempt to configure locale for strftime/Intl functions
        @setlocale(LC_TIME, ...$phpLocales);
    }

    private function bootMailSettingsFromDatabase(): void
    {
        $smtp = SiteSetting::keyValue('smtp')->toArray();
        $defaults = [
            'mailer' => Config::get('mail.default', 'smtp'),
            'host' => Config::get('mail.mailers.smtp.host'),
            'port' => Config::get('mail.mailers.smtp.port', 587),
            'username' => Config::get('mail.mailers.smtp.username'),
            'password' => Config::get('mail.mailers.smtp.password'),
            'encryption' => Config::get('mail.mailers.smtp.encryption', 'tls'),
            'timeout' => Config::get('mail.mailers.smtp.timeout', 30),
            'from_address' => Config::get('mail.from.address'),
            'from_name' => Config::get('mail.from.name'),
        ];

        Config::set('mail.default', Arr::get($smtp, 'mailer', $defaults['mailer']) ?: $defaults['mailer']);
        Config::set('mail.mailers.smtp.transport', 'smtp');

        $host = Arr::get($smtp, 'host');
        Config::set('mail.mailers.smtp.host', filled($host) ? $host : $defaults['host']);

        $port = Arr::get($smtp, 'port');
        Config::set('mail.mailers.smtp.port', $port !== null ? (int) $port : (int) $defaults['port']);

        $username = Arr::get($smtp, 'username');
        Config::set('mail.mailers.smtp.username', filled($username) ? $username : $defaults['username']);

        $password = Arr::get($smtp, 'password');
        Config::set('mail.mailers.smtp.password', filled($password) ? $password : $defaults['password']);

        $encryption = Arr::get($smtp, 'encryption');
        Config::set('mail.mailers.smtp.encryption', filled($encryption) ? $encryption : $defaults['encryption']);

        $timeout = Arr::get($smtp, 'timeout');
        Config::set('mail.mailers.smtp.timeout', $timeout !== null ? (int) $timeout : (int) $defaults['timeout']);

        $fromAddress = Arr::get($smtp, 'from_address');
        Config::set('mail.from.address', filled($fromAddress) ? $fromAddress : $defaults['from_address']);

        $fromName = Arr::get($smtp, 'from_name');
        Config::set('mail.from.name', filled($fromName) ? $fromName : $defaults['from_name']);
    }

    private function bootTelegramSettingsFromDatabase(): void
    {
        $tg = SiteSetting::keyValue('telegram')->toArray();
        Config::set('services.telegram.bot_token', Arr::get($tg, 'bot_token', Config::get('services.telegram.bot_token')));
        Config::set('services.telegram.webhook_url', Arr::get($tg, 'webhook_url', Config::get('services.telegram.webhook_url')));
        Config::set('services.telegram.webhook_secret', Arr::get($tg, 'webhook_secret', Config::get('services.telegram.webhook_secret')));
    }

    private function forceHttpsSchemeIfNecessary(): void
    {
        $appUrl = Config::get('app.url');

        if (! $appUrl) {
            return;
        }

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
        }
    }
}
