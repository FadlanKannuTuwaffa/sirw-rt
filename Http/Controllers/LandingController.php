<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\Slide;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\LandingContentLocalizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __construct(private readonly LandingContentLocalizer $localizer)
    {
    }

    public function index(): View
    {
        $siteMeta = $this->siteMeta();
        $slides = Slide::active()->get();
        $upcomingEvents = Event::query()
            ->where('is_public', true)
            ->where('status', 'scheduled')
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $stats = [
            'residents' => User::residents()->count(),
            'online' => User::residents()->where('last_seen_at', '>=', now()->subMinutes(3))->count(),
            'paid_this_month' => LedgerEntry::income()->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'outstanding_bills' => Bill::where('status', '!=', 'paid')->sum('amount'),
        ];

        $news = Event::query()
            ->where('is_public', true)
            ->latest('created_at')
            ->limit(3)
            ->get();

        $dynamicTranslations = $this->localizer->buildLandingTranslations($siteMeta, $slides, $upcomingEvents, $news);
        $site = $this->localizer->translateSiteMeta($siteMeta);
        $slidesForView = $this->localizer->translateSlides($slides->map(fn ($slide) => clone $slide));
        $upcomingEventsForView = $this->localizer->translateEventCollection($upcomingEvents->map(fn ($event) => clone $event));
        $newsForView = $this->localizer->translateEventCollection($news->map(fn ($event) => clone $event));

        return view('landing.rebuilt', [
            'site' => $site,
            'slides' => $slidesForView,
            'upcomingEvents' => $upcomingEventsForView,
            'stats' => $stats,
            'news' => $newsForView,
            'dynamicTranslations' => $dynamicTranslations,
        ])
            ->with('title', $site['name'] ?? 'Beranda');
    }

    public function about(): View
    {
        $site = $this->siteMeta();
        $managers = User::query()->where('role', 'admin')->orderBy('name')->get();
        $site = $this->localizer->translateSiteMeta($site);

        return view('pages.about', compact('site', 'managers'))
            ->with('title', 'Tentang Kami');
    }

    public function agenda(): View
    {
        $siteMeta = $this->siteMeta();
        $perPage = 9;
        $focusEventId = request()->integer('event');
        $currentPage = max(request()->integer('page', 1) ?? 1, 1);

        $baseQuery = Event::query()
            ->where('is_public', true)
            ->orderBy('start_at')
            ->orderBy('id');

        if ($focusEventId) {
            $focusEvent = (clone $baseQuery)->where('id', $focusEventId)->first();
            if ($focusEvent) {
                $beforeCountQuery = Event::query()->where('is_public', true);

                if ($focusEvent->start_at === null) {
                    $beforeCountQuery
                        ->whereNull('start_at')
                        ->where('id', '<', $focusEvent->id);
                } else {
                    $beforeCountQuery->where(function ($query) use ($focusEvent) {
                        $query->whereNull('start_at')
                            ->orWhere('start_at', '<', $focusEvent->start_at)
                            ->orWhere(function ($inner) use ($focusEvent) {
                                $inner->where('start_at', $focusEvent->start_at)
                                    ->where('id', '<', $focusEvent->id);
                            });
                    });
                }

                $beforeCount = $beforeCountQuery->count();

                $currentPage = intdiv($beforeCount, $perPage) + 1;
            }
        }

        $events = (clone $baseQuery)->paginate($perPage, ['*'], 'page', $currentPage);

        $dynamicTranslations = $this->localizer->buildAgendaTranslations($siteMeta, $events);
        $site = $this->localizer->translateSiteMeta($siteMeta);
        $events = $this->localizer->translateEventPaginator($events);

        return view('pages.agenda', [
            'site' => $site,
            'events' => $events,
            'focusEventId' => $focusEventId,
            'dynamicTranslations' => $dynamicTranslations,
        ])
            ->with('title', 'Agenda Warga');
    }

    public function contact(): View
    {
        $site = $this->siteMeta();
        $site = $this->localizer->translateSiteMeta($site);

        return view('pages.contact', compact('site'))
            ->with('title', 'Kontak Pengurus');
    }

    private function siteMeta(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return [
                'name' => 'Sistem Informasi RT',
                'tagline' => 'Smart Resident Community',
                'about' => null,
                'contact_email' => null,
                'contact_phone' => null,
                'address' => null,
                'facebook' => null,
                'instagram' => null,
                'youtube' => null,
            ];
        }

        $settings = SiteSetting::keyValue()->toArray();

        return [
            'name' => Arr::get($settings, 'site_name', 'Sistem Informasi RT'),
            'tagline' => Arr::get($settings, 'tagline', 'Smart Resident Community'),
            'about' => Arr::get($settings, 'about'),
            'vision' => Arr::get($settings, 'vision'),
            'mission' => Arr::get($settings, 'mission'),
            'contact_email' => Arr::get($settings, 'contact_email'),
            'contact_phone' => Arr::get($settings, 'contact_phone'),
            'service_hours' => Arr::get($settings, 'service_hours'),
            'address' => Arr::get($settings, 'address'),
            'facebook' => Arr::get($settings, 'facebook'),
            'instagram' => Arr::get($settings, 'instagram'),
            'youtube' => Arr::get($settings, 'youtube'),
            'logo_path' => Arr::get($settings, 'logo_path'),
            'logo_initials' => Arr::get($settings, 'logo_initials', 'SR'),
        ];
    }
}

