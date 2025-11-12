<section id="flow-demo" class="border-t border-slate-200/70 bg-slate-50/70 py-12 transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-950/60 md:py-16">
    <div class="container-app grid gap-10 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-center">
        <div class="space-y-4">
            <p class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.25em] text-sky-600 dark:text-sky-300" data-i18n="flow_demo.kicker">Lihat Cara Kerjanya</p>
            <h2 class="text-3xl font-semibold text-slate-900 dark:text-slate-100 md:text-4xl" data-i18n="flow_demo.title">3 langkah aktivasi portal warga</h2>
            <p class="text-base leading-relaxed text-slate-600 dark:text-slate-400" data-i18n="flow_demo.description">Ikuti simulasi singkat ini untuk memahami bagaimana pengurus mengundang warga, menagihkan iuran, dan memantau pembayaran tanpa repot.</p>
        </div>
        <div class="flex flex-col gap-6 rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-lg shadow-slate-200/60 transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/80 dark:shadow-slate-900/40" data-flow-demo>
            <div class="flex flex-wrap gap-2" role="tablist">
                <button type="button"
                        class="flow-demo__step"
                        data-flow-step="invite"
                        data-active="true"
                        role="tab"
                        aria-selected="true"
                        aria-controls="flow-panel-invite"
                        data-i18n="flow_demo.steps.invite.title">Undang Pengurus &amp; Warga</button>
                <button type="button"
                        class="flow-demo__step"
                        data-flow-step="bill"
                        data-active="false"
                        role="tab"
                        aria-selected="false"
                        aria-controls="flow-panel-bill"
                        data-i18n="flow_demo.steps.bill.title">Terbitkan Tagihan</button>
                <button type="button"
                        class="flow-demo__step"
                        data-flow-step="monitor"
                        data-active="false"
                        role="tab"
                        aria-selected="false"
                        aria-controls="flow-panel-monitor"
                        data-i18n="flow_demo.steps.monitor.title">Pantau &amp; Ingatkan</button>
            </div>
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 transition-colors duration-300 dark:border-slate-700/70 dark:bg-slate-900/70" data-flow-panels>
                <article id="flow-panel-invite"
                         class="flow-demo__panel"
                         data-flow-panel="invite"
                         data-active="true"
                         role="tabpanel"
                         tabindex="0">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100" data-i18n="flow_demo.steps.invite.heading">Mulai dari dashboard admin</h3>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300" data-i18n="flow_demo.steps.invite.copy">Pengurus membuat akun RT, melengkapi profil lingkungan, dan mengundang pengurus lain lewat email atau WhatsApp.</p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li data-i18n="flow_demo.steps.invite.bullet_1">Aktifkan tema dan logo lingkungan agar warga langsung familiar.</li>
                        <li data-i18n="flow_demo.steps.invite.bullet_2">Gunakan template pesan siap pakai untuk menyebarkan tautan registrasi warga.</li>
                    </ul>
                </article>
                <article id="flow-panel-bill"
                         class="flow-demo__panel"
                         data-flow-panel="bill"
                         data-active="false"
                         role="tabpanel"
                         tabindex="-1"
                         hidden>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100" data-i18n="flow_demo.steps.bill.heading">Tagihan sekali klik</h3>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300" data-i18n="flow_demo.steps.bill.copy">Template iuran bulanan membantu pengurus membuat tagihan otomatis sesuai kategori kepala keluarga.</p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li data-i18n="flow_demo.steps.bill.bullet_1">Tambahkan jatuh tempo dan bukti pembayaran langsung dari WhatsApp.</li>
                        <li data-i18n="flow_demo.steps.bill.bullet_2">Kirim notifikasi ke warga melalui email, Telegram, atau SMS gateway.</li>
                    </ul>
                </article>
                <article id="flow-panel-monitor"
                         class="flow-demo__panel"
                         data-flow-panel="monitor"
                         data-active="false"
                         role="tabpanel"
                         tabindex="-1"
                         hidden>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100" data-i18n="flow_demo.steps.monitor.heading">Pantau kas real time</h3>
                    <p class="mt-3 text-base leading-relaxed text-slate-600 dark:text-slate-300" data-i18n="flow_demo.steps.monitor.copy">Grafik kas dan daftar warga terlambat bayar selalu diperbarui sehingga pengurus cukup menekan tombol kirim pengingat.</p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-500 dark:text-slate-400">
                        <li data-i18n="flow_demo.steps.monitor.bullet_1">Filter berdasarkan RT/RW, blok rumah, atau status aktif.</li>
                        <li data-i18n="flow_demo.steps.monitor.bullet_2">Gunakan riwayat transaksi untuk menyusun laporan bulanan secara otomatis.</li>
                    </ul>
                </article>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const demo = document.querySelector('[data-flow-demo]');
        if (!demo || demo.dataset.init === '1') {
            return;
        }

        const steps = Array.from(demo.querySelectorAll('[data-flow-step]'));
        const panels = Array.from(demo.querySelectorAll('[data-flow-panel]'));

        const setActive = (target) => {
            steps.forEach((step) => {
                const active = step.dataset.flowStep === target;
                step.dataset.active = active ? 'true' : 'false';
                step.setAttribute('aria-selected', active ? 'true' : 'false');
                step.classList.toggle('flow-demo__step--active', active);
            });
            panels.forEach((panel) => {
                const active = panel.dataset.flowPanel === target;
                panel.dataset.active = active ? 'true' : 'false';
                panel.toggleAttribute('hidden', !active);
                panel.setAttribute('tabindex', active ? '0' : '-1');
            });
            const activePanel = panels.find((panel) => panel.dataset.flowPanel === target);
            if (activePanel) {
                activePanel.focus({ preventScroll: true });
            }
        };

        steps.forEach((step) => {
            step.addEventListener('click', () => setActive(step.dataset.flowStep));
            step.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setActive(step.dataset.flowStep);
                }
            });
        });

        demo.dataset.init = '1';
    });
</script>
@endpush
