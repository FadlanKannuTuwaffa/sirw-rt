import "./bootstrap";
const ThemeManager = (() => {
    const STORAGE_KEY = "sirw/theme-mode";
    const VALID_MODES = new Set(["light", "dark", "auto"]);
    const ORDERED_MODES = ["light", "dark", "auto"];
    let mode = "auto";
    let resolved = "light";
    let initialized = false;
    let mediaQuery;
    let observer;

    const resolveAuto = () => {
        if (mediaQuery && typeof mediaQuery.matches === "boolean") {
            return mediaQuery.matches ? "dark" : "light";
        }
        if (typeof window !== "undefined" && window.matchMedia) {
            mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
            return mediaQuery.matches ? "dark" : "light";
        }
        return "light";
    };

    const applyTheme = () => {
        const root =
            typeof document !== "undefined" ? document.documentElement : null;
        if (!root) {
            return;
        }
        resolved = mode === "auto" ? resolveAuto() : mode;
        root.classList.toggle("dark", resolved === "dark");
        root.dataset.theme = resolved;
        root.dataset.themeMode = mode;

        if (typeof document !== "undefined") {
            const body = document.body;
            if (body) {
                body.classList.toggle("dark", resolved === "dark");
                body.dataset.theme = resolved;
                body.dataset.themeMode = mode;
            }
        }
    };

    const syncControls = () => {
        if (typeof document === "undefined") {
            return;
        }
        document.querySelectorAll("[data-theme-mode]").forEach((button) => {
            const target = button.getAttribute("data-theme-mode");
            if (!target) {
                return;
            }
            const isActive = target === mode;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
        document.querySelectorAll("[data-theme-state]").forEach((node) => {
            node.textContent = mode;
        });
    };

    const bindControls = () => {
        if (typeof document === "undefined") {
            return;
        }
        document.querySelectorAll("[data-theme-mode]").forEach((button) => {
            if (button.dataset.themeBound === "mode") {
                return;
            }
            button.addEventListener("click", () => {
                const target = button.getAttribute("data-theme-mode");
                if (target) {
                    setMode(target);
                }
            });
            button.dataset.themeBound = "mode";
        });
        document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
            if (button.dataset.themeBound === "toggle") {
                return;
            }
            button.addEventListener("click", () => {
                const rawSequence = button.getAttribute("data-theme-sequence");
                const sequence = rawSequence
                    ? rawSequence
                          .split(",")
                          .map((item) => item.trim())
                          .filter(Boolean)
                    : undefined;
                cycleMode(sequence);
            });
            button.dataset.themeBound = "toggle";
        });
    };

    const refreshControls = () => {
        syncControls();
        bindControls();
    };

    const persist = () => {
        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch (error) {
            console.warn("Unable to persist theme mode", error);
        }
    };

    const setMode = (nextMode) => {
        if (!VALID_MODES.has(nextMode) || mode === nextMode) {
            return;
        }
        mode = nextMode;
        persist();
        applyTheme();
        refreshControls();
    };

    const cycleMode = (sequence) => {
        const list = sequence && sequence.length ? sequence : ORDERED_MODES;
        const index = list.indexOf(mode);
        const nextIndex = index === -1 ? 0 : (index + 1) % list.length;
        setMode(list[nextIndex]);
    };

    const handleMediaChange = () => {
        if (mode !== "auto") {
            return;
        }
        applyTheme();
        refreshControls();
    };

    const setupMediaQuery = () => {
        if (typeof window === "undefined" || !window.matchMedia) {
            return;
        }
        mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
        if (typeof mediaQuery.addEventListener === "function") {
            mediaQuery.addEventListener("change", handleMediaChange);
        } else if (typeof mediaQuery.addListener === "function") {
            mediaQuery.addListener(handleMediaChange);
        }
    };

    const ensureObserver = () => {
        if (
            typeof MutationObserver === "undefined" ||
            typeof document === "undefined"
        ) {
            return null;
        }
        if (!observer) {
            observer = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    if (
                        mutation.type === "childList" &&
                        mutation.addedNodes.length
                    ) {
                        refreshControls();
                        break;
                    }
                }
            });
        }
        return observer;
    };

    const setupObserver = () => {
        const instance = ensureObserver();
        if (!instance || typeof document === "undefined") {
            return;
        }
        const target = document.body;
        if (!target) {
            return;
        }
        instance.disconnect();
        instance.observe(target, { childList: true, subtree: true });
    };

    const handleLivewireNavigation = () => {
        if (typeof document === "undefined") {
            return;
        }
        document.addEventListener("livewire:navigated", () => {
            applyTheme();
            refreshControls();
            initMenus();
            setupObserver();
        });
    };

    const handleStorageChange = () => {
        if (typeof window === "undefined") {
            return;
        }
        window.addEventListener("storage", (event) => {
            if (
                event.key !== STORAGE_KEY ||
                !event.newValue ||
                !VALID_MODES.has(event.newValue)
            ) {
                return;
            }
            mode = event.newValue;
            applyTheme();
            refreshControls();
        });
    };

    const loadInitial = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored && VALID_MODES.has(stored)) {
                mode = stored;
            } else if (stored && !VALID_MODES.has(stored)) {
                localStorage.removeItem(STORAGE_KEY);
            } else if (!stored) {
                persist();
            }
        } catch (error) {
            console.warn("Unable to read theme mode", error);
        }
    };

    const start = () => {
        applyTheme();
        refreshControls();
        setupObserver();
    };

    const init = () => {
        if (initialized || typeof document === "undefined") {
            return;
        }
        initialized = true;
        setupMediaQuery();
        handleLivewireNavigation();
        handleStorageChange();
        loadInitial();
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", start, {
                once: true,
            });
        } else {
            start();
        }
    };

    return {
        init,
        getState: () => ({ mode, resolved }),
        setMode,
    };
})();
const MotionManager = (() => {
    const STORAGE_KEY = "sirw/motion-mode";
    const CALM = "calm";
    const EXPRESSIVE = "expressive";
    const VALID_MODES = new Set([CALM, EXPRESSIVE]);
    let mode = EXPRESSIVE;
    let persisted = false;
    let initialized = false;
    let mediaQuery = null;

    const getRoot = () =>
        typeof document !== "undefined" ? document.documentElement : null;

    const apply = () => {
        const root = getRoot();
        if (!root) {
            return;
        }
        root.dataset.motionMode = mode;
        const body =
            typeof document !== "undefined" ? document.body : null;
        if (body) {
            body.dataset.motionMode = mode;
        }
    };

    const persist = () => {
        if (!persisted) {
            persisted = true;
        }
        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch (error) {
            console.warn("Unable to persist motion mode", error);
        }
    };

    const refreshControls = () => {
        if (typeof document === "undefined") {
            return;
        }
        const calmActive = mode === CALM;
        document.querySelectorAll("[data-calm-state]").forEach((node) => {
            node.textContent = calmActive
                ? "Mode Tenang aktif"
                : "Mode Tenang nonaktif";
        });
        document.querySelectorAll("[data-calm-toggle]").forEach((button) => {
            button.setAttribute("aria-pressed", calmActive ? "true" : "false");
            button.classList.toggle("is-active", calmActive);
            button.setAttribute(
                "title",
                calmActive
                    ? "Mode Tenang aktif — klik untuk kembali ke mode ekspresif"
                    : "Aktifkan Mode Tenang untuk menenangkan animasi"
            );
        });
        document.querySelectorAll("[data-calm-indicator]").forEach((dot) => {
            dot.classList.toggle("opacity-80", calmActive);
        });
    };

    const setMode = (nextMode, options = {}) => {
        const { persistChoice = true } = options;
        if (!VALID_MODES.has(nextMode) || nextMode === mode) {
            refreshControls();
            return;
        }
        mode = nextMode;
        apply();
        refreshControls();
        if (persistChoice) {
            persist();
        }
    };

    const toggle = () => {
        setMode(mode === CALM ? EXPRESSIVE : CALM);
    };

    const bindControls = () => {
        if (typeof document === "undefined") {
            return;
        }
        document.querySelectorAll("[data-calm-toggle]").forEach((button) => {
            if (button.dataset.motionBound === "toggle") {
                return;
            }
            button.addEventListener("click", toggle);
            button.dataset.motionBound = "toggle";
        });
    };

    const handlePreferenceChange = (event) => {
        if (!persisted) {
            setMode(event.matches ? CALM : EXPRESSIVE, {
                persistChoice: false,
            });
        }
    };

    const loadInitial = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored && VALID_MODES.has(stored)) {
                mode = stored;
                persisted = true;
            } else if (stored && !VALID_MODES.has(stored)) {
                localStorage.removeItem(STORAGE_KEY);
            }
        } catch (error) {
            console.warn("Unable to load motion mode", error);
        }
    };

    const init = () => {
        if (initialized) {
            bindControls();
            apply();
            refreshControls();
            return;
        }
        loadInitial();
        if (
            typeof window !== "undefined" &&
            typeof window.matchMedia === "function"
        ) {
            mediaQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
            if (!persisted && mediaQuery.matches) {
                mode = CALM;
            }
            if (typeof mediaQuery.addEventListener === "function") {
                mediaQuery.addEventListener("change", handlePreferenceChange);
            } else if (typeof mediaQuery.addListener === "function") {
                mediaQuery.addListener(handlePreferenceChange);
            }
        }
        apply();
        bindControls();
        refreshControls();
        initialized = true;
    };

    const refresh = () => {
        bindControls();
        apply();
        refreshControls();
    };

    return {
        init,
        toggle,
        refresh,
        getState: () => mode,
    };
})();
const TranslationManager = (() => {
    const STORAGE_KEY = "sirw/language";
    const defaultLang = "id";
    const root =
        typeof document !== "undefined" ? document.documentElement : null;
    const body = typeof document !== "undefined" ? document.body : null;
    const throttle = (fn, wait = 50) => {
        let last = 0;
        let timeout;
        return (...args) => {
            const now = Date.now();
            if (now - last >= wait) {
                last = now;
                fn(...args);
                return;
            }
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                last = Date.now();
                fn(...args);
            }, wait - (now - last));
        };
    };
    let safeTopBound = false;
    let languageContainers = [];
    const translations = {
        id: {
            "site.brand_title": "Sistem Informasi RT",
            "site.brand_tagline":
                "Semua layanan warga dalam satu portal transparan.",
            "hero.badge": "Portal Warga Digital",
            "hero.tagline": "Kolaboratif dan Transparan",
            "hero.description":
                "Kelola iuran, agenda, dan informasi lingkungan secara real time. Akses warga dan pengurus berada di satu platform yang aman serta mudah digunakan.",
            "hero.cta_login": "Masuk sebagai Warga",
            "hero.cta_register": "Daftar",
            "hero.benefits.fast.title": "Konfirmasi cepat",
            "hero.benefits.fast.desc":
                "Pembayaran diverifikasi otomatis oleh pengurus dengan notifikasi instan.",
            "hero.benefits.reminder.title": "Pengumuman real time",
            "hero.benefits.reminder.desc":
                "Agenda dan berita terbaru tampil langsung tanpa menunggu pengurus menyebar manual.",
            "hero.benefits.device.title": "Nyaman di semua perangkat",
            "hero.benefits.device.desc":
                "Tampilan responsif yang ringan untuk ponsel, tablet, hingga desktop RT.",
            "hero.scroll_for_more": "Lihat fitur lainnya",
            "hero.scroll_for_more.text": "Scroll untuk lihat fitur",
            "hero.more_info": "Informasi lainnya",
            "hero.prev": "Slide sebelumnya",
            "hero.next": "Slide selanjutnya",
            "hero.indicator_hint": "Geser atau klik untuk melihat",
            "hero.no_slides_title": "Belum ada slider aktif.",
            "hero.no_slides_desc":
                "Tambahkan slider dari panel admin untuk menampilkan informasi terbaru.",
            "hero.empty_title": "Selamat datang di portal warga",
            "hero.empty_desc":
                "Tambahkan slider melalui panel admin untuk menampilkan informasi penting secara visual.",
            "nav.home": "Beranda",
            "nav.about": "Tentang",
            "nav.toggle_open": "Buka navigasi",
            "nav.toggle_close": "Tutup navigasi",
            "nav.agenda": "Agenda",
            "nav.contact": "Kontak",
            "nav.login": "Masuk",
            "nav.register": "Daftar",
            "stats.total_label": "Total Warga",
            "stats.online_suffix": "online saat ini",
            "stats.paid_label": "Pembayaran Bulan Ini",
            "stats.paid_desc": "Kas warga tercatat realtime",
            "stats.outstanding_label": "Tagihan Belum Lunas",
            "stats.outstanding_desc": "Pengingat otomatis siap membantu",
            "stats.remote_label": "Kelola dari Mana Saja",
            "stats.remote_desc": "Portal dapat diakses 24/7",
            "agenda.section_title": "Agenda Terdekat",
            "agenda.section_desc":
                "Tetap terhubung dengan kegiatan warga dan rapat lingkungan.",
            "agenda.view_all": "Lihat semua agenda",
            "agenda.empty": "Belum ada agenda terjadwal.",
            "agenda.location_label": "Lokasi:",
            "agenda.location_tbd": "Akan diinformasikan",
            "experience.kicker": "Pengalaman Warga",
            "experience.title": "Informasi lengkap, Tanpa Ribet",
            "experience.description":
                "Satu Platform Untuk Semua Kebutuhan Administrasi, Semua Masalah Administrasi Dapat Diselesaikan Disini.",
            "experience.benefits.tracking.title": "Status iuran selalu jelas",
            "experience.benefits.tracking.desc":
                "Riwayat pembayaran dan bukti konfirmasi tersaji rapi untuk setiap kepala keluarga.",
            "experience.benefits.announcements.title":
                "Pengumuman tidak terlewat",
            "experience.benefits.announcements.desc":
                "Agenda baru dan pemberitahuan penting otomatis muncul di beranda warga.",
            "experience.benefits.history.title": "Arsip dokumen aman",
            "experience.benefits.history.desc":
                "Notulen, surat keputusan, dan arsip kegiatan tersimpan digital tanpa perlu grup tambahan.",
            "experience.snapshot.title": "Sorotan lingkungan",
            "experience.snapshot.heading": "Portal warga siap pakai",
            "experience.snapshot.status": "Realtime untuk warga",
            "experience.snapshot.cards.overview.title": "Ringkasan iuran",
            "experience.snapshot.cards.overview.desc":
                "Grafik pembayaran membantu pengurus tunggal memastikan kas tetap sehat.",
            "experience.snapshot.cards.events.title": "Agenda & pengingat",
            "experience.snapshot.cards.events.desc":
                "Setiap warga mengetahui jadwal piket, rapat, dan kegiatan dari satu layar.",
            "experience.snapshot.cards.support.title": "Layanan warga",
            "experience.snapshot.cards.support.desc":
                "Form kontak memudahkan warga menyampaikan pertanyaan yang tercatat baik.",
            "value.section_title": "Kenapa memilih platform ini?",
            "value.section_desc":
                "Portal digital SIRW memudahkan kolaborasi antara pengurus dan warga, menghadirkan transparansi keuangan, serta mengirim reminder otomatis agar kegiatan berjalan tertib.",
            "value.card_transparent_title": "Transparan",
            "value.card_transparent_desc":
                "Rekap kas dan arus kas dapat dipantau warga secara realtime.",
            "value.card_responsive_title": "Responsif",
            "value.card_responsive_desc":
                "Tampilan nyaman diakses dari ponsel maupun desktop.",
            "value.card_integrated_title": "Terintegrasi",
            "value.card_integrated_desc":
                "Pengingat email dan pencatatan keuangan menyatu tanpa perlu aplikasi lain.",
            "value.card_secure_title": "Aman",
            "value.card_secure_desc":
                "Hanya warga terdaftar yang bisa memiliki akun dan mengakses data sensitif.",
            "footer.all_rights": "Seluruh hak cipta dilindungi.",
            "footer.address_unset": "Alamat belum diatur",
            "news.section_title": "Kabar Warga",
            "news.section_desc": "Update kegiatan terakhir dari pengurus.",
            "news.empty": "Belum ada berita terbaru.",
            "contact.card_title": "Tanya Pengurus",
            "contact.card_desc":
                "Hubungi pengurus untuk aktivasi akun atau informasi lain.",
            "cta.kicker": "Waktunya Paperless",
            "cta.title": "Siap modernisasi pengelolaan RT/RW Anda?",
            "cta.description":
                "Aktifkan dashboard warga, integrasikan pencatatan kas, dan hadirkan transparansi yang mudah dipahami oleh semua anggota keluarga.",
            "cta.primary": "Daftar gratis sekarang",
            "cta.secondary": "Diskusikan dengan pengurus",
            "contact.btn": "Hubungi Pengurus",
            "contact.email_label": "Email:",
            "contact.phone_label": "Telepon/WA:",
            "contact.address_label": "Alamat:",
            "contact.unavailable": "Belum tersedia",
            "about.heading_prefix": "Tentang",
            "about.default_description":
                "SIRW adalah portal digital yang membantu pengurus dan warga mengelola kegiatan lingkungan secara transparan dan kolaboratif.",
            "about.vision_title": "Visi",
            "about.default_vision":
                "Mewujudkan lingkungan yang sehat, aman, dan transparan dengan dukungan teknologi yang mudah diakses oleh setiap warga.",
            "about.mission_title": "Misi",
            "about.default_mission_1":
                "1. Menyediakan sistem pencatatan iuran dan keuangan yang terbuka.",
            "about.default_mission_2":
                "2. Menyebarkan informasi agenda dan pengumuman secara cepat.",
            "about.default_mission_3":
                "3. Menghadirkan kanal komunikasi terpusat antara warga dan pengurus.",
            "about.managers_title": "Pengurus Utama",
            "about.managers_description":
                "Hubungi pengurus berikut untuk bantuan dan aktivasi akun.",
            "about.no_managers": "Data pengurus belum tersedia.",
            "agenda.page_title": "Agenda Warga",
            "agenda.page_description":
                "Dapatkan informasi kegiatan lingkungan terkini. Login untuk menambahkan pengingat pribadi.",
            "agenda.status_label": "Status:",
            "agenda.page_empty": "Belum ada agenda terjadwal.",
            "contact.page_title": "Hubungi Pengurus",
            "contact.page_description":
                "Gunakan informasi berikut untuk menghubungi admin atau pengurus RT terkait pendaftaran akun dan pertanyaan lainnya.",
            "contact.card_primary_title": "Kontak Utama",
            "contact.service_title": "Jam Pelayanan",
            "contact.default_service_hours":
                "Senin - Sabtu, 08.00 - 17.00 WIB. Pengurus akan merespon pesan Anda maksimal 1x24 jam kerja.",
            "contact.emergency_note":
                "Untuk urusan darurat, harap hubungi ketua RT secara langsung melalui nomor telepon yang tercantum.",
            "contact.form_title": "Formulir Pesan",
            "contact.form_description":
                "Silakan kirim pesan singkat dan pihak pengurus akan menghubungi Anda kembali melalui email.",
            "contact.error_heading": "Terjadi kesalahan:",
            "contact.form_name_placeholder": "Nama Anda",
            "contact.form_email_placeholder": "Email",
            "contact.form_phone_placeholder": "Nomor Telepon (opsional)",
            "contact.form_message_placeholder": "Pesan Anda",
            "contact.form_submit": "Kirim Pesan",
            "language.name.id": "Bahasa Indonesia",
            "language.name.en": "Bahasa Inggris",
        },
        en: {
            "site.brand_title": "RT Information System",
            "site.brand_tagline":
                "All neighbourhood services in one transparent portal.",
            "hero.badge": "Digital Resident Portal",
            "hero.tagline": "Collaborative and Transparent",
            "hero.description":
                "Manage dues, agendas, and neighbourhood information in real time. Residents and administrators work on one secure, easy-to-use platform.",
            "hero.cta_login": "Sign In as Resident",
            "hero.cta_register": "Register",
            "hero.benefits.fast.title": "Fast confirmations",
            "hero.benefits.fast.desc":
                "Payments are verified automatically with instant notifications for administrators.",
            "hero.benefits.reminder.title": "Realtime announcements",
            "hero.benefits.reminder.desc":
                "Latest events and news appear immediately without waiting for manual distribution.",
            "hero.benefits.device.title": "Comfortable everywhere",
            "hero.benefits.device.desc":
                "A lightweight, responsive layout for phones, tablets, and desktop displays.",
            "hero.scroll_for_more": "Discover more features",
            "hero.scroll_for_more.text": "Scroll to explore features",
            "hero.more_info": "More information",
            "hero.prev": "Previous slide",
            "hero.next": "Next slide",
            "hero.indicator_hint": "Swipe or click to explore",
            "hero.no_slides_title": "No slides are active yet.",
            "hero.no_slides_desc":
                "Add slides from the admin panel to showcase the latest information.",
            "hero.empty_title": "Welcome to the resident portal",
            "hero.empty_desc":
                "Add slides from the admin panel to highlight important information visually.",
            "nav.home": "Home",
            "nav.about": "About",
            "nav.toggle_open": "Open navigation",
            "nav.toggle_close": "Close navigation",
            "nav.agenda": "Events",
            "nav.contact": "Contact",
            "nav.login": "Sign In",
            "nav.register": "Register",
            "stats.total_label": "Total Residents",
            "stats.online_suffix": "online now",
            "stats.paid_label": "Payments This Month",
            "stats.paid_desc": "Community funds recorded in real time",
            "stats.outstanding_label": "Outstanding Bills",
            "stats.outstanding_desc":
                "Automatic reminders keep everyone on track",
            "stats.remote_label": "Manage From Anywhere",
            "stats.remote_desc": "Access the portal 24/7",
            "agenda.section_title": "Upcoming Events",
            "agenda.section_desc":
                "Stay connected with neighbourhood activities and meetings.",
            "agenda.view_all": "View all events",
            "agenda.empty": "No events scheduled yet.",
            "agenda.location_label": "Location:",
            "agenda.location_tbd": "To be announced",
            "experience.kicker": "Resident Experience",
            "experience.title": "Complete information, hassle-free",
            "experience.description":
                "One Platform for All Administrative Needs, All Administrative Issues Can Be Resolved Here.",
            "experience.benefits.tracking.title": "Clear dues status",
            "experience.benefits.tracking.desc":
                "Payment history and confirmations stay organized for every household.",
            "experience.benefits.announcements.title":
                "Announcements never missed",
            "experience.benefits.announcements.desc":
                "New events and important notices surface instantly on the resident home page.",
            "experience.benefits.history.title": "Secure digital archive",
            "experience.benefits.history.desc":
                "Minutes, letters, and activity records stay safe without relying on extra chat groups.",
            "experience.snapshot.title": "Neighbourhood snapshot",
            "experience.snapshot.heading": "Resident portal ready to go",
            "experience.snapshot.status": "Realtime for residents",
            "experience.snapshot.cards.overview.title": "Dues overview",
            "experience.snapshot.cards.overview.desc":
                "Payment insights help the sole admin keep community funds healthy.",
            "experience.snapshot.cards.events.title": "Schedules & reminders",
            "experience.snapshot.cards.events.desc":
                "Everyone sees pick-up duties, meetings, and activities from one screen.",
            "experience.snapshot.cards.support.title": "Resident services",
            "experience.snapshot.cards.support.desc":
                "Contact forms capture questions so responses are tracked and organised.",
            "value.section_title": "Why choose this platform?",
            "value.section_desc":
                "The SIRW digital portal makes collaboration between administrators and residents effortless, keeps finances transparent, and sends automated reminders so activities run smoothly.",
            "value.card_transparent_title": "Transparent",
            "value.card_transparent_desc":
                "Residents can monitor cash flow and balances in real time.",
            "value.card_responsive_title": "Responsive",
            "value.card_responsive_desc":
                "Comfortable experience on phones and desktops.",
            "value.card_integrated_title": "Integrated",
            "value.card_integrated_desc":
                "Email reminders and finance tracking live together without extra apps.",
            "value.card_secure_title": "Secure",
            "value.card_secure_desc":
                "Only registered residents can create accounts and access sensitive data.",
            "footer.all_rights": "All rights reserved.",
            "footer.address_unset": "Address not set yet",
            "news.section_title": "Community News",
            "news.section_desc": "Latest updates from the administrators.",
            "news.empty": "No news yet.",
            "contact.card_title": "Contact Administrators",
            "contact.card_desc":
                "Get in touch for account activation or more information.",
            "cta.kicker": "Go Paperless",
            "cta.title": "Ready to modernise your RT/RW operations?",
            "cta.description":
                "Launch the resident dashboard, connect cash tracking, and deliver transparency every family can understand.",
            "cta.primary": "Create your free account",
            "cta.secondary": "Talk with administrators",
            "contact.btn": "Contact Administrators",
            "contact.email_label": "Email:",
            "contact.phone_label": "Phone/WhatsApp:",
            "contact.address_label": "Address:",
            "contact.unavailable": "Not available",
            "about.heading_prefix": "About",
            "about.default_description":
                "SIRW is a digital portal that helps administrators and residents manage neighbourhood activities transparently and collaboratively.",
            "about.vision_title": "Vision",
            "about.default_vision":
                "Create a safe, healthy, and transparent community supported by technology that every resident can easily access.",
            "about.mission_title": "Mission",
            "about.default_mission_1":
                "1. Provide an open system for dues and finance tracking.",
            "about.default_mission_2":
                "2. Share agendas and announcements quickly with residents.",
            "about.default_mission_3":
                "3. Offer a central communication hub between residents and administrators.",
            "about.managers_title": "Key Administrators",
            "about.managers_description":
                "Contact the administrators below for assistance and account activation.",
            "about.no_managers": "Administrator data is not available yet.",
            "agenda.page_title": "Community Events",
            "agenda.page_description":
                "Get the latest neighbourhood activities. Sign in to add personal reminders.",
            "agenda.status_label": "Status:",
            "agenda.page_empty": "No events scheduled yet.",
            "contact.page_title": "Contact Administrators",
            "contact.page_description":
                "Use the following information to reach RT administrators about account registration or other questions.",
            "contact.card_primary_title": "Primary Contact",
            "contact.service_title": "Service Hours",
            "contact.default_service_hours":
                "Monday - Saturday, 08:00 - 17:00 WIB. Administrators will reply within 1×24 working hours.",
            "contact.emergency_note":
                "For urgent matters, please contact the RT head directly using the listed phone number.",
            "contact.form_title": "Contact Form",
            "contact.form_description":
                "Send a short message and the administrators will get back to you via email.",
            "contact.error_heading": "There was an error:",
            "contact.form_name_placeholder": "Your Name",
            "contact.form_email_placeholder": "Email",
            "contact.form_phone_placeholder": "Phone Number (optional)",
            "contact.form_message_placeholder": "Your Message",
            "contact.form_submit": "Send Message",
            "language.name.id": "Indonesian",
            "language.name.en": "English",
        },
    };
    const languageNames = {
        id: "Bahasa Indonesia",
        en: "English",
    };
    const languageCodes = {
        id: "ID",
        en: "EN",
    };
    const languageFlags = {
        id: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="12" fill="#E11D48"/><rect y="12" width="24" height="12" fill="#ffffff"/></svg>',
        en: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" fill="#0A47A9"/><path fill="#ffffff" d="M0 10.5h24v3H0zM10.5 0h3v24h-3z"/><path fill="#ffffff" d="M0 0l9 6H6L0 2.2zM24 0l-9 6h3l6-3.8zM0 24l9-6H6l-6 3.8zM24 24l-9-6h3l6 3.8z"/><path fill="#E11D48" d="M0 11h24v2H0zM11 0h2v24h-2z"/><path fill="#E11D48" d="M0 0l8 5.3H5.5L0 1.9zM24 0l-8 5.3h2.5L24 1.9zM0 24l8-5.3H5.5L0 22.1zM24 24l-8-5.3h2.5L24 22.1z"/></svg>',
    };

    const languageToggleLabels = {
        id: "Pilih bahasa",
        en: "Choose language",
    };
    const recomputeSafeTop = () => {
        if (!root || typeof document === "undefined") return;
        const header =
            document.querySelector("[data-sticky-header]") ||
            document.getElementById("site-header") ||
            document.querySelector("header") ||
            document.querySelector('nav[aria-label="Primary"]');
        const tools = document.querySelector("[data-mobile-tools]");
        const headerHeight = header
            ? Math.round(header.getBoundingClientRect().height)
            : 0;
        const toolsHeight = tools
            ? Math.round(tools.getBoundingClientRect().height)
            : 0;
        const safeTop = Math.max(0, headerHeight + toolsHeight - 20);
        root.style.setProperty("--header-h", `${headerHeight}px`);
        root.style.setProperty("--tools-h", `${toolsHeight}px`);
        root.style.setProperty("--safe-top", `${safeTop}px`);
    };
    const bindSafeTopListeners = () => {
        if (safeTopBound || typeof window === "undefined") return;
        const handler = throttle(recomputeSafeTop, 80);
        window.addEventListener("resize", handler, { passive: true });
        window.addEventListener("orientationchange", handler, {
            passive: true,
        });
        safeTopBound = true;
    };
    const bindToggle = (toggle, menu, hide, show) => {
        const onToggle = (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (menu.classList.contains("hidden")) {
                show();
            } else {
                hide();
            }
        };
        toggle.addEventListener("pointerup", onToggle, { passive: false });
        toggle.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                onToggle(e);
            }
        });
    };
    const hideMenuInstance = (container) => {
        if (!container) return;
        const toggle = container.querySelector("[data-language-toggle]");
        const menu = container.querySelector("[data-language-menu]");
        if (!menu || !toggle) return;
        menu.classList.add("hidden");
        toggle.setAttribute("aria-expanded", "false");
    };
    const hideAllMenus = () => {
        languageContainers.forEach((container) => hideMenuInstance(container));
    };
    let outsideBound = false;
    let outsidePointerHandler = null;
    let outsideClickHandler = null;
    let escapeHandler = null;
    const bindOutsideHandlers = () => {
        if (outsideBound || typeof document === "undefined") return;
        outsidePointerHandler = (event) => {
            if (
                languageContainers.some((container) =>
                    container.contains(event.target)
                )
            ) {
                return;
            }
            hideAllMenus();
        };
        outsideClickHandler = (event) => {
            if (
                languageContainers.some((container) =>
                    container.contains(event.target)
                )
            ) {
                return;
            }
            hideAllMenus();
        };
        escapeHandler = (event) => {
            if (event.key === "Escape") {
                hideAllMenus();
            }
        };
        document.addEventListener("pointerup", outsidePointerHandler, {
            passive: true,
        });
        document.addEventListener("click", outsideClickHandler);
        document.addEventListener("keydown", escapeHandler);
        outsideBound = true;
    };
    const mergeDynamicTranslations = () => {
        if (typeof window === "undefined") return;
        const dynamic = window.SIRW?.dynamicTranslations;
        if (!dynamic || typeof dynamic !== "object") {
            return;
        }
        Object.entries(dynamic).forEach(([lang, entries]) => {
            if (!entries || typeof entries !== "object") {
                return;
            }
            if (!translations[lang]) {
                translations[lang] = {};
            }
            Object.assign(translations[lang], entries);
        });
    };
    const getCsrfToken = () => {
        if (typeof document === "undefined") return undefined;
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? undefined
        );
    };
    const syncServerLocale = (lang) => {
        if (typeof fetch !== "function") return;
        const token = getCsrfToken();
        if (!token) return;
        try {
            fetch("/locale", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": token,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ locale: lang }),
                credentials: "same-origin",
            }).catch(() => {});
        } catch (error) {
            console.warn("Unable to sync locale with server", error);
        }
    };
    const normalizeLanguage = (value) => {
        if (!value || typeof value !== "string") {
            return undefined;
        }
        const lower = value.toLowerCase();
        if (translations[lower]) {
            return lower;
        }
        const [base] = lower.split("-");
        if (translations[base]) {
            return base;
        }
        return undefined;
    };
    const detectBrowserLanguage = () => {
        if (typeof navigator === "undefined") {
            return defaultLang;
        }
        const candidates = [];
        if (Array.isArray(navigator.languages)) {
            candidates.push(...navigator.languages);
        }
        if (navigator.language) {
            candidates.push(navigator.language);
        }
        if (navigator.userLanguage) {
            candidates.push(navigator.userLanguage);
        }
        for (const candidate of candidates) {
            const normalized = normalizeLanguage(candidate);
            if (normalized) {
                return normalized;
            }
        }
        return defaultLang;
    };
    let current = defaultLang;
    const beginLanguageTransition = () => {
        if (!body) return;
        body.classList.remove("language-transition-complete");
        body.classList.add("language-transitioning");
    };
    const endLanguageTransition = () => {
        if (!body) return;
        body.classList.remove("language-transitioning");
        body.classList.add("language-transition-complete");
        window.setTimeout(() => {
            if (!body) return;
            body.classList.remove("language-transition-complete");
        }, 220);
    };
    const hasTranslatableContent = () => {
        if (typeof document === "undefined") return false;
        return Boolean(
            document.querySelector("[data-i18n],[data-i18n-placeholder]")
        );
    };
    const getTranslation = (lang, key) => {
        if (!translations[lang]) return undefined;
        return Object.prototype.hasOwnProperty.call(translations[lang], key)
            ? translations[lang][key]
            : undefined;
    };
    const applyTranslations = () => {
        if (typeof document === "undefined" || !hasTranslatableContent())
            return;
        document.querySelectorAll("[data-i18n]").forEach((node) => {
            const key = node.getAttribute("data-i18n");
            if (!key) return;
            const text =
                getTranslation(current, key) ??
                getTranslation(defaultLang, key);
            if (text === undefined) return;
            const attr = node.dataset.i18nAttr;
            if (attr) {
                node.setAttribute(attr, text);
            } else {
                node.textContent = text;
            }
        });
        document.querySelectorAll("[data-i18n-placeholder]").forEach((node) => {
            const key = node.getAttribute("data-i18n-placeholder");
            if (!key) return;
            const text =
                getTranslation(current, key) ??
                getTranslation(defaultLang, key);
            if (text === undefined) return;
            node.setAttribute("placeholder", text);
        });
    };

    const persist = () => {
        try {
            localStorage.setItem(STORAGE_KEY, current);
        } catch (error) {
            console.warn("Unable to persist language preference", error);
        }
    };
    const updateControls = () => {
        if (typeof document === "undefined") return;
        // Update semua label kode bahasa
        document.querySelectorAll("[data-language-label]").forEach((label) => {
            const code = languageCodes[current] ?? current.toUpperCase();
            label.textContent = code;
        });

        // Update semua bendera
        document
            .querySelectorAll("[data-language-flag]")
            .forEach((flagTarget) => {
                const optionContainer = flagTarget.closest(
                    "[data-language-option]"
                );
                const optionValue = optionContainer
                    ? optionContainer.getAttribute("data-language-option")
                    : null;
                const flagLang = optionValue
                    ? normalizeLanguage(optionValue) ?? optionValue
                    : current;
                const svgMarkup =
                    languageFlags[flagLang] ?? languageFlags[defaultLang] ?? "";
                flagTarget.innerHTML = svgMarkup;
            });

        // Update aria-label semua toggle
        document
            .querySelectorAll("[data-language-toggle]")
            .forEach((toggle) => {
                const readable =
                    languageNames[current] ??
                    languageNames[defaultLang] ??
                    current.toUpperCase();
                const toggleLabel =
                    languageToggleLabels[current] ??
                    languageToggleLabels[defaultLang] ??
                    "Choose language";
                toggle.setAttribute(
                    "aria-label",
                    `${toggleLabel} (${readable})`
                );
            });
        document
            .querySelectorAll("[data-language-option]")
            .forEach((option) => {
                const value = option.getAttribute("data-language-option");
                if (!value) return;
                const normalized = normalizeLanguage(value) ?? value;
                const isActive = normalized === current;
                option.setAttribute(
                    "aria-pressed",
                    isActive ? "true" : "false"
                );
                option.classList.toggle("language-option--active", isActive);
                const codeNode = option.querySelector("[data-language-code]");
                if (codeNode) {
                    codeNode.textContent =
                        languageCodes[normalized] ?? normalized.toUpperCase();
                }
                const nameNode = option.querySelector("[data-language-name]");
                if (nameNode) {
                    nameNode.textContent =
                        languageNames[normalized] ?? normalized;
                }
            });
        document.querySelectorAll("[data-mobile-language]").forEach((node) => {
            node.textContent =
                languageNames[current] ??
                languageNames[defaultLang] ??
                current.toUpperCase();
        });
        const mobileContainer = document.querySelector(
            "[data-mobile-control-stack]"
        );
        if (mobileContainer) {
            mobileContainer.dataset.locale = current;
        }
        if (root) {
            root.setAttribute("lang", current);
            root.dataset.locale = current;
        }
        if (body) {
            body.dataset.locale = current;
        }
    };
    const setLanguage = (lang) => {
        const normalized = normalizeLanguage(lang) ?? defaultLang;
        if (normalized === current) {
            applyTranslations();
            updateControls();
            return;
        }
        beginLanguageTransition();
        current = normalized;
        persist();
        applyTranslations();
        updateControls();
        syncServerLocale(current);
        if (
            typeof window !== "undefined" &&
            typeof window.requestAnimationFrame === "function"
        ) {
            window.requestAnimationFrame(() => {
                window.setTimeout(endLanguageTransition, 150);
            });
        } else {
            endLanguageTransition();
        }
    };

    const loadInitial = () => {
        try {
            const stored =
                typeof localStorage !== "undefined"
                    ? localStorage.getItem(STORAGE_KEY)
                    : null;
            const normalized = normalizeLanguage(stored);
            if (normalized) {
                current = normalized;
                return;
            }
            if (stored) {
                localStorage.removeItem(STORAGE_KEY);
            }
        } catch (error) {
            console.warn("Unable to read language preference", error);
        }
        const serverLocale = root
            ? normalizeLanguage(root.getAttribute("data-locale"))
            : undefined;
        if (serverLocale) {
            current = serverLocale;
            persist();
            return;
        }
        const browserLocale = detectBrowserLanguage();
        current = normalizeLanguage(browserLocale) ?? defaultLang;
        persist();
    };

    const initMenus = () => {
        if (typeof document === "undefined") return;

        languageContainers = Array.from(
            document.querySelectorAll("[data-language-switcher]")
        );
        if (!languageContainers.length) return;

        languageContainers.forEach((container) => {
            const toggle = container.querySelector("[data-language-toggle]");
            const menu = container.querySelector("[data-language-menu]");
            if (!toggle || !menu) return;

            const hide = () => {
                if (!menu.classList.contains("hidden")) {
                    menu.classList.add("hidden");
                }
                toggle.setAttribute("aria-expanded", "false");
            };
            const show = () => {
                hideAllMenus();
                menu.classList.remove("hidden");
                toggle.setAttribute("aria-expanded", "true");
            };

            if (container.dataset.languageBound !== "1") {
                bindToggle(toggle, menu, hide, show);
                menu.addEventListener("click", (e) => e.stopPropagation());
                menu.addEventListener("pointerup", (e) => e.stopPropagation(), {
                    passive: true,
                });
                container.dataset.languageBound = "1";
            }

            container
                .querySelectorAll("[data-language-option]")
                .forEach((opt) => {
                    if (opt.dataset.languageOptionBound === "1") return;
                    const choose = (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        const lang = opt.getAttribute("data-language-option");
                        setLanguage(lang);
                        hide();
                    };
                    opt.addEventListener("pointerup", choose, {
                        passive: false,
                    });
                    opt.addEventListener("keydown", (event) => {
                        if (event.key === "Enter" || event.key === " ") {
                            choose(event);
                        }
                    });
                    opt.dataset.languageOptionBound = "1";
                });

            hide();
        });

        bindOutsideHandlers();
    };

    const init = () => {
        if (!root || typeof document === "undefined") return;
        mergeDynamicTranslations();
        loadInitial();
        applyTranslations();
        updateControls();
        const serverLocale = normalizeLanguage(
            root.getAttribute("data-locale")
        );
        if (serverLocale && serverLocale !== current) {
            syncServerLocale(current);
        }
        initMenus();
        recomputeSafeTop();
        if (typeof window !== "undefined") {
            window.requestAnimationFrame(recomputeSafeTop);
            window.setTimeout(recomputeSafeTop, 150);
        }
        bindSafeTopListeners();
    };

    if (typeof window !== "undefined") {
        window.setLanguage = setLanguage;
        window.initMenus = initMenus;
        window.initMenu = initMenus;
    }
    if (typeof document !== "undefined") {
        document.addEventListener("livewire:load", init);
        document.addEventListener("livewire:navigated", init);
    }

    return {
        init,
        setLanguage,
        getLanguage: () => current,
        initMenus,
        recomputeSafeTop,
    };
})();
const ExperienceManager = (() => {
    const STORAGE_KEY = "sirw/resident-experience";
    const ROOT_CLASS_TEXT = "resident-text-large";
    const ROOT_CLASS_CONTRAST = "resident-contrast-high";
    const BODY_SELECTOR = "[data-resident-root]";
    let eventsBound = false;
    let currentLanguage = null;
    let reloadTimeout = null;

    const scheduleLanguageReload = () => {
        if (typeof window === "undefined") return;
        if (reloadTimeout) {
            window.clearTimeout(reloadTimeout);
        }
        reloadTimeout = window.setTimeout(() => {
            const body = getBody();
            const target = body?.dataset?.profileUrl ?? window.location.href;
            window.location.assign(target);
        }, 650);
    };

    const getBody = () =>
        typeof document !== "undefined"
            ? document.querySelector(BODY_SELECTOR)
            : null;

    const normalize = (value, fallback) => {
        if (typeof value === "string") {
            const trimmed = value.trim();
            if (trimmed.length) {
                return trimmed;
            }
        }
        return fallback;
    };

    const apply = (prefs) => {
        if (typeof document === "undefined") return;
        const body = getBody();
        const root = document.documentElement;
        if (!body || !root || !prefs) return;

        const language = normalize(prefs.language, body.dataset.language || "id");
        const textSize = normalize(prefs.textSize, body.dataset.textSize || "normal");
        const contrast = normalize(prefs.contrast, body.dataset.contrast || "normal");
        const languageChanged =
            currentLanguage !== null && currentLanguage !== language;

        currentLanguage = language;

        body.dataset.language = language;
        body.dataset.textSize = textSize;
        body.dataset.contrast = contrast;

        root.setAttribute("lang", language);
        root.dataset.locale = language;
        root.classList.toggle(ROOT_CLASS_TEXT, textSize === "large");
        root.classList.toggle(ROOT_CLASS_CONTRAST, contrast === "high");

        if (typeof window !== "undefined" && typeof window.setLanguage === "function") {
            window.setLanguage(language);
        }

        if (languageChanged) {
            scheduleLanguageReload();
        }
    };

    const persist = (prefs) => {
        if (typeof localStorage === "undefined") return;
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
        } catch (error) {
            console.warn("Unable to persist experience preferences", error);
        }
    };

    const loadStored = () => {
        if (typeof localStorage === "undefined") return null;
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            console.warn("Unable to read persisted experience preferences", error);
            return null;
        }
    };

    const readDom = () => {
        const body = getBody();
        if (!body) return null;
        return {
            language: body.dataset.language || "id",
            textSize: body.dataset.textSize || "normal",
            contrast: body.dataset.contrast || "normal",
        };
    };

    const handleUpdate = (detail) => {
        const payload = detail?.preferences ?? detail ?? {};
        const domPrefs = readDom() ?? {};
        const merged = {
            ...domPrefs,
            ...payload,
        };
        apply(merged);
        persist(merged);
    };

    const bindEvents = () => {
        if (eventsBound || typeof window === "undefined") return;
        window.addEventListener("resident-preferences-updated", (event) => {
            handleUpdate(event.detail);
        });
        eventsBound = true;
    };

    const init = () => {
        if (typeof document === "undefined") return;
        const stored = loadStored() ?? {};
        const domPrefs = readDom() ?? {};
        const merged = { ...stored, ...domPrefs };
        apply(merged);
        persist(merged);
        bindEvents();
    };

    return {
        init,
        apply,
    };
})();
const SliderManager = (() => {
    const ROOT_SELECTOR = "[data-slider-root]";
    const TRACK_SELECTOR = "[data-slider-track]";
    const SLIDE_SELECTOR = "[data-slider-slide]";
    const PREV_SELECTOR = "[data-slider-prev]";
    const NEXT_SELECTOR = "[data-slider-next]";
    const INDICATOR_SELECTOR = "[data-slider-indicator]";
    const PROGRESS_SELECTOR = "[data-slider-progress]";
    const TOGGLE_SELECTOR = "[data-slider-toggle]";
    const ANNOUNCER_SELECTOR = "[data-slider-announcer]";
    const SLIDE_SUMMARY_SELECTOR = "[data-slider-slide-summary]";

    const toNumber = (value, fallback) => {
        if (typeof value === "number" && Number.isFinite(value)) {
            return value;
        }
        if (typeof value === "string") {
            const parsed = Number(value);
            return Number.isFinite(parsed) ? parsed : fallback;
        }
        return fallback;
    };

    const initSlider = (root) => {
        if (root.dataset.sliderInit === "1") {
            return;
        }
        const track = root.querySelector(TRACK_SELECTOR);
        if (!track) return;
        const slides = Array.from(track.querySelectorAll(SLIDE_SELECTOR));
        if (!slides.length) return;
        const total = slides.length;
        const prevBtn = root.querySelector(PREV_SELECTOR);
        const nextBtn = root.querySelector(NEXT_SELECTOR);
        const indicators = Array.from(
            root.querySelectorAll(INDICATOR_SELECTOR)
        );
        const progressBars = indicators.map((indicator) =>
            indicator.querySelector(PROGRESS_SELECTOR)
        );
        const toggleButton = root.querySelector(TOGGLE_SELECTOR);
        const pauseLabel = toggleButton?.getAttribute("data-slider-pause-label") || "Pause";
        const playLabel = toggleButton?.getAttribute("data-slider-play-label") || "Play";
        const announcer = root.querySelector(ANNOUNCER_SELECTOR);
        const intervalAttr =
            root.getAttribute("data-slider-interval") ??
            track.getAttribute("data-slider-interval");
        const intervalMs = toNumber(intervalAttr, 6500);
        let activeIndex = slides.findIndex(
            (slide) =>
                slide.classList.contains("opacity-100") ||
                slide.dataset.active === "true"
        );
        if (activeIndex < 0) {
            activeIndex = 0;
        }
        let timerId = null;
        let isPaused = false;
        let userPaused = false;
        root.dataset.sliderPaused = "false";
        const reducedMotionQuery =
            typeof window !== "undefined" &&
            typeof window.matchMedia === "function"
                ? window.matchMedia("(prefers-reduced-motion: reduce)")
                : null;

        const modIndex = (index) => {
            if (!total) return 0;
            const remainder = index % total;
            return remainder >= 0 ? remainder : remainder + total;
        };

        const stopTimer = () => {
            if (timerId) {
                window.clearTimeout(timerId);
                timerId = null;
            }
        };

        const updatePauseButton = () => {
            if (!toggleButton) return;
            const playText = playLabel;
            const pauseText = pauseLabel;
            toggleButton.setAttribute("aria-pressed", isPaused ? "true" : "false");
            toggleButton.setAttribute(
                "aria-label",
                `${isPaused ? playLabel : pauseLabel} slider`
            );
            toggleButton.dataset.sliderState = isPaused ? "paused" : "playing";
            const text = toggleButton.querySelector("[data-slider-toggle-text]");
            if (text) {
                text.textContent = isPaused ? playText : pauseText;
            }
            const playIcon = toggleButton.querySelector('[data-slider-icon="play"]');
            if (playIcon) {
                playIcon.classList.toggle("hidden", !isPaused);
            }
            const pauseIcon = toggleButton.querySelector(
                '[data-slider-icon="pause"]'
            );
            if (pauseIcon) {
                pauseIcon.classList.toggle("hidden", isPaused);
            }
        };

        const announceSlide = () => {
            if (!announcer) return;
            const currentSlide = slides[activeIndex];
            if (!currentSlide) return;
            const summaryNode = currentSlide.querySelector(
                SLIDE_SUMMARY_SELECTOR
            );
            if (summaryNode && summaryNode.textContent) {
                announcer.textContent = summaryNode.textContent;
                return;
            }
            const parts = [];
            const title = currentSlide.querySelector("[data-slider-title]");
            const subtitle = currentSlide.querySelector(
                "[data-slider-subtitle]"
            );
            const description = currentSlide.querySelector(
                "[data-slider-description]"
            );
            if (title && title.textContent) parts.push(title.textContent.trim());
            if (subtitle && subtitle.textContent)
                parts.push(subtitle.textContent.trim());
            if (description && description.textContent)
                parts.push(description.textContent.trim());
            announcer.textContent = parts.join(" — ");
        };

        const animateProgress = () => {
            progressBars.forEach((bar, idx) => {
                if (!bar) return;
                bar.style.transition = "none";
                void bar.offsetWidth;
                if (idx === activeIndex && total > 1 && !isPaused) {
                    bar.style.width = "0%";
                    void bar.offsetWidth;
                    bar.style.transition = `width ${intervalMs}ms linear`;
                    bar.style.width = "100%";
                    bar.style.opacity = "1";
                    return;
                }
                bar.style.width = "0%";
                bar.style.opacity = idx === activeIndex ? "0.6" : "0.2";
            });
        };

        const setPaused = (state, { source = "user" } = {}) => {
            if (state === isPaused) {
                updatePauseButton();
                return;
            }
            if (source === "prefers" && state === false && userPaused) {
                updatePauseButton();
                return;
            }
            isPaused = state;
            if (source === "user") {
                userPaused = state;
            }
            root.dataset.sliderPaused = isPaused ? "true" : "false";
            if (isPaused) {
                stopTimer();
            } else {
                startTimer();
            }
            updatePauseButton();
            animateProgress();
        };

        const updateSlides = () => {
            slides.forEach((slide, idx) => {
                const isActive = idx === activeIndex;
                slide.classList.toggle("opacity-100", isActive);
                slide.classList.toggle("translate-x-0", isActive);
                slide.classList.toggle("scale-100", isActive);
                slide.classList.toggle("pointer-events-auto", isActive);
                slide.classList.toggle("z-20", isActive);
                slide.classList.toggle("opacity-0", !isActive);
                slide.classList.toggle("translate-x-6", !isActive);
                slide.classList.toggle("scale-[0.98]", !isActive);
                slide.classList.toggle("pointer-events-none", !isActive);
                slide.classList.toggle("z-10", !isActive);
                slide.setAttribute("aria-hidden", isActive ? "false" : "true");
                slide.dataset.active = isActive ? "true" : "false";
            });
        };

        const updateIndicators = () => {
            indicators.forEach((indicator, idx) => {
                const isActive = idx === activeIndex;
                indicator.dataset.active = isActive ? "true" : "false";
                indicator.setAttribute("aria-pressed", isActive ? "true" : "false");
                indicator.setAttribute(
                    "aria-label",
                    `Slide ${idx + 1} dari ${total}`
                );
                indicator.setAttribute("role", "button");
                indicator.setAttribute(
                    "aria-current",
                    isActive ? "true" : "false"
                );
            });
        };

        const startTimer = () => {
            stopTimer();
            if (
                isPaused ||
                total <= 1 ||
                !Number.isFinite(intervalMs) ||
                intervalMs <= 0
            ) {
                return;
            }
            timerId = window.setTimeout(() => {
                timerId = null;
                goTo(activeIndex + 1);
            }, intervalMs);
        };

        const goTo = (index) => {
            if (!total) return;
            stopTimer();
            activeIndex = modIndex(index);
            updateSlides();
            updateIndicators();
            animateProgress();
            announceSlide();
            startTimer();
        };

        if (prevBtn) {
            prevBtn.addEventListener("click", () => goTo(activeIndex - 1));
            if (total <= 1) {
                prevBtn.setAttribute("aria-hidden", "true");
                prevBtn.setAttribute("tabindex", "-1");
            }
        }
        if (nextBtn) {
            nextBtn.addEventListener("click", () => goTo(activeIndex + 1));
            if (total <= 1) {
                nextBtn.setAttribute("aria-hidden", "true");
                nextBtn.setAttribute("tabindex", "-1");
            }
        }
        indicators.forEach((indicator, idx) => {
            indicator.addEventListener("click", () => goTo(idx));
            indicator.addEventListener("keydown", (event) => {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    goTo(idx);
                }
            });
            if (!indicator.hasAttribute("tabindex")) {
                indicator.setAttribute("tabindex", "0");
            }
        });

        const pause = () => stopTimer();
        const resume = () => {
            if (!root.matches(":focus-within") && !isPaused) {
                startTimer();
            }
        };

        root.addEventListener("mouseenter", pause);
        root.addEventListener("mouseleave", resume);
        root.addEventListener("focusin", pause);
        root.addEventListener("focusout", (event) => {
            if (!root.contains(event.relatedTarget)) {
                resume();
            }
        });

        if (toggleButton) {
            toggleButton.addEventListener("click", () =>
                setPaused(!isPaused, { source: "user" })
            );
            toggleButton.addEventListener("keydown", (event) => {
                if (event.key === " " || event.key === "Enter") {
                    event.preventDefault();
                    setPaused(!isPaused, { source: "user" });
                }
            });
        }

        if (reducedMotionQuery) {
            const applyPreference = (matches) => {
                if (matches) {
                    setPaused(true, { source: "prefers" });
                } else if (!userPaused) {
                    setPaused(false, { source: "prefers" });
                }
            };
            applyPreference(reducedMotionQuery.matches);
            if (typeof reducedMotionQuery.addEventListener === "function") {
                reducedMotionQuery.addEventListener("change", (event) =>
                    applyPreference(event.matches)
                );
            } else if (typeof reducedMotionQuery.addListener === "function") {
                reducedMotionQuery.addListener((event) =>
                    applyPreference(event.matches)
                );
            }
        }

        updateSlides();
        updateIndicators();
        animateProgress();
        announceSlide();
        startTimer();

        updatePauseButton();
        root.dataset.sliderInit = "1";
    };

    const init = () => {
        if (typeof document === "undefined") return;
        const roots = Array.from(document.querySelectorAll(ROOT_SELECTOR));
        roots.forEach(initSlider);
    };

    return {
        init,
    };
})();

const SidebarManager = (() => {
    const SIDEBAR_SELECTOR = "[data-sidebar]";
    const TOGGLE_SELECTOR = "[data-sidebar-toggle]";
    const parseClasses = (value, fallback = []) =>
        value ? value.split(/\s+/).filter(Boolean) : fallback;
    const mediaQuery =
        typeof window !== "undefined" && typeof window.matchMedia === "function"
            ? window.matchMedia("(min-width: 1024px)")
            : null;

    const getContent = (sidebar) => {
        const target = sidebar.id;
        if (!target) return null;
        return document.querySelector(`[data-sidebar-content="${target}"]`);
    };

    const getOverlay = (sidebar) => {
        const target = sidebar.id;
        if (!target) return null;
        return document.querySelector(`[data-sidebar-overlay="${target}"]`);
    };

    const setState = (sidebar, open) => {
        const collapseClasses = parseClasses(
            sidebar.getAttribute("data-sidebar-collapse-class"),
            ["-translate-x-full"]
        );
        const expandClasses = parseClasses(
            sidebar.getAttribute("data-sidebar-expand-class"),
            ["translate-x-0"]
        );
        collapseClasses.forEach((cls) => {
            if (!cls) return;
            if (open) {
                sidebar.classList.remove(cls);
            } else {
                sidebar.classList.add(cls);
            }
        });
        expandClasses.forEach((cls) => {
            if (!cls) return;
            if (open) {
                sidebar.classList.add(cls);
            } else {
                sidebar.classList.remove(cls);
            }
        });
        sidebar.dataset.sidebarState = open ? "open" : "closed";

        const content = getContent(sidebar);
        if (content) {
            const openClasses = parseClasses(
                content.getAttribute("data-sidebar-open-class")
            );
            const closedClasses = parseClasses(
                content.getAttribute("data-sidebar-closed-class")
            );
            openClasses.forEach((cls) => content.classList.toggle(cls, open));
            closedClasses.forEach((cls) =>
                content.classList.toggle(cls, !open)
            );
        }

        const overlay = getOverlay(sidebar);
        if (overlay) {
            overlay.classList.toggle("opacity-100", open);
            overlay.classList.toggle("opacity-0", !open);
            overlay.classList.toggle("pointer-events-auto", open);
            overlay.classList.toggle("pointer-events-none", !open);
        }

        document
            .querySelectorAll(
                `${TOGGLE_SELECTOR}[data-sidebar-target="${sidebar.id}"]`
            )
            .forEach((toggle) => {
                toggle.setAttribute("aria-expanded", open ? "true" : "false");
            });
    };

    const handleToggle = (sidebar) => {
        const nextState = sidebar.dataset.sidebarState !== "open";
        setState(sidebar, nextState);
        sidebar.dataset.sidebarManual = "true";
    };

    const initialiseSidebar = (sidebar) => {
        const shouldOpen = mediaQuery
            ? mediaQuery.matches
            : window.innerWidth >= 1024;
        setState(sidebar, shouldOpen);
        const overlay = getOverlay(sidebar);
        if (overlay) {
            overlay.addEventListener("click", () => setState(sidebar, false));
        }
    };

    const registerToggles = (sidebars) => {
        document.querySelectorAll(TOGGLE_SELECTOR).forEach((toggle) => {
            const targetId = toggle.getAttribute("data-sidebar-target");
            if (!targetId) return;
            const sidebar = sidebars.find(
                (candidate) => candidate.id === targetId
            );
            if (!sidebar) return;
            toggle.addEventListener("click", () => handleToggle(sidebar));
        });
    };

    const bindKeyboardClose = (sidebars) => {
        document.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") return;
            const isMobile = window.innerWidth < 1024;
            if (!isMobile) return;
            sidebars.forEach((sidebar) => {
                if (sidebar.dataset.sidebarState === "open") {
                    setState(sidebar, false);
                }
            });
        });
    };

    const bindMediaQuery = (sidebars) => {
        if (!mediaQuery) return;
        mediaQuery.addEventListener("change", (event) => {
            if (event.matches) {
                sidebars.forEach((sidebar) => {
                    if (
                        sidebar.dataset.sidebarState === "closed" &&
                        sidebar.dataset.sidebarManual === "true"
                    ) {
                        setState(sidebar, false);
                    } else {
                        setState(sidebar, true);
                    }
                });
            } else {
                sidebars.forEach((sidebar) => {
                    sidebar.dataset.sidebarManual = "";
                    setState(sidebar, false);
                });
            }
        });
    };

    const init = () => {
        if (typeof document === "undefined") return;
        const sidebars = Array.from(
            document.querySelectorAll(SIDEBAR_SELECTOR)
        );
        if (!sidebars.length) return;
        sidebars.forEach((sidebar) => initialiseSidebar(sidebar));
        registerToggles(sidebars);
        bindKeyboardClose(sidebars);
        bindMediaQuery(sidebars);
    };

    return {
        init,
        setState,
    };
})();

const ControlCenter = (() => {
    const TOGGLE_SELECTOR = "[data-control-center-toggle]";
    const PANEL_SELECTOR = "[data-control-center-panel]";
    const ROOT_SELECTOR = "[data-control-center-root]";
    const CLOSE_SELECTOR = "[data-control-center-close]";
    const TARGET_ATTRIBUTE = "data-control-center-target";
    const OPEN_CLASSES = ["pointer-events-auto", "opacity-100", "scale-100"];
    const CLOSED_CLASSES = ["pointer-events-none", "opacity-0", "scale-95"];

    let activePanel = null;
    let activeToggle = null;
    let lastFocus = null;
    let outsideHandlerBound = false;

    const getPanelForToggle = (toggle) => {
        if (typeof document === "undefined" || !toggle) return null;
        const explicitId =
            toggle.getAttribute(TARGET_ATTRIBUTE) ||
            toggle.getAttribute("aria-controls");
        if (explicitId) {
            const panel = document.getElementById(explicitId);
            if (panel) return panel;
        }
        const root = toggle.closest(ROOT_SELECTOR);
        if (root) {
            const panel = root.querySelector(PANEL_SELECTOR);
            if (panel) return panel;
        }
        return document.querySelector(PANEL_SELECTOR);
    };

    const applyClasses = (panel, open) => {
        if (!panel) return;
        panel.dataset.open = open ? "true" : "false";
        panel.setAttribute("aria-hidden", open ? "false" : "true");
        CLOSED_CLASSES.forEach((cls) => panel.classList.toggle(cls, !open));
        OPEN_CLASSES.forEach((cls) => panel.classList.toggle(cls, open));
    };

    const focusFirstInteractive = (panel) => {
        if (!panel) return;
        const focusableSelectors =
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        const focusables = Array.from(
            panel.querySelectorAll(focusableSelectors)
        ).filter(
            (node) =>
                !node.hasAttribute("disabled") &&
                node.getAttribute("aria-hidden") !== "true" &&
                node.offsetParent !== null
        );
        if (focusables.length) {
            focusables[0].focus({ preventScroll: true });
        } else {
            panel.focus({ preventScroll: true });
        }
    };

    const handleOutsideClick = (event) => {
        if (!activePanel) return;
        if (
            activePanel.contains(event.target) ||
            (activeToggle && activeToggle.contains(event.target))
        ) {
            return;
        }
        close();
    };

    const handleKeydown = (event) => {
        if (event.key === "Escape") {
            if (activePanel) {
                event.preventDefault();
                close();
            }
        }
    };

    const bindGlobalHandlers = () => {
        if (outsideHandlerBound || typeof document === "undefined") return;
        document.addEventListener("mousedown", handleOutsideClick, true);
        document.addEventListener("touchstart", handleOutsideClick, true);
        document.addEventListener("keydown", handleKeydown);
        outsideHandlerBound = true;
    };

    const unbindGlobalHandlers = () => {
        if (!outsideHandlerBound || typeof document === "undefined") return;
        document.removeEventListener("mousedown", handleOutsideClick, true);
        document.removeEventListener("touchstart", handleOutsideClick, true);
        document.removeEventListener("keydown", handleKeydown);
        outsideHandlerBound = false;
    };

    const open = (panel, toggle) => {
        if (!panel) return;
        if (activePanel && panel === activePanel) {
            close();
            return;
        }
        close();
        activePanel = panel;
        activeToggle = toggle || null;
        lastFocus = document.activeElement;
        if (activeToggle) {
            activeToggle.setAttribute("aria-expanded", "true");
        }
        applyClasses(panel, true);
        panel.dataset.controlCenterActive = "true";
        bindGlobalHandlers();
        focusFirstInteractive(panel);
    };

    function close() {
        if (!activePanel) return;
        applyClasses(activePanel, false);
        activePanel.dataset.controlCenterActive = "false";
        if (activeToggle) {
            activeToggle.setAttribute("aria-expanded", "false");
        }
        const returnFocus = lastFocus;
        activePanel = null;
        activeToggle = null;
        lastFocus = null;
        unbindGlobalHandlers();
        if (returnFocus && typeof returnFocus.focus === "function") {
            returnFocus.focus({ preventScroll: true });
        }
    }

    const bindToggle = (toggle) => {
        if (toggle.dataset.controlCenterBound === "1") return;
        toggle.addEventListener("click", (event) => {
            event.preventDefault();
            const panel = getPanelForToggle(toggle);
            if (!panel) return;
            if (panel === activePanel) {
                close();
            } else {
                open(panel, toggle);
            }
        });
        toggle.dataset.controlCenterBound = "1";
    };

    const bindPanel = (panel) => {
        if (!panel) return;
        panel.setAttribute("aria-hidden", "true");
        applyClasses(panel, false);
        const closeButton = panel.querySelector(CLOSE_SELECTOR);
        if (closeButton && closeButton.dataset.controlCenterBound !== "1") {
            closeButton.addEventListener("click", (event) => {
                event.preventDefault();
                close();
            });
            closeButton.dataset.controlCenterBound = "1";
        }
    };

    const init = () => {
        if (typeof document === "undefined") return;
        const panels = Array.from(
            document.querySelectorAll(PANEL_SELECTOR)
        );
        panels.forEach(bindPanel);
        document
            .querySelectorAll(TOGGLE_SELECTOR)
            .forEach((toggle) => bindToggle(toggle));
    };

    const refresh = () => {
        init();
    };

    const openById = (id) => {
        if (typeof document === "undefined") return;
        const panel = id
            ? document.getElementById(id)
            : document.querySelector(PANEL_SELECTOR);
        if (!panel) return;
        const toggle =
            document.querySelector(
                `${TOGGLE_SELECTOR}[aria-controls="${panel.id}"]`
            ) ||
            document.querySelector(
                `${TOGGLE_SELECTOR}[${TARGET_ATTRIBUTE}="${panel.id}"]`
            );
        open(panel, toggle || null);
    };

    return {
        init,
        refresh,
        open: openById,
        close,
    };
})();

const CommandPalette = (() => {
    const ROOT_SELECTOR = "[data-command-root]";
    const OPEN_TRIGGER_SELECTOR = "[data-command-open]";
    const INPUT_SELECTOR = "[data-command-input]";
    const ITEM_SELECTOR = "[data-command-item]";
    const GROUP_SELECTOR = "[data-command-group]";
    const EMPTY_SELECTOR = "[data-command-empty]";
    const BACKDROP_SELECTOR = "[data-command-backdrop]";
    const PANEL_SELECTOR = "[data-command-panel]";
    const FORM_SELECTOR = "[data-command-form]";
    const ACTIVE_CLASS = "command-item__button--active";

    let root = null;
    let input = null;
    let items = [];
    let groups = [];
    let emptyState = null;
    let backdrop = null;
    let panel = null;
    let form = null;
    let isOpen = false;
    let activeIndex = -1;
    let previousHtmlOverflow = "";
    let previousBodyOverflow = "";

    const lockScroll = () => {
        if (typeof document === "undefined") return;
        return;
    };

    const unlockScroll = () => {
        if (typeof document === "undefined") return;
        return;
    };

    const collectElements = () => {
        if (typeof document === "undefined") return false;
        root = document.querySelector(ROOT_SELECTOR);
        if (!root) return false;
        input = root.querySelector(INPUT_SELECTOR);
        items = Array.from(root.querySelectorAll(ITEM_SELECTOR));
        groups = Array.from(root.querySelectorAll(GROUP_SELECTOR));
        emptyState = root.querySelector(EMPTY_SELECTOR);
        backdrop = root.querySelector(BACKDROP_SELECTOR);
        panel = root.querySelector(PANEL_SELECTOR);
        form = root.querySelector(FORM_SELECTOR);
        items.forEach((item) => {
            if (item.dataset.commandItemBound === "1") return;
            const button =
                item.matches("button") ? item : item.querySelector("button");
            if (button) {
                button.addEventListener("click", (event) => {
                    event.preventDefault();
                    runItem(item);
                });
            }
            item.dataset.commandItemBound = "1";
        });
        return true;
    };

    const visibleItems = () =>
        items.filter((item) => !item.classList.contains("hidden"));

    const updateGroupVisibility = () => {
        groups.forEach((group) => {
            const hasVisible = Array.from(
                group.querySelectorAll(ITEM_SELECTOR)
            ).some((item) => !item.classList.contains("hidden"));
            group.classList.toggle("hidden", !hasVisible);
        });
    };

    const applyActiveState = () => {
        const visibles = visibleItems();
        visibles.forEach((item, index) => {
            const button =
                item.matches("button") ? item : item.querySelector("button");
            const active = index === activeIndex;
            item.classList.toggle("is-active", active);
            if (button) {
                button.classList.toggle(ACTIVE_CLASS, active);
                button.setAttribute("aria-selected", active ? "true" : "false");
            }
        });
    };

    const setActiveIndex = (index) => {
        const visibles = visibleItems();
        if (!visibles.length) {
            activeIndex = -1;
            applyActiveState();
            return;
        }
        const safeIndex =
            (index % visibles.length + visibles.length) % visibles.length;
        activeIndex = safeIndex;
        applyActiveState();
        const activeItem = visibles[safeIndex];
        if (activeItem) {
            activeItem.scrollIntoView({
                block: "nearest",
                inline: "nearest",
            });
        }
    };

    const filterItems = (query) => {
        const normalized = query.trim().toLowerCase();
        let visibleCount = 0;
        items.forEach((item) => {
            const keywords =
                item.dataset.commandKeywords?.toLowerCase() ?? "";
            const text = item.textContent?.toLowerCase() ?? "";
            const matches =
                !normalized ||
                keywords.includes(normalized) ||
                text.includes(normalized);
            item.classList.toggle("hidden", !matches);
            if (matches) {
                visibleCount += 1;
            }
        });
        updateGroupVisibility();
        if (emptyState) {
            emptyState.classList.toggle("hidden", visibleCount > 0);
        }
        if (visibleCount > 0) {
            setActiveIndex(0);
        } else {
            activeIndex = -1;
            applyActiveState();
        }
    };

    const setRootState = (open) => {
        if (!root || !panel || !backdrop) return;
        root.dataset.commandOpen = open ? "true" : "false";
        root.classList.toggle("pointer-events-none", !open);
        root.classList.toggle("pointer-events-auto", open);
        root.style.visibility = open ? "visible" : "hidden";
        root.style.display = open ? "block" : "none";
        root.classList.toggle("opacity-0", !open);
        root.classList.toggle("opacity-100", open);
        backdrop.classList.toggle("opacity-0", !open);
        backdrop.classList.toggle("opacity-100", open);
        panel.classList.toggle("opacity-0", !open);
        panel.classList.toggle("opacity-100", open);
        panel.classList.toggle("scale-95", !open);
        panel.classList.toggle("scale-100", open);
        root.setAttribute("aria-hidden", open ? "false" : "true");
    };

    const open = (event) => {
        if (event) event.preventDefault();
        if (!collectElements()) return;
        if (isOpen) {
            if (input) {
                input.focus({ preventScroll: true });
                input.select();
            }
            return;
        }
        isOpen = true;
        setRootState(true);
        lockScroll();
        const query = input?.value ?? "";
        filterItems(query);
        window.setTimeout(() => {
            if (input) {
                input.focus({ preventScroll: true });
                input.select();
            }
        }, 0);
    };

    const close = () => {
        if (!isOpen) return;
        isOpen = false;
        setRootState(false);
        unlockScroll();
        activeIndex = -1;
        applyActiveState();
    };

    const runAction = (item) => {
        const action = item.dataset.commandAction;
        if (!action) return;
        switch (action) {
            case "cycle-theme": {
                const state = ThemeManager.getState();
                const current = state.mode ?? "auto";
                const sequence = ["light", "dark", "auto"];
                const next =
                    sequence[(sequence.indexOf(current) + 1) % sequence.length];
                ThemeManager.setMode(next);
                break;
            }
            case "toggle-calm": {
                MotionManager.toggle();
                break;
            }
            case "open-profile": {
                const url = item.dataset.commandUrl;
                if (url) {
                    window.location.assign(url);
                }
                break;
            }
            case "focus-control-center": {
                const targetId = item.getAttribute("data-command-target");
                ControlCenter.open(targetId);
                break;
            }
            case "open-copilot": {
                CopilotManager.open();
                break;
            }
            default:
                break;
        }
    };

    const runItem = (item) => {
        if (!item) return;
        const type = item.dataset.commandType || "route";
        if (type === "action") {
            runAction(item);
        } else {
            const url = item.dataset.commandUrl;
            if (url) {
                window.location.assign(url);
            } else if (form) {
                form.submit();
            }
        }
        close();
    };

    const runActiveItem = () => {
        const visibles = visibleItems();
        if (activeIndex < 0 || activeIndex >= visibles.length) return false;
        runItem(visibles[activeIndex]);
        return true;
    };

    const handleInputKeydown = (event) => {
        if (event.key === "ArrowDown") {
            event.preventDefault();
            setActiveIndex(activeIndex + 1);
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            setActiveIndex(activeIndex - 1);
        } else if (event.key === "Enter") {
            const handled = runActiveItem();
            if (handled) {
                event.preventDefault();
            }
        } else if (event.key === "Escape") {
            event.preventDefault();
            close();
        }
    };

    const handleGlobalKeydown = (event) => {
        const isMac =
            typeof navigator !== "undefined" &&
            navigator.platform.toUpperCase().indexOf("MAC") >= 0;
        const metaPressed = isMac ? event.metaKey : event.ctrlKey;
        if (metaPressed && event.key.toLowerCase() === "k") {
            event.preventDefault();
            open();
        } else if (isOpen && event.key === "Escape") {
            event.preventDefault();
            close();
        }
    };

    const bindTriggers = () => {
        if (typeof document === "undefined") return;
        document
            .querySelectorAll(OPEN_TRIGGER_SELECTOR)
            .forEach((trigger) => {
                if (trigger.dataset.commandTriggerBound === "1") return;
                trigger.addEventListener("click", open);
                trigger.dataset.commandTriggerBound = "1";
            });
    };

    const bindInternalEvents = () => {
        if (!root) return;
        if (input && input.dataset.commandInputBound !== "1") {
            input.addEventListener("input", () => {
                filterItems(input.value);
            });
            input.addEventListener("keydown", handleInputKeydown);
            input.dataset.commandInputBound = "1";
        }
        if (backdrop && backdrop.dataset.commandBackdropBound !== "1") {
            backdrop.addEventListener("click", close);
            backdrop.dataset.commandBackdropBound = "1";
        }
        if (panel && panel.dataset.commandPanelBound !== "1") {
            panel.addEventListener("keydown", (event) => {
                if (event.key === "Tab" && isOpen) {
                    const focusables = panel.querySelectorAll(
                        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                    );
                    if (!focusables.length) {
                        event.preventDefault();
                        input?.focus({ preventScroll: true });
                    }
                }
            });
            panel.dataset.commandPanelBound = "1";
        }
        if (form && form.dataset.commandFormBound !== "1") {
            form.addEventListener("submit", () => {
                close();
            });
            form.dataset.commandFormBound = "1";
        }
    };

    const init = () => {
        if (!collectElements()) return;
        bindTriggers();
        bindInternalEvents();
        document.addEventListener("keydown", handleGlobalKeydown);
    };

    const refresh = () => {
        collectElements();
        bindTriggers();
        bindInternalEvents();
    };

    return {
        init,
        refresh,
        open,
        close,
    };
})();

const CopilotManager = (() => {
    const ROOT_SELECTOR = "[data-copilot-root]";
    const PANEL_SELECTOR = "[data-copilot-panel]";
    const BACKDROP_SELECTOR = "[data-copilot-backdrop]";
    const OPEN_TRIGGER_SELECTOR = "[data-copilot-open]";
    const CLOSE_TRIGGER_SELECTOR = "[data-copilot-close]";
    const ACTION_SELECTOR = "[data-copilot-action]";

    let root = null;
    let panel = null;
    let backdrop = null;
    let isOpen = false;
    let livewireHooked = false;
    let livewireListenersBound = false;
    let mutationObserver = null;

    const ensureElements = () => {
        if (typeof document === "undefined") return false;
        if (!root || !document.contains(root)) {
            root = document.querySelector(ROOT_SELECTOR);
        }
        if (!root) return false;
        if (!panel || !root.contains(panel)) {
            panel = root.querySelector(PANEL_SELECTOR);
        }
        if (!backdrop || !root.contains(backdrop)) {
            backdrop = root.querySelector(BACKDROP_SELECTOR);
        }
        return !!panel;
    };

    const onLivewireReady = () => {
        if (
            livewireHooked ||
            typeof window === "undefined" ||
            !window.Livewire ||
            typeof window.Livewire.hook !== "function"
        ) {
            return;
        }

        // Re-sync panel state after Livewire DOM updates so it stays open.
        window.Livewire.hook("message.processed", () => {
            ensureElements();
            bindActions();
            if (isOpen) {
                syncOpenState();
            }
        });

        ensureElements();
        bindActions();
        if (isOpen) {
            syncOpenState();
        }

        livewireHooked = true;
    };

    const ensureLivewireListeners = () => {
        if (livewireListenersBound || typeof document === "undefined") return;

        // Livewire might load after this script, so wait for its init events.
        document.addEventListener("livewire:init", onLivewireReady);
        document.addEventListener("livewire:load", onLivewireReady);

        livewireListenersBound = true;
    };

    const ensureMutationObserver = () => {
        if (mutationObserver || typeof MutationObserver === "undefined") return;
        if (!ensureElements()) return;

        mutationObserver = new MutationObserver(() => {
            ensureElements();
            bindActions();
            if (isOpen) {
                syncOpenState();
            }
        });

        mutationObserver.observe(root, {
            childList: true,
            subtree: true,
        });
    };

    const disconnectMutationObserver = () => {
        if (!mutationObserver) return;
        mutationObserver.disconnect();
        mutationObserver = null;
    };

    const toggleClasses = (open) => {
        if (!ensureElements()) return;
        root.dataset.open = open ? "true" : "false";
        root.setAttribute("aria-hidden", open ? "false" : "true");
        root.classList.toggle("pointer-events-none", !open);
        root.classList.toggle("pointer-events-auto", open);
        root.style.visibility = open ? "visible" : "hidden";
        root.style.display = open ? "block" : "none";
        root.classList.toggle("opacity-0", !open);
        root.classList.toggle("opacity-100", open);
        if (backdrop) {
            backdrop.classList.toggle("opacity-0", !open);
            backdrop.classList.toggle("opacity-100", open);
        }
        if (panel) {
            panel.classList.toggle("opacity-0", !open);
            panel.classList.toggle("translate-x-10", !open);
            panel.classList.toggle("translate-x-0", open);
        }
    };

    const syncOpenState = () => {
        if (!ensureElements()) return;
        toggleClasses(isOpen);
    };

    const focusPanel = () => {
        if (!panel) return;
        const focusableSelectors =
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
        const focusables = Array.from(
            panel.querySelectorAll(focusableSelectors)
        ).filter(
            (node) =>
                !node.hasAttribute("disabled") &&
                node.getAttribute("aria-hidden") !== "true" &&
                node.offsetParent !== null
        );
        if (focusables.length) {
            focusables[0].focus({ preventScroll: true });
        } else {
            panel.focus({ preventScroll: true });
        }
    };

    const refreshData = () => {
        if (typeof window === "undefined" || !window.Livewire) return;
        window.Livewire.dispatch("copilot:refresh");
    };

    const open = () => {
        ensureLivewireListeners();
        onLivewireReady();
        ensureMutationObserver();
        if (isOpen) {
            refreshData();
            return;
        }
        isOpen = true;
        root?.setAttribute("data-open", "true");
        root?.setAttribute("aria-hidden", "false");
        toggleClasses(true);
        refreshData();
        window.setTimeout(focusPanel, 16);
        document.addEventListener("keydown", handleKeydown);
    };

    const close = () => {
        if (!isOpen) return;
        isOpen = false;
        root?.setAttribute("data-open", "false");
        root?.setAttribute("aria-hidden", "true");
        toggleClasses(false);
        document.removeEventListener("keydown", handleKeydown);
        disconnectMutationObserver();
    };

    const handleKeydown = (event) => {
        if (event.key === "Escape") {
            event.preventDefault();
            close();
        }
    };

    const handleAction = (event) => {
        const button = event.currentTarget;
        if (!button) return;
        const type = button.getAttribute("data-copilot-action") || "route";
        const payload =
            button.getAttribute("data-copilot-action-payload") || "";
        const actionId = button.getAttribute("data-copilot-action-id");
        const metaRoute =
            button.dataset.copilotMetaRoute ||
            button.dataset.metaRoute ||
            button.getAttribute("data-copilot-meta-route") ||
            "";

        if (type === "route" && payload) {
            close();
            window.location.assign(payload);
            return;
        }

        if (type === "command") {
            if (payload === "copilot:send-bill-reminders") {
                close();
                if (metaRoute) {
                    window.location.assign(metaRoute);
                }
                return;
            }
            if (typeof window !== "undefined" && window.Livewire && actionId) {
                window.Livewire.dispatch("copilot:execute-action", {
                    actionId,
                });
            }
            return;
        }

        close();
    };

    const bindActions = () => {
        if (!ensureElements()) return;
        panel
            .querySelectorAll(ACTION_SELECTOR)
            .forEach((button) => {
                if (button.dataset.copilotActionBound === "1") return;
                button.addEventListener("click", handleAction);
                button.dataset.copilotActionBound = "1";
            });
    };

    const bindTriggers = () => {
        if (typeof document === "undefined") return;
        document.querySelectorAll(OPEN_TRIGGER_SELECTOR).forEach((btn) => {
            if (btn.dataset.copilotBound === "1") return;
            btn.addEventListener("click", (event) => {
                event.preventDefault();
                open();
            });
            btn.dataset.copilotBound = "1";
        });
        document.querySelectorAll(CLOSE_TRIGGER_SELECTOR).forEach((btn) => {
            if (btn.dataset.copilotBound === "1") return;
            btn.addEventListener("click", (event) => {
                event.preventDefault();
                close();
            });
            btn.dataset.copilotBound = "1";
        });
        if (ensureElements() && backdrop) {
            backdrop.addEventListener("click", close);
        }
    };

    const init = () => {
        ensureLivewireListeners();
        onLivewireReady();
        ensureMutationObserver();
        if (!ensureElements()) return;
        toggleClasses(isOpen);
        bindTriggers();
        bindActions();
        window.addEventListener("copilot-refreshed", () => {
            bindActions();
            ensureElements();
            syncOpenState();
        });
        window.addEventListener("copilot-action-executed", (event) => {
            const detail = event.detail || {};
            if (detail.type === "route" && detail.payload) {
                close();
                window.location.assign(detail.payload);
            }
        });
    };

    const refresh = () => {
        ensureLivewireListeners();
        onLivewireReady();
        ensureMutationObserver();
        ensureElements();
        bindActions();
        bindTriggers();
    };

    return {
        init,
        refresh,
        open,
        close,
    };
})();

const AdminClock = (() => {
    const SELECTOR = "[data-admin-clock]";
    const timers = new Map();

    const getLocale = (node) =>
        node.getAttribute("data-clock-locale") ||
        (typeof navigator !== "undefined" ? navigator.language : "id-ID");
    const getTimezone = (node) => {
        const attr = node.getAttribute("data-clock-timezone");
        if (attr && attr.toLowerCase() !== "local") {
            return attr;
        }
        if (typeof Intl !== "undefined") {
            const options = Intl.DateTimeFormat().resolvedOptions();
            if (options && options.timeZone) {
                return options.timeZone;
            }
        }
        return "UTC";
    };

    const formatDate = (date, locale, timezone) => {
        const dateFormatter = new Intl.DateTimeFormat(locale, {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
            timeZone: timezone,
        });
        const timeFormatter = new Intl.DateTimeFormat(locale, {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false,
            timeZone: timezone,
        });
        return `${dateFormatter.format(date)} • ${timeFormatter.format(date)}`;
    };

    const updateNode = (node) => {
        const locale = getLocale(node);
        const timezone = getTimezone(node);
        node.textContent = formatDate(new Date(), locale, timezone);
    };

    const ensureTimer = (node) => {
        if (timers.has(node)) {
            return;
        }
        updateNode(node);
        const timer = setInterval(() => updateNode(node), 1000);
        timers.set(node, timer);
    };

    const clearTimers = () => {
        timers.forEach((timer) => clearInterval(timer));
        timers.clear();
    };

    const init = () => {
        if (typeof document === "undefined") return;
        const nodes = Array.from(document.querySelectorAll(SELECTOR));
        if (!nodes.length) {
            clearTimers();
            return;
        }
        nodes.forEach((node) => ensureTimer(node));
    };

    const destroy = () => {
        clearTimers();
    };

    return {
        init,
        destroy,
    };
})();

const PasswordStrength = (() => {
    const WRAPPER_SELECTOR = "[data-password-strength]";
    const BAR_SELECTOR = "[data-strength-bar]";
    const TEXT_SELECTOR = "[data-strength-text]";
    const STATE_CLASSES = ["is-danger", "is-weak", "is-strong"];

    const STATES = [
        {
            label: "Masukkan password untuk mengetahui kekuatannya.",
            width: "0%",
            color: "#94a3b8",
        },
        {
            label: "Sangat lemah",
            width: "25%",
            color: "#ef4444",
            className: "is-danger",
        },
        {
            label: "Perlu diperkuat",
            width: "55%",
            color: "#f97316",
            className: "is-weak",
        },
        {
            label: "Cukup kuat",
            width: "80%",
            color: "#facc15",
            className: "is-strong",
        },
        {
            label: "Sangat kuat",
            width: "100%",
            color: "#22c55e",
            className: "is-strong",
        },
    ];

    const evaluate = (password) => {
        if (!password || password.length === 0) {
            return 0;
        }

        let score = 0;
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/\d/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;

        if (score <= 2) {
            return 1;
        }
        if (score <= 3) {
            return 2;
        }
        if (score <= 5) {
            return 3;
        }

        return 4;
    };

    const applyState = (wrapper, bar, text, level) => {
        const state = STATES[level] ?? STATES[0];

        if (bar) {
            bar.style.width = state.width;
            bar.style.backgroundColor = state.color;
        }

        if (text) {
            text.textContent = state.label;
        }

        if (wrapper) {
            wrapper.classList.remove(...STATE_CLASSES);
            if (state.className) {
                wrapper.classList.add(state.className);
            }
        }
    };

    const bindWrapper = (wrapper) => {
        if (!wrapper || wrapper.dataset.passwordStrengthInit === "1") {
            return;
        }

        const targetId = wrapper.getAttribute("data-password-strength");
        if (!targetId) {
            return;
        }

        const input = document.getElementById(targetId);
        if (!input) {
            return;
        }

        const bar = wrapper.querySelector(BAR_SELECTOR);
        const text = wrapper.querySelector(TEXT_SELECTOR);

        const update = () => {
            const level = evaluate(input.value);
            applyState(wrapper, bar, text, level);
        };

        input.addEventListener("input", update);
        input.addEventListener("blur", update);
        update();

        wrapper.dataset.passwordStrengthInit = "1";
    };

    const init = () => {
        if (typeof document === "undefined") {
            return;
        }

        document
            .querySelectorAll(WRAPPER_SELECTOR)
            .forEach((wrapper) => bindWrapper(wrapper));
    };

    return {
        init,
    };
})();

document.addEventListener("DOMContentLoaded", () => {
    ThemeManager.init();
    MotionManager.init();
    TranslationManager.init();
    SliderManager.init();
    SidebarManager.init();
    ControlCenter.init();
    CommandPalette.init();
    CopilotManager.init();
    AdminClock.init();
    PasswordStrength.init();
    window.SIRW = window.SIRW || {};
    window.SIRW.theme = ThemeManager;
    window.SIRW.motion = MotionManager;
    window.SIRW.translation = TranslationManager;
    window.SIRW.slider = SliderManager;
    window.SIRW.sidebar = SidebarManager;
    window.SIRW.copilot = CopilotManager;
    window.SIRW.clock = AdminClock;
    window.SIRW.passwordStrength = PasswordStrength;
    window.SIRW.controlCenter = ControlCenter;
    window.SIRW.command = CommandPalette;
});

if (typeof document !== "undefined") {
    document.addEventListener("livewire:navigated", () => {
        AdminClock.destroy();
        AdminClock.init();
        PasswordStrength.init();
        SliderManager.init();
        MotionManager.refresh();
        ControlCenter.refresh();
        CommandPalette.refresh();
        CopilotManager.refresh();
    });
}

if (typeof window !== "undefined") {
    window.addEventListener("beforeunload", () => {
        AdminClock.destroy();
    });
}

let passwordStrengthHooked = false;
const ensurePasswordStrengthHook = () => {
    if (
        passwordStrengthHooked ||
        typeof window === "undefined" ||
        !window.Livewire
    ) {
        return;
    }
    window.Livewire.hook("message.processed", () => {
        PasswordStrength.init();
        MotionManager.refresh();
    });
    passwordStrengthHooked = true;
};

ensurePasswordStrengthHook();

if (typeof document !== "undefined") {
    document.addEventListener("livewire:load", () => {
        ensurePasswordStrengthHook();
        PasswordStrength.init();
        MotionManager.refresh();
    });
}

let sliderHooked = false;
const ensureSliderHook = () => {
    if (sliderHooked || typeof window === "undefined" || !window.Livewire) {
        return;
    }
    window.Livewire.hook("message.processed", () => {
        SliderManager.init();
        MotionManager.refresh();
    });
    sliderHooked = true;
};

ensureSliderHook();

if (typeof document !== "undefined") {
    document.addEventListener("livewire:load", () => {
        ensureSliderHook();
        SliderManager.init();
    });
}
if (typeof document !== "undefined") {
    const bootExperienceManager = () => ExperienceManager.init();
    if (document.readyState !== "loading") {
        bootExperienceManager();
    } else {
        document.addEventListener("DOMContentLoaded", bootExperienceManager, {
            once: true,
        });
    }
    document.addEventListener("livewire:load", bootExperienceManager);
    document.addEventListener("livewire:navigated", bootExperienceManager);
}
