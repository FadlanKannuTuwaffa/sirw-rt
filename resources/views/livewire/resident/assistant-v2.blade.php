<div x-data="chatAssistant()" x-init="initChat()">
    <script>
        function chatAssistant() {
            return {
                streaming: false,
                timezone() {
                    try {
                        const resolved = Intl.DateTimeFormat().resolvedOptions().timeZone;
                        return resolved || 'UTC';
                    } catch (error) {
                        return 'UTC';
                    }
                },
                initChat() {
                    this.$wire.on('stream-chat', () => {
                        this.sendMessage();
                    });
                },
                async sendMessage() {
                    if (this.streaming) return;
                    this.streaming = true;

                    const idx = this.$wire.messages.length - 1;
                    const prevIdx = idx - 1;
                    const userMsg = this.$wire.messages[prevIdx] ? this.$wire.messages[prevIdx].text : '';
                    this.$wire.messages[idx].text = '';

                    try {
                        const token = await this.$wire.getToken();
                        const timezone = this.timezone();
                        const res = await fetch('/api/assistant/chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/event-stream',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Authorization': 'Bearer ' + token,
                                'X-Assistant-Timezone': timezone,
                            },
                            body: JSON.stringify({ message: userMsg, timezone })
                        });

                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        const reader = res.body.getReader();
                        const decoder = new TextDecoder();
                        let buf = '';

                        while (true) {
                            const r = await reader.read();
                            if (r.done) break;

                            buf += decoder.decode(r.value, { stream: true });
                            const lines = buf.split('\n');
                            buf = lines.pop() || '';

                            for (let line of lines) {
                                if (!line.trim() || !line.startsWith('data: ')) continue;
                                
                                try {
                                    const json = line.substring(6).trim();
                                    if (!json) continue;
                                    
                                    const obj = JSON.parse(json);
                                    if (!obj) continue;
                                    
                                    const { type, content } = obj;
                                    
                                    if (type === 'token') {
                                        const txt = content !== undefined && content !== null ? String(content) : '';
                                        if (txt) {
                                            this.$wire.messages[idx].text += txt;
                                        }
                                    } else if (type === 'done') {
                                        this.streaming = false;
                                        return;
                                    }
                                } catch (e) {
                                    console.error('Parse error:', e, 'Line:', line);
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Error:', err);
                        this.$wire.messages[idx].text = 'Error: ' + err.message;
                    }

                    this.streaming = false;
                }
            }
        }
    </script>

<div
    data-resident-card
    data-variant="muted"
    class="flex flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white/95 p-4 shadow-sm transition-colors duration-300 dark:border-slate-700/60 dark:bg-slate-900/70"
    data-resident-assistant
>
    <header class="flex flex-col gap-1">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sky-500 dark:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3h5.25m5.25 6.75a2.25 2.25 0 0 1-2.25 2.25h-9.75A2.25 2.25 0 0 1 3 18.75V5.25A2.25 2.25 0 0 1 5.25 3h9l4.5 4.5z" />
            </svg>
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.assistant.title') }}</h2>
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ __('resident.assistant.subtitle') }}</p>
    </header>

    <div class="flex flex-col gap-3 max-h-56 overflow-y-auto pr-1">
        @foreach ($messages as $message)
            @php
                $isAssistant = $message['type'] === 'assistant';
            @endphp
            <div
                class="{{ $isAssistant
                    ? 'ml-0 mr-auto rounded-2xl rounded-bl-sm bg-sky-50 px-3 py-2 text-xs font-medium text-slate-700 dark:bg-sky-500/10 dark:text-sky-100'
                    : 'ml-auto mr-0 rounded-2xl rounded-br-sm bg-slate-900 px-3 py-2 text-xs font-semibold text-white dark:bg-slate-700' }}"
            >
                {!! nl2br(e($message['text'])) !!}
            </div>
        @endforeach
    </div>

    @if (! empty($suggestions))
        <div class="flex flex-col gap-2">
            <p class="text-[10px] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">{{ __('resident.assistant.suggestion_label') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($suggestions as $suggestion)
                    <button
                        type="button"
                        wire:click="useSuggestion('{{ $suggestion }}')"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-medium text-slate-500 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-sky-500 dark:hover:text-sky-200"
                    >
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <form wire:submit.prevent="ask" class="flex items-center gap-2">
        <label for="assistant-question" class="sr-only">{{ __('resident.assistant.input_placeholder') }}</label>
        <input
            id="assistant-question"
            type="text"
            wire:model.defer="question"
            placeholder="{{ __('resident.assistant.input_placeholder') }}"
            class="flex-1 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600 transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-sky-500 dark:focus:ring-sky-500/40"
        />
        <button
            type="submit"
            class="inline-flex items-center gap-1 rounded-full bg-sky-500 px-3 py-2 text-[11px] font-semibold text-white transition-colors duration-200 hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-1 focus:ring-offset-white dark:bg-sky-500/80 dark:hover:bg-sky-500 dark:focus:ring-sky-500/60"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 12 12-9-5.25 18L9.75 13.5 6 12z" />
            </svg>
            {{ __('resident.assistant.send_label') }}
        </button>
    </form>
</div>
</div>
