<div x-data="assistantChatNew()" x-init="init()" class="assistant-container">
    <style>
        .assistant-container { max-width: 100%; margin: 0 auto; }
        .chat-messages { scroll-behavior: smooth; }
        .message-bubble { animation: slideIn 0.3s ease-out; max-width: 85%; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .typing-indicator { display: inline-flex; gap: 4px; padding: 12px 16px; }
        .typing-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; animation: typing 1.4s infinite; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing { 0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); } 30% { opacity: 1; transform: scale(1); } }
        .suggestion-chip { transition: all 0.2s ease; }
        .suggestion-chip:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .quick-reply { transition: all 0.15s ease; }
        .quick-reply:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(14, 165, 233, 0.15); }
        .send-button { transition: all 0.2s ease; }
        .send-button:hover:not(:disabled) { transform: scale(1.05); }
        .send-button:disabled { opacity: 0.5; cursor: not-allowed; }
        [x-cloak] { display: none !important; }
    </style>
    
    <script>
        const assistantAuthToken = @json($token ?? '');
        const escapeHtml = (value = '') => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        const formatStreamText = (value = '') => escapeHtml(value).replace(/\n/g, '<br>');
        function assistantChatNew() {
            return {
                streaming: false,
                timeout: null,
                isTyping: false,
                streamBuffer: '',
                currentAssistantIndex: null,
                timezone() {
                    try {
                        const resolved = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        return resolved || 'UTC';
                    } catch (error) {
                        return 'UTC';
                    }
                },
                assistantBubble(index) {
                    return this.$el.querySelector(`[data-message-text="${index}"]`);
                },
                renderAssistantText(index, text = '') {
                    const target = this.assistantBubble(index);
                    if (!target) {
                        if (index !== this.currentAssistantIndex) {
                            return;
                        }

                        this.$nextTick(() => {
                            const pendingTarget = this.assistantBubble(index);
                            if (pendingTarget) {
                                pendingTarget.innerHTML = formatStreamText(text);
                            }
                        });
                        return;
                    }

                    target.innerHTML = formatStreamText(text);
                },
                init() {
                    this.$wire.on('stream-chat', () => {
                        clearTimeout(this.timeout);
                        this.timeout = setTimeout(() => this.send(), 100);
                    });
                },
                async send() {
                    if (this.streaming) return;
                    this.streaming = true;
                    this.isTyping = true;

                    const assistantIndex = this.$wire.messages.length - 1;
                    const userIndex = assistantIndex - 1;
                    const msg = this.$wire.messages[userIndex]?.text || '';

                    this.currentAssistantIndex = assistantIndex;
                    this.streamBuffer = '';
                    this.renderAssistantText(assistantIndex, '');

                    await this.$wire.set('messages.' + assistantIndex + '.text', '');
                    await this.$wire.set('messages.' + assistantIndex + '.meta', {});
                    
                    this.$nextTick(() => {
                        const container = this.$el.querySelector('.chat-messages');
                        if (container) container.scrollTop = container.scrollHeight;
                    });

                    try {
                        const token = this.$wire.token || assistantAuthToken || '';
                        const timezone = this.timezone();
                        const res = await fetch('/api/assistant/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/event-stream',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'Authorization': 'Bearer ' + token,
                                'X-Assistant-Timezone': timezone,
                            },
                            body: JSON.stringify({ message: msg, timezone })
                        });

                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        const reader = res.body?.getReader ? res.body.getReader() : null;
                        if (!reader) {
                            const fallbackText = await res.text().catch(() => '');
                            const hint = fallbackText || 'Gagal membaca stream balasan.';
                            this.renderAssistantText(assistantIndex, hint);
                            await this.$wire.set('messages.' + assistantIndex + '.text', hint);
                            this.currentAssistantIndex = null;
                            this.streamBuffer = '';
                            this.isTyping = false;
                            this.streaming = false;
                            return;
                        }
                        const decoder = new TextDecoder();
                        let buf = '';

                        while (true) {
                            const {done, value} = await reader.read();
                            if (done) break;

                            buf += decoder.decode(value, {stream: true});
                            const lines = buf.split('\n');
                            buf = lines.pop() || '';

                            for (let line of lines) {
                                if (!line.trim() || !line.startsWith('data: ')) continue;
                                const json = line.substring(6).trim();
                                if (!json) continue;
                                
                                try {
                                    const data = JSON.parse(json);
                                    if (data?.type === 'token') {
                                        const content = data.content ?? '';
                                        if (assistantIndex === this.currentAssistantIndex) {
                                            this.streamBuffer += content;
                                            this.renderAssistantText(assistantIndex, this.streamBuffer);
                                            this.$nextTick(() => {
                                                const container = this.$el.querySelector('.chat-messages');
                                                if (container) container.scrollTop = container.scrollHeight;
                                            });
                                        }
                                    } else if (data?.type === 'meta') {
                                        await this.$wire.set('messages.' + assistantIndex + '.meta', data.content ?? {});
                                    } else if (data?.type === 'done') {
                                        if (assistantIndex === this.currentAssistantIndex) {
                                            await this.$wire.set('messages.' + assistantIndex + '.text', this.streamBuffer);
                                        }
                                        this.currentAssistantIndex = null;
                                        this.streamBuffer = '';
                                        this.isTyping = false;
                                        this.streaming = false;
                                        return;
                                    }
                                } catch (e) {
                                    const fallback = 'Error: ' + e.message;
                                    this.renderAssistantText(assistantIndex, fallback);
                                    await this.$wire.set('messages.' + assistantIndex + '.text', fallback);
                                    this.currentAssistantIndex = null;
                                    this.streamBuffer = '';
                                }
                            }
                        }
                    } catch (err) {
                        const fallback = 'Error: ' + err.message;
                        this.renderAssistantText(assistantIndex, fallback);
                        await this.$wire.set('messages.' + assistantIndex + '.text', fallback);
                        this.currentAssistantIndex = null;
                        this.streamBuffer = '';
                    }

                    this.isTyping = false;
                    this.streaming = false;
                }
        }
    }
</script>

<script>
        function kbFeedbackWidget(meta = {}) {
            return {
                state: 'ask',
                note: '',
                submitted: false,
                submitting: false,
                error: '',
                meta,
                async submit(helpful) {
                    if (this.submitting || this.submitted || !this.meta.token) {
                        return;
                    }

                    this.submitting = true;
                    this.error = '';

                    const headers = {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    };

                    if (assistantAuthToken) {
                        headers['Authorization'] = 'Bearer ' + assistantAuthToken;
                    }

                    try {
                        const res = await fetch('/api/assistant/feedback/kb', {
                            method: 'POST',
                            headers,
                            body: JSON.stringify({
                                token: this.meta.token,
                                helpful,
                                note: helpful ? null : (this.note || null),
                            }),
                        });

                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        this.state = 'done';
                        this.submitted = true;
                    } catch (error) {
                        this.error = 'Gagal menyimpan feedback. Coba lagi ya.';
                        this.state = 'error';
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>

<div class="flex flex-col gap-4 rounded-2xl border border-slate-200/70 bg-gradient-to-br from-white to-slate-50/50 p-5 shadow-lg backdrop-blur-sm transition-all duration-300 hover:shadow-xl dark:border-slate-700/60 dark:from-slate-900 dark:to-slate-800/50" x-data="{ showPrefs:false }">
    <header class="flex items-center gap-3 pb-3 border-b border-slate-200/50 dark:border-slate-700/50">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
        </div>
        <div class="flex-1">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ __('resident.assistant.title') }}</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.assistant.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-white/60 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-sky-300 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200" @click="showPrefs = true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 16v-2m4.95-7.05l1.414-1.414M5.636 18.364l1.414-1.414M18 12h2M4 12h2m9.9 4.95l1.414 1.414M6.343 5.636l1.414 1.414"/>
                </svg>
                Preferensi gaya jawab
            </button>
            <div class="h-2 w-2 rounded-full bg-green-500 shadow-lg shadow-green-500/50" title="Online"></div>
        </div>
    </header>

    <div class="chat-messages flex flex-col gap-3 max-h-80 min-h-[200px] overflow-y-auto pr-2 scroll-smooth" style="scrollbar-width: thin;">
        @foreach ($messages as $message)
            <div class="message-bubble {{ $message['type'] === 'assistant' ? 'ml-0 mr-auto' : 'ml-auto mr-0' }}" wire:key="chat-message-{{ $loop->index }}">
                @if($message['type'] === 'assistant')
                    <div class="flex items-start gap-2">
                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                            </svg>
                        </div>
                        <div class="rounded-2xl rounded-tl-sm bg-gradient-to-br from-sky-50 to-blue-50/50 px-4 py-2.5 text-sm text-slate-700 shadow-sm dark:from-sky-500/10 dark:to-blue-500/5 dark:text-sky-100" data-message-text="{{ $loop->index }}">
                            {!! nl2br(e($message['text'])) !!}
                        </div>
                    </div>
                    @if (!empty($message['meta']['knowledge_feedback']['token'] ?? null))
                        <div class="ml-9 mt-2" x-data="kbFeedbackWidget(@js($message['meta']['knowledge_feedback']))" x-cloak>
                            <div class="rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3 text-xs text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200" x-show="state === 'ask'">
                                <p class="font-semibold text-slate-700 dark:text-slate-100">Apakah jawaban ini membantu?</p>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" class="rounded-full bg-emerald-500/90 px-3 py-1 font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:opacity-60" @click="submit(true)" :disabled="submitting">Ya, membantu</button>
                                    <button type="button" class="rounded-full border border-slate-300 px-3 py-1 font-semibold text-slate-600 transition hover:border-rose-400 hover:text-rose-500 dark:border-slate-600 dark:text-slate-200" @click="state = 'note'">Tidak</button>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-rose-200/80 bg-rose-50/70 px-4 py-3 text-xs text-rose-700 shadow-sm dark:border-rose-700/40 dark:bg-rose-900/40 dark:text-rose-100" x-show="state === 'note'">
                                <p class="font-semibold">Bagian mana yang kurang tepat?</p>
                                <textarea x-model="note" rows="2" class="mt-2 w-full rounded-xl border border-rose-200 bg-white/90 px-3 py-2 text-slate-700 focus:border-rose-400 focus:outline-none dark:border-rose-700/70 dark:bg-rose-900/30 dark:text-slate-100" placeholder="Contoh: tolong detailkan dokumen yang dibutuhkan"></textarea>
                                <div class="mt-2 flex gap-2">
                                    <button type="button" class="rounded-full bg-rose-500 px-3 py-1 font-semibold text-white shadow-sm transition hover:bg-rose-600 disabled:opacity-60" @click="submit(false)" :disabled="submitting">Kirim</button>
                                    <button type="button" class="rounded-full border border-rose-200/80 px-3 py-1 font-semibold text-rose-600 transition hover:border-rose-300 hover:text-rose-700 dark:border-rose-700/70 dark:text-rose-200" @click="state = 'ask'">Batal</button>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/70 px-4 py-3 text-xs font-semibold text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/30 dark:text-emerald-200" x-show="state === 'done'">
                                Terima kasih! Masukan kamu sudah dicatat.
                            </div>
                            <div class="rounded-2xl border border-rose-200/80 bg-rose-50/70 px-4 py-3 text-xs font-semibold text-rose-600 dark:border-rose-700/40 dark:bg-rose-900/30 dark:text-rose-200" x-show="state === 'error'">
                                <span x-text="error"></span>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="rounded-2xl rounded-tr-sm bg-gradient-to-br from-slate-900 to-slate-800 px-4 py-2.5 text-sm font-medium text-white shadow-md dark:from-slate-700 dark:to-slate-600">
                        {!! nl2br(e($message['text'])) !!}
                    </div>
                @endif
            </div>
        @endforeach
        
        <div x-show="isTyping" class="message-bubble ml-0 mr-auto" x-transition>
            <div class="flex items-start gap-2">
                <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div class="typing-indicator rounded-2xl rounded-tl-sm bg-gradient-to-br from-sky-50 to-blue-50/50 text-sky-600 shadow-sm dark:from-sky-500/10 dark:to-blue-500/5">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($suggestions) && count($messages) <= 2)
        <div class="flex flex-col gap-2.5 pt-2 border-t border-slate-200/50 dark:border-slate-700/50">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">💡 {{ __('resident.assistant.suggestion_label') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($suggestions as $suggestion)
                    <button type="button" wire:click="useSuggestion('{{ $suggestion }}')" class="suggestion-chip inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-white px-3.5 py-1.5 text-xs font-medium text-slate-600 shadow-sm transition-all duration-200 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 hover:shadow-md dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:bg-sky-500/10 dark:hover:text-sky-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if (!empty($chips) && count($messages) <= 2)
        <div class="flex flex-col gap-2 pt-1 border-t border-dashed border-slate-200/60 dark:border-slate-700/60">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                ⚡ {{ app()->getLocale() === 'en' ? 'Quick replies' : 'Balasan cepat' }}
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach ($chips as $chip)
                    <button
                        type="button"
                        wire:click="useSuggestion(@js($chip))"
                        class="quick-reply inline-flex items-center gap-1 rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 shadow-sm hover:bg-sky-100 hover:text-sky-900 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200 dark:hover:border-sky-400 dark:hover:bg-sky-500/20 dark:hover:text-sky-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ $chip }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

<form wire:submit.prevent="ask" class="flex items-center gap-2 pt-2">
        <div class="relative flex-1">
            <input 
                id="assistant-question" 
                type="text" 
                wire:model.defer="question" 
                placeholder="{{ __('resident.assistant.input_placeholder') }}" 
                x-bind:disabled="streaming"
                class="w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 pr-12 text-sm text-slate-700 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200/50 focus:shadow-md disabled:opacity-50 disabled:cursor-not-allowed dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-sky-500 dark:focus:ring-sky-500/30" 
            />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                </svg>
            </div>
        </div>
        <button 
            type="submit" 
            x-bind:disabled="streaming"
            class="send-button flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/30 transition-all duration-200 hover:shadow-xl hover:shadow-sky-500/40 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed dark:shadow-sky-500/20 dark:hover:shadow-sky-500/30"
            title="{{ __('resident.assistant.send_label') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
            </svg>
        </button>
</form>

    <div x-cloak x-show="showPrefs" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/60 p-4" @keydown.escape.window="showPrefs = false" @click.self="showPrefs = false">
        <div class="relative w-full max-w-2xl rounded-3xl border border-slate-200/80 bg-white p-5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
            <button type="button" class="absolute right-3 top-3 rounded-full border border-slate-200/80 p-1 text-slate-500 transition hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:text-slate-300" @click="showPrefs = false">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-sm dark:border-slate-700/60 dark:bg-slate-900/60">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Preferensi gaya jawab</p>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Atur bahasa & tone favoritmu</h3>
                    </div>
                    @if ($styleStatus === 'saved')
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-300">Tersimpan</span>
                    @endif
                </div>

                <form wire:submit.prevent="saveStylePreferences" class="mt-4 space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Bahasa utama</span>
                            <select wire:model.defer="styleLanguage" class="rounded-xl border border-slate-200/70 bg-white/95 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100">
                                <option value="id">Bahasa Indonesia</option>
                                <option value="en">Bahasa Inggris</option>
                                <option value="jv">Bahasa Jawa</option>
                                <option value="su">Bahasa Sunda</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Tone / formality</span>
                            <select wire:model.defer="styleFormality" class="rounded-xl border border-slate-200/70 bg-white/95 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100">
                                <option value="santai">Santai & friendly</option>
                                <option value="formal">Formal & rapi</option>
                                <option value="tegas">Tegas & singkat</option>
                            </select>
                        </label>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Emoji</span>
                            <select wire:model.defer="styleEmojiPolicy" class="rounded-xl border border-slate-200/70 bg-white/95 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100">
                                <option value="none">Tanpa emoji</option>
                                <option value="light">Sedikit emoji</option>
                                <option value="full">Bebas pakai emoji</option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Perkenalkan diri</span>
                            <select wire:model.defer="styleIntroduceSelf" class="rounded-xl border border-slate-200/70 bg-white/95 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100">
                                <option value="1">Iya, kenalkan diri di awal</option>
                                <option value="0">Tidak perlu kenalan lagi</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex items-center gap-2 rounded-2xl border border-slate-200/70 bg-white/90 px-3 py-2 text-xs dark:border-slate-700/70 dark:bg-slate-900/60">
                        <input id="pref-humor" type="checkbox" wire:model="styleHumor" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500 dark:border-slate-600 dark:bg-slate-800">
                        <label for="pref-humor" class="text-slate-600 dark:text-slate-300">Boleh sisipkan candaan ringan</label>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveStylePreferences" class="rounded-full bg-gradient-to-r from-sky-500 to-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm transition hover:shadow-lg disabled:opacity-50">
                            <span wire:loading.remove wire:target="saveStylePreferences">Simpan preferensi</span>
                            <span wire:loading wire:target="saveStylePreferences">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
