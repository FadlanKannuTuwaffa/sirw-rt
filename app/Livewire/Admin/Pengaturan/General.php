<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\SiteSetting;
use App\Support\StorageUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class General extends Component
{
    use WithFileUploads;

    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public string $site_name = 'Sistem Informasi RT';
    public ?string $tagline = null;
    public ?string $about = null;
    public ?string $vision = null;
    public ?string $mission = null;
    public ?string $contact_email = null;
    public ?string $contact_phone = null;
    public ?string $service_hours = null;
    public ?string $address = null;
    public ?string $facebook = null;
    public ?string $instagram = null;
    public ?string $youtube = null;

    public ?string $logo_initials = 'SR';
    public $logo;
    public ?string $logo_url = null;

    public function mount(): void
    {
        $this->site_name = $this->getSetting('site_name', $this->site_name);
        $this->tagline = $this->getSetting('tagline');
        $this->about = $this->getSetting('about');
        $this->vision = $this->getSetting('vision');
        $this->mission = $this->getSetting('mission');
        $this->contact_email = $this->getSetting('contact_email');
        $this->contact_phone = $this->getSetting('contact_phone');
        $this->service_hours = $this->getSetting('service_hours');
        $this->address = $this->getSetting('address');
        $this->facebook = $this->getSetting('facebook');
        $this->instagram = $this->getSetting('instagram');
        $this->youtube = $this->getSetting('youtube');
        $this->logo_initials = $this->getSetting('logo_initials', $this->logo_initials);
        $logoPath = $this->getSetting('logo_path');

        $this->logo_url = StorageUrl::forPublicDisk($logoPath);
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.general');
    }

    public function save(): void
    {
        $data = $this->validate([
            'site_name' => ['required', 'string', 'max:160'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'about' => ['nullable', 'string'],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'service_hours' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string'],
            'facebook' => ['nullable', 'url'],
            'instagram' => ['nullable', 'url'],
            'youtube' => ['nullable', 'url'],
            'logo_initials' => ['nullable', 'string', 'max:4'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        foreach (Arr::except($data, ['logo']) as $key => $value) {
            if ($key === 'logo_initials' && $value) {
                $value = strtoupper(trim($value));
            }
            $this->storeSetting($key, $value);
        }

        if ($this->logo) {
            $path = $this->logo->store('branding', 'public');
            $this->storeSetting('logo_path', $path);

            $this->logo_url = StorageUrl::forPublicDisk($path);
            $this->reset('logo'); // release temporary upload so we show the stored logo URL
        }

        session()->flash('status', 'Pengaturan berhasil disimpan.');
    }

    private function getSetting(string $key, mixed $default = null): mixed
    {
        return optional(SiteSetting::where('key', $key)->first())->value ?? $default;
    }

    private function storeSetting(string $key, mixed $value): void
    {
        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => 'general']
        );
    }
}
