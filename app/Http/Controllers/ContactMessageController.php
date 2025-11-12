<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageSubmitted;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = ContactMessage::create($data);

        $recipients = collect([
            SiteSetting::where('key', 'contact_email')->value('value'),
            config('mail.from.address'),
        ])->filter()
            ->flatMap(function ($value) {
                $parts = preg_split('/[;,]+/', (string) $value) ?: [];

                return array_filter(
                    array_map('trim', $parts),
                    static fn ($email) => $email !== ''
                );
            })
            ->filter(static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($recipients->isNotEmpty()) {
            foreach ($recipients as $recipient) {
                Mail::to($recipient)->queue(new ContactMessageSubmitted($message));
            }
        }

        $locale = app()->getLocale();
        $statusMessage = $locale === 'en'
            ? 'Message sent successfully. The administrators will get back to you shortly.'
            : 'Pesan berhasil dikirim. Pengurus akan menghubungi Anda secepatnya.';

        return back()->with('status', $statusMessage);
    }
}

