<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Admin\ManualProofPreviewController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\Resident\EmailVerificationController;
use App\Http\Controllers\Resident\EventCalendarController;
use App\Http\Controllers\Resident\ReceiptController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\StorageProxyController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureResident;
use App\Http\Middleware\IsAdmin;
use App\Livewire\Admin\Agenda\Create as AdminAgendaCreate;
use App\Livewire\Admin\Agenda\Form as AdminAgendaForm;
use App\Livewire\Admin\Agenda\Index as AdminAgendaIndex;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Kas\Index as AdminKasIndex;
use App\Livewire\Admin\Laporan\Index as AdminLaporanIndex;
use App\Livewire\Admin\Pembayaran\Index as AdminPembayaranIndex;
use App\Livewire\Admin\Pengaturan\General as AdminSettingsGeneral;
use App\Livewire\Admin\Pengaturan\Slider as AdminSettingsSlider;
use App\Livewire\Admin\Pengaturan\Smtp as AdminSettingsSmtp;
use App\Livewire\Admin\Pengaturan\PaymentGateway as AdminSettingsPayment;
use App\Livewire\Admin\Pengaturan\ReminderTemplate as AdminSettingsReminderTemplate;
use App\Livewire\Admin\Pengaturan\TelegramBot as AdminSettingsTelegram;
use App\Livewire\Admin\Pengaturan\AssistantAnalytics as AdminSettingsAssistantAnalytics;
use App\Livewire\Admin\Pengaturan\DummyClientMonitor as AdminSettingsDummyClientMonitor;
use App\Livewire\Admin\Pengaturan\ToolBlueprintManager as AdminSettingsToolBlueprintManager;
use App\Livewire\Admin\Pengaturan\ReasoningLessonManager as AdminSettingsReasoningLessonManager;
use App\Livewire\Admin\Pengaturan\LlmSnapshotCandidates as AdminSettingsLlmSnapshotCandidates;
use App\Livewire\Admin\Profil\EditProfile as AdminProfileEdit;
use App\Livewire\Admin\Profil\ViewProfile as AdminProfileView;
use App\Livewire\Admin\Tagihan\Create as AdminTagihanCreate;
use App\Livewire\Admin\Tagihan\Form as AdminTagihanForm;
use App\Livewire\Admin\Tagihan\Index as AdminTagihanIndex;
use App\Livewire\Admin\Reminder\Automation as AdminReminderAutomation;
use App\Livewire\Admin\Warga\CitizenRecords;
use App\Livewire\Admin\Warga\Create as AdminWargaCreate;
use App\Livewire\Admin\Warga\Form as AdminWargaForm;
use App\Livewire\Admin\Warga\Index as AdminWargaIndex;
use App\Livewire\Resident\Assistant as ResidentAssistant;
use App\Livewire\Resident\Bills as ResidentBills;
use App\Livewire\Resident\Dashboard as ResidentDashboard;
use App\Livewire\Resident\Directory as ResidentDirectory;
use App\Livewire\Resident\Payments\ManualPayment as ResidentManualPayment;
use App\Livewire\Resident\Payments\TripayPayment as ResidentTripayPayment;
use App\Livewire\Resident\Profile as ResidentProfile;
use App\Livewire\Resident\Reports as ResidentReports;
use App\Livewire\Resident\Search as ResidentSearch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->withoutMiddleware(['web'])->group(function () {
    Route::match(['GET', 'POST'], '/payment/webhook', PaymentWebhookController::class)
        ->name('payments.webhook');
    Route::match(['GET', 'POST'], '/payment/callback', PaymentWebhookController::class)
        ->name('payments.callback');
    Route::match(['GET', 'POST'], '/telegram/webhook', TelegramWebhookController::class)
        ->name('telegram.webhook');
});

Route::get('storage/files/{path}', StorageProxyController::class)
    ->where('path', '.*')
    ->name('storage.proxy');

// Guard against accidental GET requests to Livewire's update endpoint
Route::get('/livewire/update', function () {
    $fallback = Auth::check() ? route('resident.profile') : route('landing');

    return redirect()->to($fallback);
})->name('livewire.update.fallback');

// Rute untuk Halaman Landing/Publik
Route::post('/locale', LocaleController::class)->name('locale.update');
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/about', [LandingController::class, 'about'])->name('about');
Route::get('/agenda', [LandingController::class, 'agenda'])->name('landing.agenda');
Route::get('/kontak', [LandingController::class, 'contact'])->name('landing.contact');
Route::post('/kontak', [ContactMessageController::class, 'store'])->name('contact.store');

// Rute untuk Autentikasi (Login, Register, Logout)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/lupa-password', [PasswordResetController::class, 'showIdentifierForm'])->name('password.request');
    Route::post('/lupa-password', [PasswordResetController::class, 'handleIdentifier'])->name('password.request.submit');
    Route::get('/lupa-password/otp/{token}', [PasswordResetController::class, 'showOtpForm'])->name('password.otp.show');
    Route::post('/lupa-password/otp/{token}', [PasswordResetController::class, 'verifyOtp'])->name('password.otp.verify');
    Route::get('/lupa-password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset.show');
    Route::post('/lupa-password/reset/{token}', [PasswordResetController::class, 'resetPassword'])->name('password.reset.perform');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/konfirmasi-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/konfirmasi-password', [ConfirmablePasswordController::class, 'store'])->name('password.confirm.store');
});


// Rute untuk Warga/Penduduk (Resident)
Route::middleware(['auth', EnsureResident::class])->prefix('warga')->name('resident.')->group(function () {
    Route::get('/verifikasi-email', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::post('/verifikasi-email', [EmailVerificationController::class, 'verify'])->name('verification.verify');
    Route::post('/verifikasi-email/kirim-ulang', [EmailVerificationController::class, 'resend'])->name('verification.resend');
    Route::get('/verifikasi-email/batal', [EmailVerificationController::class, 'cancel'])->name('verification.cancel');

    Route::middleware(EnsureEmailVerified::class)->group(function () {
        Route::get('/dashboard', ResidentDashboard::class)->name('dashboard');
        Route::get('/agenda/{event}/ics', EventCalendarController::class)->name('events.ics');
        Route::get('/tagihan', ResidentBills::class)->name('bills');
        Route::get('/tagihan/{bill}/manual', ResidentManualPayment::class)->name('bills.manual');
        Route::get('/tagihan/{bill}/tripay', ResidentTripayPayment::class)->name('bills.tripay');
        Route::get('/tagihan/{payment}/manual-proof', ManualProofPreviewController::class)->name('bills.manual-proof');
        Route::get('/tagihan/{bill}/print', [ReceiptController::class, 'show'])->name('bills.receipt');
        Route::get('/tagihan/{bill}/download', [ReceiptController::class, 'download'])->name('bills.receipt.download');
        Route::get('/laporan', ResidentReports::class)->name('reports');
        Route::get('/warga', ResidentDirectory::class)->name('directory');
        Route::get('/asisten', ResidentAssistant::class)->name('assistant');
        Route::get('/profil', ResidentProfile::class)->middleware('password.confirm')->name('profile');
        Route::get('/cari', ResidentSearch::class)->name('search');
    });
});

// Rute untuk Admin
Route::middleware(['auth', IsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::get('/search', \App\Livewire\Admin\Search::class)->name('search');

    // Warga
    Route::get('/warga', AdminWargaIndex::class)->name('warga.index');
    Route::get('/warga/create', AdminWargaCreate::class)->name('warga.create');
    Route::get('/warga/pra-registrasi', CitizenRecords::class)->name('warga.reserve');
    Route::get('/warga/{user}/edit', AdminWargaForm::class)->middleware('password.confirm')->name('warga.edit');

    // Tagihan
    Route::get('/tagihan', AdminTagihanIndex::class)->name('tagihan.index');
    Route::get('/tagihan/create', AdminTagihanCreate::class)->name('tagihan.create'); // Diubah dari 'buat' menjadi 'create' untuk konsistensi
    Route::get('/tagihan/{bill}/edit', AdminTagihanForm::class)->name('tagihan.edit'); // Ditambahkan untuk fitur edit tagihan

    // Pembayaran & Laporan
    Route::get('/pembayaran', AdminPembayaranIndex::class)->name('pembayaran.index');
    Route::get('/pembayaran/manual-proof/{payment}', ManualProofPreviewController::class)->name('pembayaran.manual-proof');
    Route::get('/kas', AdminKasIndex::class)->name('kas.index');
    Route::get('/laporan', AdminLaporanIndex::class)->name('laporan.index');

    // Reminder
    Route::get('/reminder-otomatis', AdminReminderAutomation::class)->name('reminder.automation');

    // Agenda
    Route::get('/agenda', AdminAgendaIndex::class)->name('agenda.index');
    Route::get('/agenda/create', AdminAgendaCreate::class)->name('agenda.create'); // Diubah dari 'buat' menjadi 'create' untuk konsistensi
    Route::get('/agenda/{event}/edit', AdminAgendaForm::class)->name('agenda.edit'); // Ditambahkan untuk fitur edit agenda

    // Pengaturan (Logo, About, Slider)
    Route::get('/pengaturan', AdminSettingsGeneral::class)->name('settings.general');
    Route::get('/pengaturan/assistant-analytics', AdminSettingsAssistantAnalytics::class)->name('settings.analytics');
    Route::get('/pengaturan/dummy-monitor', AdminSettingsDummyClientMonitor::class)->name('settings.dummy-monitor');
    Route::get('/pengaturan/tool-blueprints', AdminSettingsToolBlueprintManager::class)->name('settings.tool-blueprints');
    Route::get('/pengaturan/reasoning-lessons', AdminSettingsReasoningLessonManager::class)->name('settings.reasoning-lessons');
    Route::get('/pengaturan/llm-candidates', AdminSettingsLlmSnapshotCandidates::class)->name('settings.llm-candidates');
    Route::get('/pengaturan/slider', AdminSettingsSlider::class)->name('settings.slider');
    Route::get('/pengaturan/smtp', AdminSettingsSmtp::class)->name('settings.smtp');
    Route::get('/pengaturan/payment-gateway', AdminSettingsPayment::class)->name('settings.payment');
    Route::get('/pengaturan/template-reminder', AdminSettingsReminderTemplate::class)->name('settings.reminder');
    Route::get('/pengaturan/telegram-bot', AdminSettingsTelegram::class)->name('settings.telegram');

    // Profil Admin
    Route::get('/profil', AdminProfileView::class)->name('profile.view');
    Route::get('/profil/edit', AdminProfileEdit::class)->middleware('password.confirm')->name('profile.edit');
});
