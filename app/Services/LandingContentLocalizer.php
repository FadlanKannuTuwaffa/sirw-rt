<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LandingContentLocalizer
{
    private const DEFAULT_LOCALE = 'id';
    private const SUPPORTED = ['id', 'en'];

    public function __construct(private readonly AutoTranslator $translator) {}

    public function locale(): string
    {
        return in_array(app()->getLocale(), self::SUPPORTED, true)
            ? app()->getLocale()
            : self::DEFAULT_LOCALE;
    }

    public function shouldTranslate(): bool
    {
        return $this->locale() !== self::DEFAULT_LOCALE;
    }

    public function translateSiteMeta(array $site): array
    {
        if (! $this->shouldTranslate()) {
            return $site;
        }

        $keys = [
            'name',
            'tagline',
            'about',
            'vision',
            'mission',
            'service_hours',
            'address',
        ];

        foreach ($keys as $key) {
            if (! empty($site[$key]) && is_string($site[$key])) {
                $site[$key] = $this->translator->translate($site[$key], $this->locale(), self::DEFAULT_LOCALE);
            }
        }

        return $site;
    }

    public function translateSlides(Collection $slides): Collection
    {
        if (! $this->shouldTranslate()) {
            return $slides;
        }

        return $slides->transform(function ($slide) {
            foreach (['title', 'subtitle', 'description', 'button_label'] as $field) {
                $value = $slide->{$field} ?? null;
                if (is_string($value) && $value !== '') {
                    $slide->{$field} = $this->translator->translate($value, $this->locale(), self::DEFAULT_LOCALE);
                }
            }

            return $slide;
        });
    }

    public function buildLandingTranslations(array $site, Collection $slides, Collection $upcomingEvents, Collection $news): array
    {
        $translations = [];

        foreach (self::SUPPORTED as $locale) {
            $entries = [];

            $brandTitle = $this->valueForLocale($site['name'] ?? null, $locale);
            if ($brandTitle !== null) {
                $entries['site.brand_title'] = $brandTitle;
            }

            $brandTagline = $this->valueForLocale($site['tagline'] ?? null, $locale);
            if ($brandTagline !== null) {
                $entries['site.brand_tagline'] = $brandTagline;
            }

            foreach ($slides as $slide) {
                $key = "landing.slides.{$slide->id}";

                $title = $this->valueForLocale($slide->title ?? null, $locale);
                if ($title !== null) {
                    $entries["{$key}.title"] = $title;
                    $indicator = Str::limit($title, 34);
                    if ($indicator !== '') {
                        $entries["{$key}.indicator"] = $indicator;
                    }
                }

                $subtitle = $this->valueForLocale($slide->subtitle ?? null, $locale);
                if ($subtitle !== null) {
                    $entries["{$key}.subtitle"] = $subtitle;
                }

                $description = $this->valueForLocale($slide->description ?? null, $locale);
                if ($description !== null) {
                    $entries["{$key}.description"] = $description;
                }

                $button = $this->valueForLocale($slide->button_label ?? null, $locale);
                if ($button !== null) {
                    $entries["{$key}.button"] = $button;
                }

                $aria = null;
                if (isset($slide->title)) {
                    $showText = $this->translator->translate('Tampilkan', $locale, self::DEFAULT_LOCALE) ?? 'Tampilkan';
                    $translatedTitle = $this->valueForLocale($slide->title, $locale);
                    if ($translatedTitle !== null) {
                        $aria = $showText . ' ' . $translatedTitle;
                    }
                }
                if ($aria !== null) {
                    $entries["{$key}.aria_label"] = $aria;
                }
            }

            foreach ($upcomingEvents as $event) {
                $key = "landing.events.{$event->id}";

                $title = $this->valueForLocale($event->title ?? null, $locale);
                if ($title !== null) {
                    $entries["{$key}.title"] = $title;
                }

                $description = $this->limitedValueForLocale($event->description ?? null, $locale, 120);
                if ($description !== null) {
                    $entries["{$key}.description"] = $description;
                }

                $location = $this->valueForLocale($event->location ?? null, $locale);
                if ($location !== null) {
                    $entries["{$key}.location"] = $location;
                }
            }

            foreach ($news as $item) {
                $key = "landing.news.{$item->id}";

                $title = $this->valueForLocale($item->title ?? null, $locale);
                if ($title !== null) {
                    $entries["{$key}.title"] = $title;
                }

                $description = $this->limitedValueForLocale($item->description ?? null, $locale, 200);
                if ($description !== null) {
                    $entries["{$key}.description"] = $description;
                }
            }

            if (! empty($entries)) {
                $translations[$locale] = $entries;
            }
        }

        return $translations;
    }

    public function translateEventCollection(Collection $events): Collection
    {
        return $events->transform(function ($event) {
            return $this->translateEventToLocale($event, $this->locale());
        });
    }

    public function translateEventPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $paginator->through(function ($event) {
            return $this->translateEventToLocale($event, $this->locale());
        });

        return $paginator;
    }

    public function buildAgendaTranslations(array $site, LengthAwarePaginator|Collection $events): array
    {
        $collection = $events instanceof LengthAwarePaginator ? $events->items() : $events;
        $translations = [];

        foreach (self::SUPPORTED as $locale) {
            $entries = [];

            $brandTitle = $this->valueForLocale($site['name'] ?? null, $locale);
            if ($brandTitle !== null) {
                $entries['site.brand_title'] = $brandTitle;
            }

            $brandTagline = $this->valueForLocale($site['tagline'] ?? null, $locale);
            if ($brandTagline !== null) {
                $entries['site.brand_tagline'] = $brandTagline;
            }

            foreach ($collection as $event) {
                $key = "agenda.events.{$event->id}";

                $title = $this->valueForLocale($event->title ?? null, $locale);
                if ($title !== null) {
                    $entries["{$key}.title"] = $title;
                }

                $description = $this->limitedValueForLocale($event->description ?? null, $locale, 180);
                if ($description !== null) {
                    $entries["{$key}.description"] = $description;
                }

                $location = $this->valueForLocale($event->location ?? null, $locale);
                if ($location !== null) {
                    $entries["{$key}.location"] = $location;
                }

                $status = $this->localizedStatus($event->status, $locale);
                if ($status !== null) {
                    $entries["{$key}.status"] = $status;
                }
            }

            if (! empty($entries)) {
                $translations[$locale] = $entries;
            }
        }

        return $translations;
    }

    private function translateEventToLocale(Event $event, string $locale): Event
    {
        foreach (['title', 'description', 'location'] as $attribute) {
            $value = $event->{$attribute};
            if (is_string($value) && $value !== '') {
                $translated = $this->translator->translate($value, $locale, self::DEFAULT_LOCALE);
                $event->{$attribute} = $translated ?? $value;
            }
        }

        if (isset($event->status)) {
            $event->status = $this->localizedStatus($event->status, $locale);
        }

        return $event;
    }

    private function localizedStatus(?string $status, ?string $localeOverride = null): ?string
    {
        if (! $status) {
            return $status;
        }

        $statusKey = Str::snake($status);

        $mapping = [
            'scheduled' => [
                'en' => 'Scheduled',
                'id' => 'Terjadwal',
            ],
            'completed' => [
                'en' => 'Completed',
                'id' => 'Selesai',
            ],
            'cancelled' => [
                'en' => 'Cancelled',
                'id' => 'Dibatalkan',
            ],
        ];

        $locale = $localeOverride ?? $this->locale();

        if (isset($mapping[$statusKey][$locale])) {
            return $mapping[$statusKey][$locale];
        }

        $translated = $this->translator->translate(Str::headline($statusKey), $locale, self::DEFAULT_LOCALE);

        return $translated ?? Str::headline($statusKey);
    }

    private function valueForLocale(?string $text, string $locale): ?string
    {
        if (! is_string($text)) {
            return null;
        }

        $trimmed = trim($text);
        if ($trimmed === '') {
            return null;
        }

        $translated = $this->translator->translate($trimmed, $locale, self::DEFAULT_LOCALE);

        return $translated ?? $trimmed;
    }

    private function limitedValueForLocale(?string $text, string $locale, int $limit): ?string
    {
        $translated = $this->valueForLocale($text, $locale);

        if ($translated === null) {
            return null;
        }

        return Str::limit($translated, $limit);
    }
}
