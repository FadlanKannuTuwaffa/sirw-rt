<div class="font-['Inter'] text-slate-700 dark:text-slate-200" data-resident-stack>
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/70 bg-white/95 p-2 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-slider-root data-slider-interval="7000" data-resident-fade data-motion-animated>
        @if ($slides->isEmpty())
            <div class="flex min-h-[14rem] items-center justify-center text-sm text-slate-500 dark:text-slate-400">
                {{ __('resident.dashboard.no_info_slides') }}
            </div>
        @else
            <div class="relative h-[18rem] w-full overflow-hidden rounded-[22px] sm:h-[21rem] lg:h-[23rem]" data-slider-track data-slider-interval="7000">
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $slides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                @php
                    $summaryParts = collect([
                        $slide->title,
                        $slide->subtitle,
                        $slide->description ? Str::limit($slide->description, 140) : null,
                    ])->filter()->all();
                    $summaryText = implode(' — ', $summaryParts);
                @endphp
                <article
                    data-slider-slide
                    data-index="<?php echo e($loop->index); ?>"
                    data-active="<?php echo e($loop->first ? 'true' : 'false'); ?>"
                    class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'pointer-events-none absolute inset-0 flex h-full w-full flex-col overflow-hidden rounded-[22px] transition-opacity duration-500 ease-out',
                        'opacity-100 pointer-events-auto z-20' => $loop->first,
                        'opacity-0 translate-x-3 z-10' => ! $loop->first,
                    ]); ?>"
                    aria-hidden="<?php echo e($loop->first ? 'false' : 'true'); ?>"
                >
                    <div class="sr-only" data-slider-slide-summary>{{ $summaryText }}</div>
                    <div class="absolute inset-0 overflow-hidden rounded-[22px]" data-slider-media>
                        <div class="absolute inset-0 bg-slate-900/55 transition-opacity duration-500 dark:bg-slate-950/65" data-slider-overlay></div>
                        <!--[if BLOCK]><![endif]--><?php if($slide->image_path):
                            ?><img src="<?php echo e(asset('storage/'.$slide->image_path)); ?>" alt="<?php echo e($slide->title); ?>" class="absolute inset-0 h-full w-full object-cover opacity-25 blur-xl">
                            <div class="absolute inset-0 flex items-center justify-center p-3 sm:p-6">
                                <img src="<?php echo e(asset('storage/'.$slide->image_path)); ?>" alt="<?php echo e($slide->title); ?>" class="h-full w-full max-h-full max-w-full object-contain">
                            </div>
                        <?php else:
                            ?><div class="absolute inset-0 flex items-center justify-center bg-slate-800/80 text-sm font-semibold text-white/80">
                                <?php echo e(Str::limit($slide->title, 42)); ?> 

                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                    <div class="relative z-10 mt-auto flex flex-col gap-3 p-6 sm:p-7 text-white" data-slider-copy>
                        <!--[if BLOCK]><![endif]--><?php if($slide->subtitle):
                            ?><p class="text-xs font-medium text-white/80" data-slider-subtitle><?php echo e($slide->subtitle); ?></p>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        <h2 class="text-2xl font-semibold leading-tight" data-slider-title><?php echo e($slide->title); ?></h2>
                        <!--[if BLOCK]><![endif]--><?php if($slide->description):
                            ?><p class="text-sm text-white/85 drop-shadow-[0_6px_18px_rgba(15,23,42,0.45)]" data-slider-description><?php echo e(Str::limit($slide->description, 200)); ?></p>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        <!--[if BLOCK]><![endif]--><?php if($slide->button_label && $slide->button_url):
                            ?><!-- TODO: Add sharing links here -->
                            <a></a>
                                <?php echo e($slide->button_label); ?> 

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-3.5 w-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                                </svg>
                            </a>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
            <div class="pointer-events-none absolute inset-x-0 top-4 flex justify-between px-6">
                <button type="button" data-slider-prev class="pointer-events-auto inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-white/15 text-white shadow-sm backdrop-blur transition-colors duration-200 hover:bg-white/25 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent dark:focus-visible:ring-slate-200/50 dark:focus-visible:ring-offset-slate-900 dark:border-slate-700/60 dark:bg-slate-900/60 dark:hover:bg-slate-800/70">
                    <span class="sr-only">{{ __('resident.dashboard.previous_slide') }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4.5 w-4.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7" />
                    </svg>
                </button>
                <button type="button" data-slider-next class="pointer-events-auto inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-white/15 text-white shadow-sm backdrop-blur transition-colors duration-200 hover:bg-white/25 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent dark:focus-visible:ring-slate-200/50 dark:focus-visible:ring-offset-slate-900 dark:border-slate-700/60 dark:bg-slate-900/60 dark:hover:bg-slate-800/70">
                    <span class="sr-only">{{ __('resident.dashboard.next_slide') }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4.5 w-4.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
            <div class="sr-only" data-slider-announcer aria-live="polite"></div>
            <div class="pointer-events-none absolute inset-x-6 bottom-6 flex items-center justify-between gap-3">
                <button
                    type="button"
                    data-slider-toggle
                    data-slider-pause-label="{{ __('resident.dashboard.pause') }}"
                    data-slider-play-label="{{ __('resident.dashboard.play') }}"
                    class="pointer-events-auto inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/15 px-4 py-2 text-xs font-semibold text-white shadow-sm backdrop-blur transition-colors duration-200 hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent dark:border-slate-700/60 dark:bg-slate-900/60 dark:hover:bg-slate-800/70"
                    aria-pressed="false"
                    aria-label="{{ __('resident.dashboard.pause_slider') }}"
                >
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4" data-slider-icon="pause">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 6.75v10.5M14.25 6.75v10.5" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="hidden h-4 w-4" data-slider-icon="play">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 5.75 8.5 6.25-8.5 6.25z" />
                        </svg>
                        <span data-slider-toggle-text>{{ __('resident.dashboard.pause') }}</span>
                    </span>
                </button>
                <div class="pointer-events-none flex items-center gap-2">
                @foreach ($slides as $slide)
                    <span class="pointer-events-auto h-2 w-8 rounded-full bg-white/20 transition-opacity duration-300" data-slider-indicator data-index="{{ $loop->index }}">
                        <span class="sr-only">{{ __('resident.dashboard.select_slide') . ' ' . $loop->iteration }}</span>
                        <span class="block h-full origin-left rounded-full bg-white/70 opacity-0 transition-[opacity,width] duration-300" data-slider-progress style="width: 0%;"></span>
                    </span>
                @endforeach
                </div>
            </div>
            <details class="mt-4 rounded-2xl border border-white/30 bg-white/40 p-4 text-xs text-slate-700 shadow-sm backdrop-blur-sm transition-colors duration-300 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-300 sm:text-sm">
                <summary class="cursor-pointer font-semibold text-slate-600 dark:text-slate-200">{{ __('resident.dashboard.slider_content_summary') }}</summary>
                <ul class="mt-2 space-y-1 list-disc pl-4 marker:text-slate-400 dark:marker:text-slate-500">
                    @foreach ($slides as $slide)
                        @php
                            $summaryParts = collect([
                                $slide->title,
                                $slide->subtitle,
                                $slide->description ? Str::limit($slide->description, 140) : null,
                            ])->filter()->all();
                        @endphp
                        <li>{{ implode(' — ', $summaryParts) }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </section>

    @php
        $languagePreference = $languagePreference ?? 'id';
        $syncLocale = $languagePreference === 'en' ? 'en' : 'id';
        $syncTime = now()->copy()->locale($syncLocale);
        $statSyncedLabel = __('resident.dashboard.updated_at', ['time' => $syncTime->translatedFormat('H:i')]);

        $statCards = [
            [
                'label' => __('resident.dashboard.active_bills'),
                'value' => number_format($stats['outstanding']),
                'format' => 'currency',
                'tone' => 'info',
                'variant' => 'muted',
                'value_class' => 'text-[#0284C7] dark:text-sky-300',
                'caption' => __('resident.dashboard.amount_awaiting_settlement'),
            ],
            [
                'label' => __('resident.dashboard.payments_this_month'),
                'value' => number_format($stats['paid_this_month']),
                'format' => 'currency',
                'tone' => 'success',
                'variant' => 'muted',
                'value_class' => 'text-[#22C55E] dark:text-emerald-300',
                'caption' => __('resident.dashboard.total_collected_this_month'),
            ],
            [
                'label' => __('resident.dashboard.total_bills'),
                'value' => number_format($stats['total_bills']),
                'format' => 'count',
                'tone' => 'neutral',
                'variant' => 'muted',
                'value_class' => 'text-slate-900 dark:text-slate-100',
                'caption' => __('resident.dashboard.bills_published_so_far'),
            ],
            [
                'label' => __('resident.dashboard.bills_settled'),
                'value' => number_format($stats['paid_bills']),
                'format' => 'count',
                'tone' => 'neutral',
                'variant' => 'muted',
                'value_class' => 'text-slate-900 dark:text-slate-100',
                'caption' => __('resident.dashboard.bills_marked_as_paid'),
            ],
        ];
    @endphp

    <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-4" data-resident-fade="delayed" data-motion-animated>
        @foreach ($statCards as $card)
            <article class="flex h-full flex-col gap-4 p-5" data-resident-card data-variant="{{ $card['variant'] }}" data-motion-card>
                <div class="flex items-center gap-3">
                    <span data-resident-chip data-tone="{{ $card['tone'] }}">{{ $card['label'] }}</span>
                    <span class="ml-auto text-xs text-slate-400/90 dark:text-slate-500">{{ $statSyncedLabel }}</span>
                </div>
                <p class="text-3xl font-semibold {{ $card['value_class'] }}">
                    @if ($card['format'] === 'currency')
                        {{ __('resident.dashboard.currency_rp') }}{{ $card['value'] }}
                    @else
                        {{ $card['value'] }}
                    @endif
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $card['caption'] }}</p>
            </article>
        @endforeach
    </section>

    @if (!empty($insights))
        <section data-resident-card data-variant="muted" class="mt-6 space-y-4 p-6" data-resident-fade="later" data-motion-card>
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2>{{ __('resident.dashboard.financial_insights') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.automated_predictions') }}</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 transition-colors duration-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                    {{ __('resident.dashboard.real_time') }}
                </span>
            </header>
            <div class="grid gap-4 lg:grid-cols-3" data-resident-insights>
                @foreach ($insights as $insight)
                    <article class="flex h-full flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white/95 p-5 shadow-sm transition-colors duration-200 dark:border-slate-700/60 dark:bg-slate-900/80" data-resident-insight>
                        <div class="flex items-center gap-3">
                            <span data-resident-chip data-tone="{{ $insight['tone'] ?? 'info' }}">{{ $insight['type'] ?? 'Insight' }}</span>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $insight['title'] }}</h3>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $insight['summary'] }}</p>
                        @if (!empty($insight['detail']))
                            <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $insight['detail'] }}</p>
                        @endif
                        @if (!empty($insight['actions']))
                            <div class="mt-auto flex flex-wrap gap-2 pt-2">
                                @foreach ($insight['actions'] as $action)
                                    <a
                                        href="{{ $action['href'] ?? '#' }}"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-xs font-semibold text-sky-600 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-sky-200 dark:hover:border-sky-500"
                                    >
                                        {{ $action['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid gap-6 lg:grid-cols-2" data-resident-fade="later" data-motion-animated>
        <div data-resident-card data-variant="muted" class="flex h-full flex-col gap-6 p-6" data-motion-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2>{{ __('resident.dashboard.outstanding_bills') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.sorted_by_priority') }}</p>
                </div>
                <a href="{{ route('resident.bills') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-sky-600 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-sky-200 dark:hover:border-sky-500">
                    {{ __('resident.dashboard.manage_bills') }}
                </a>
            </div>
            <div class="space-y-3">
                @forelse ($outstandingBills as $bill)
                    <article class="group flex flex-col gap-2 rounded-2xl border border-slate-200/70 bg-white px-5 py-4 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700/60 dark:bg-slate-900/70 dark:hover:border-sky-500/40">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $bill->title }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.due_date') }} {{ $bill->due_date?->translatedFormat('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="text-right text-sm font-semibold text-[#0284C7] dark:text-sky-300">
                                {{ __('resident.dashboard.currency_rp') }}{{ number_format($bill->payable_amount) }}
                                @if ($bill->gateway_fee > 0)
                                    <p class="text-[11px] font-normal text-slate-400 dark:text-slate-500">&bull; {{ __('resident.dashboard.admin_fee') }}: {{ __('resident.dashboard.currency_rp') }}{{ number_format($bill->gateway_fee) }}</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white/90 p-6 text-center text-xs font-medium text-slate-400 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-500">
                        {{ __('resident.dashboard.all_bills_settled') }}
                    </div>
                @endforelse
            </div>
        </div>
        <div data-resident-card data-variant="muted" class="flex h-full flex-col gap-6 p-6" data-motion-card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.dashboard.recent_payments') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.latest_confirmations') }}</p>
                </div>
                <a href="{{ route('resident.bills') }}#riwayat" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-emerald-600 transition-colors duration-200 hover:border-emerald-200 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-emerald-200 dark:hover:border-emerald-400">
                    {{ __('resident.dashboard.full_history') }}
                </a>
            </div>
            <div class="space-y-3">
                @forelse ($recentPayments as $payment)
                    <article class="rounded-2xl border border-slate-200/70 bg-white px-5 py-4 text-sm text-slate-600 transition-colors duration-200 hover:border-emerald-300/60 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-emerald-400/40">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $payment->bill?->title }}</p>
                            <span>{{ __('resident.dashboard.paid') }}</span>
                        </div>
                        <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-300">{{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->customer_total ?? ($payment->amount + $payment->fee_amount)) }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            {{ __('resident.dashboard.bill') }}: {{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->amount) }}
                            @if ($payment->fee_amount > 0)
                                &bull; {{ __('resident.dashboard.admin_fee') }}: {{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->fee_amount) }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</p>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-emerald-300/40 bg-white/90 p-6 text-center text-xs font-medium text-slate-400 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-500">
                        {{ __('resident.dashboard.no_payments_yet') }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section data-resident-card data-variant="muted" class="p-6" data-resident-fade="later" data-motion-card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2>{{ __('resident.dashboard.upcoming_agenda') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.click_to_see_details') }}</p>
            </div>
            <button type="button" wire:click="openAgendaPanel" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-sky-600 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-sky-200 dark:hover:border-sky-500">
                {{ __('resident.dashboard.view_details') }}
            </button>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @forelse ($upcomingEvents as $event)
                @php
                    $statusMap = [
                        'going' => ['label' => __('resident.dashboard.attending'), 'tone' => 'success'],
                        'maybe' => ['label' => __('resident.dashboard.considering'), 'tone' => 'info'],
                        'declined' => ['label' => __('resident.dashboard.not_attending'), 'tone' => 'danger'],
                        'pending' => ['label' => __('resident.dashboard.not_confirmed'), 'tone' => 'neutral'],
                    ];
                    $currentStatus = $rsvpStatuses[$event->id] ?? 'pending';
                    $statusMeta = $statusMap[$currentStatus] ?? $statusMap['pending'];
                    $startDisplay = $event->is_all_day
                        ? $event->start_at?->translatedFormat('l, d M Y')
                        : $event->start_at?->translatedFormat('l, d M Y H:i');
                    $endDisplay = $event->end_at
                        ? ($event->is_all_day
                            ? $event->end_at->translatedFormat('l, d M Y')
                            : $event->end_at->translatedFormat('l, d M Y H:i'))
                        : null;
                    $timeRange = $endDisplay ? $startDisplay . ' - ' . $endDisplay : $startDisplay;
                    $locationText = $event->location ?: __('resident.dashboard.location_will_be_announced');
                    $shareMessage = __('resident.dashboard.bill') . ": {$event->title}\\n"
                        . __('resident.dashboard.time') . ": {$timeRange}\\n"
                        . __('resident.dashboard.location') . ": {$locationText}";
                    $whatsappLink = 'https://wa.me/?text=' . rawurlencode($shareMessage);
                    $telegramLink = 'https://t.me/share/url?text=' . rawurlencode($shareMessage);
                    $icsLink = route('resident.events.ics', $event);
                @endphp
                <article
                    class="cursor-pointer rounded-2xl border border-slate-200/70 bg-white px-5 py-4 transition-colors duration-200 hover:border-sky-200 dark:border-slate-700/60 dark:bg-slate-900/70 dark:hover:border-sky-500/40"
                    wire:click="viewEvent({{ $event->id }})"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $timeRange }}</p>
                            <h3 class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">{{ $event->title }}</h3>
                            <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ Str::limit($event->description, 140) }}</p>
                        </div>
                        <span data-resident-chip data-tone="{{ $statusMeta['tone'] }}">{{ $statusMeta['label'] }}</span>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 text-[11px] font-semibold">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ __('resident.dashboard.rsvp') }}</span>
                            @foreach ([
                                
                                App\Models\EventAttendance::STATUS_GOING => __('resident.dashboard.attend'), 
                                
                                App\Models\EventAttendance::STATUS_MAYBE => __('resident.dashboard.maybe'), 
                                
                                App\Models\EventAttendance::STATUS_DECLINED => __('resident.dashboard.decline')
                            
                            ] as $statusKey => $label)
                                @php
                                    $isActive = $currentStatus === $statusKey;
                                @endphp
                                <button
                                    type="button"
                                    wire:click.stop="setRsvpStatus({{ $event->id }}, '{{ $statusKey }}' )"
                                    wire:loading.attr="disabled"
                                    wire:target="setRsvpStatus"
                                    class="{{ $isActive
                                        ? 'inline-flex items-center gap-2 rounded-full border border-sky-300 bg-sky-100/80 px-3 py-1.5 text-sky-700 transition-colors duration-200 dark:border-sky-500/60 dark:bg-sky-500/15 dark:text-sky-200'
                                        : 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-500 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-sky-500 dark:hover:text-sky-200' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                            <button
                                type="button"
                                wire:click.stop="setRsvpStatus({{ $event->id }}, '{{ 
                                App\Models\EventAttendance::STATUS_PENDING 
                                }}' )"
                                wire:loading.attr="disabled"
                                wire:target="setRsvpStatus"
                                class="inline-flex items-center gap-2 rounded-full border border-transparent bg-slate-100 px-3 py-1.5 text-slate-400 transition-colors duration-200 hover:bg-slate-200 hover:text-slate-600 dark:bg-slate-800 dark:text-slate-500 dark:hover:bg-slate-700"
                            >
                                {{ __('resident.dashboard.reset') }}
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ __('resident.dashboard.share_via') }}</span>
                            <a
                                href="{{ $whatsappLink }}"
                                onclick="event.stopPropagation();"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-emerald-600 transition-colors duration-200 hover:border-emerald-300 hover:text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200"
                            >
                                {{ __('resident.dashboard.whatsapp') }}
                            </a>
                            <a
                                href="{{ $telegramLink }}"
                                onclick="event.stopPropagation();"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-sky-600 transition-colors duration-200 hover:border-sky-300 hover:text-sky-700 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200"
                            >
                                {{ __('resident.dashboard.telegram') }}
                            </a>
                            <a
                                href="{{ $icsLink }}"
                                onclick="event.stopPropagation();"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-500 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-sky-500 dark:hover:text-sky-200"
                            >
                                {{ __('resident.dashboard.download_ics') }}
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white/90 p-6 text-center text-xs font-medium text-slate-400 md:col-span-2 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-500">
                    {{ __('resident.dashboard.no_upcoming_agenda') }}
                </div>
            @endforelse
        </div>
    </section>

    @if ($showAgendaPanel)
        <div wire:key="agenda-modal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/45 backdrop-blur-sm px-4 py-8">
            <button type="button" class="absolute inset-0 h-full w-full cursor-pointer" wire:click="closeAgendaPanel" aria-label="{{ __('resident.dashboard.close_agenda_details') }}"></button>
            <div class="relative z-10 w-full max-w-xl" data-resident-card data-variant="muted">
                <div class="flex items-start justify-between gap-4 p-6">
                    <div>
                        <span data-resident-chip data-tone="info">{{ __('resident.dashboard.agenda_details') }}</span>
                        @if ($selectedEvent)
                            @php
                    $statusMap = [
                        'going' => ['label' => __('resident.dashboard.attending'), 'tone' => 'success'],
                        'maybe' => ['label' => __('resident.dashboard.considering'), 'tone' => 'info'],
                        'declined' => ['label' => __('resident.dashboard.not_attending'), 'tone' => 'danger'],
                        'pending' => ['label' => __('resident.dashboard.not_confirmed'), 'tone' => 'neutral'],
                    ];
                    $selectedStatusKey = $rsvpStatuses[$selectedEvent->id] ?? 'pending';
                    $selectedStatusMeta = $statusMap[$selectedStatusKey] ?? $statusMap['pending'];
                    $selectedStart = $selectedEvent->start_at;
                    $selectedEnd = $selectedEvent->end_at;
                    $selectedStartDisplay = $selectedStart
                        ? ($selectedEvent->is_all_day ? $selectedStart->translatedFormat('l, d M Y') : $selectedStart->translatedFormat('l, d M Y H:i'))
                        : __('resident.dashboard.schedule_to_be_announced');
                    $selectedEndDisplay = $selectedEnd
                        ? ($selectedEvent->is_all_day ? $selectedEnd->translatedFormat('l, d M Y') : $selectedEnd->translatedFormat('l, d M Y H:i'))
                        : null;
                    $selectedTimeRange = $selectedEndDisplay ? $selectedStartDisplay . ' - ' . $selectedEndDisplay : $selectedStartDisplay;
                    $selectedLocation = $selectedEvent->location ?: __('resident.dashboard.location_will_be_announced');
                    $selectedShareMessage = __('resident.dashboard.bill') . ": {$selectedEvent->title}\\n"
                        . __('resident.dashboard.time') . ": {$selectedTimeRange}\\n"
                        . __('resident.dashboard.location') . ": {$selectedLocation}";
                    $selectedWhatsappLink = 'https://wa.me/?text=' . rawurlencode($selectedShareMessage);
                    $selectedTelegramLink = 'https://t.me/share/url?text=' . rawurlencode($selectedShareMessage);
                    $selectedIcsLink = route('resident.events.ics', $selectedEvent);
                @endphp
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $selectedEvent->title }}</h3>
                                <span data-resident-chip data-tone="{{ $selectedStatusMeta['tone'] }}">{{ $selectedStatusMeta['label'] }}</span>
                            </div>
                        @else
                            <h3 class="mt-3 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.dashboard.upcoming_agenda') }}</h3>
                        @endif
                    </div>
                    <button type="button" class="rounded-full border border-slate-200 bg-white/80 p-2 text-slate-500 transition-colors duration-200 hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:text-slate-100" wire:click="closeAgendaPanel" aria-label="{{ __('resident.dashboard.close') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 pb-6">
                    @if ($selectedEvent)
                        <dl class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.time') }}</dt>
                                <dd class="mt-1 font-semibold text-slate-800 dark:text-slate-100">{{ $selectedTimeRange }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.location') }}</dt>
                                <dd class="mt-1">{{ $selectedLocation }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.description') }}</dt>
                                <dd class="mt-1 leading-relaxed">{{ $selectedEvent->description ?: __('resident.dashboard.no_additional_details') }}</dd>
                            </div>
                        </dl>
                        <div class="mt-6 space-y-4 text-[11px] font-semibold">
                            <div>
                                <p class="uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ __('resident.dashboard.attendance_status') }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @foreach ([
                                        
                                        App\Models\EventAttendance::STATUS_GOING => __('resident.dashboard.attending'), 
                                        
                                        App\Models\EventAttendance::STATUS_MAYBE => __('resident.dashboard.considering'), 
                                        
                                        App\Models\EventAttendance::STATUS_DECLINED => __('resident.dashboard.decline')
                                    
                                    ] as $statusKey => $label)
                                        @php
                                            $isActive = $selectedStatusKey === $statusKey;
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="setRsvpStatus({{ $selectedEvent->id }}, '{{ $statusKey }}' )"
                                            wire:loading.attr="disabled"
                                            wire:target="setRsvpStatus"
                                            class="{{ $isActive
                                                ? 'inline-flex items-center gap-2 rounded-full border border-sky-300 bg-sky-100/80 px-3 py-1.5 text-sky-700 transition-colors duration-200 dark:border-sky-500/60 dark:bg-sky-500/15 dark:text-sky-200'
                                                : 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-500 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-sky-500 dark:hover:text-sky-200' }}"
                                        >
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                    <button
                                        type="button"
                                        wire:click="setRsvpStatus({{ $selectedEvent->id }}, '{{ \App\Models\EventAttendance::STATUS_PENDING }}' )"
                                        wire:loading.attr="disabled"
                                        wire:target="setRsvpStatus"
                                        class="inline-flex items-center gap-2 rounded-full border border-transparent bg-slate-100 px-3 py-1.5 text-slate-400 transition-colors duration-200 hover:bg-slate-200 hover:text-slate-600 dark:bg-slate-800 dark:text-slate-500 dark:hover:bg-slate-700"
                                    >
                                        {{ __('resident.dashboard.reset') }}
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ __('resident.dashboard.quick_reminders') }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <a
                                        href="{{ $selectedWhatsappLink }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-emerald-600 transition-colors duration-200 hover:border-emerald-300 hover:text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/15 dark:text-emerald-200"
                                    >
                                        {{ __('resident.dashboard.whatsapp') }}
                                    </a>
                                    <a
                                        href="{{ $selectedTelegramLink }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-sky-600 transition-colors duration-200 hover:border-sky-300 hover:text-sky-700 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200"
                                    >
                                        {{ __('resident.dashboard.telegram') }}
                                    </a>
                                    <a
                                        href="{{ $selectedIcsLink }}"
                                        onclick="event.stopPropagation();"
                                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-500 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-sky-500 dark:hover:text-sky-200"
                                    >
                                        {{ __('resident.dashboard.download_ics') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('resident.dashboard.select_agenda_to_view_details') }}</p>
                    @endif
                    <div class="mt-6 flex justify-end">
                        <button type="button" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#0284C7]/15 px-5 py-2 text-xs font-semibold text-[#0284C7] transition-colors duration-200 hover:bg-[#0284C7] hover:text-white dark:bg-sky-500/15 dark:text-sky-200 dark:hover:bg-sky-500 dark:hover:text-slate-900" wire:click="closeAgendaPanel">
                            {{ __('resident.dashboard.done') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>



