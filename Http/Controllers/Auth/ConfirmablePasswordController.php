<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Arr;

class ConfirmablePasswordController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $backRoute = route('landing');
        $backLabel = 'Kembali ke beranda';

        if ($user) {
            if ($user->role === 'warga') {
                $backRoute = route('resident.dashboard');
                $backLabel = 'Kembali ke dashboard warga';
            } elseif (method_exists($user, 'isAdmin') ? $user->isAdmin() : $user->role === 'admin') {
                $backRoute = route('admin.dashboard');
                $backLabel = 'Kembali ke dashboard admin';
            }
        }

        return view('auth.confirm-password', [
            'title' => 'Konfirmasi Password',
            'intended' => $request->session()->get('url.intended'),
            'site' => $this->siteMeta(),
            'backRoute' => $backRoute,
            'backLabel' => $backLabel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Silakan masukkan password akun Anda.',
        ]);

        if (! Hash::check($request->input('password'), $request->user()->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => 'Password tidak sesuai. Coba lagi.',
            ]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('landing'));
    }

    private function siteMeta(): array
    {
        $settings = SiteSetting::keyValue()->toArray();

        return [
            'name' => Arr::get($settings, 'site_name', 'Sistem Informasi RT'),
            'tagline' => Arr::get($settings, 'tagline'),
            'contact_email' => Arr::get($settings, 'contact_email'),
            'contact_phone' => Arr::get($settings, 'contact_phone'),
            'address' => Arr::get($settings, 'address'),
        ];
    }
}
