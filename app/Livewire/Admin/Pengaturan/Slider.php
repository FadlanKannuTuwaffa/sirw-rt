<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\Slide;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Slider extends Component
{
    private const MAX_IMAGE_SIZE = 3_145_728; // ~3MB

    private const ALLOWED_MIMES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/webp' => 'webp',
    ];

    public string $title = '';
    public ?string $subtitle = null;
    public ?string $description = null;
    public ?string $button_label = null;
    public ?string $button_url = null;

    public ?string $imageData = null;
    public ?string $imageMime = null;
    public ?string $imageOriginalName = null;
    public ?string $imagePreviewUrl = null;

    public ?int $editingId = null;
    public ?string $currentImagePath = null;
    public ?string $currentImageUrl = null;

    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public function render()
    {
        $slides = Slide::orderBy('position')->get();

        foreach ($slides as $slide) {
            if ($slide->image_path) {
                $this->mirrorStoredFileToPublic($slide->image_path);
            }
        }

        return view('livewire.admin.pengaturan.slider', [
            'slides' => $slides,
        ]);
    }

    public function receiveImagePayload(string $payload, string $mime, string $name): void
    {
        $this->imageData = $payload;
        $this->imageMime = strtolower($mime);
        $this->imageOriginalName = $name;
        $this->imagePreviewUrl = 'data:'.$mime.';base64,'.$payload;
        $this->resetValidation(['imageData']);
    }

    public function handleImageTooLarge(): void
    {
        $this->addError('imageData', 'Ukuran gambar melebihi batas 3MB.');
        $this->clearImagePayload();
    }

    public function handleImageReadError(): void
    {
        $this->addError('imageData', 'Gagal membaca file gambar. Coba pilih ulang.');
        $this->clearImagePayload();
    }

    public function clearImagePayload(): void
    {
        $this->imageData = null;
        $this->imageMime = null;
        $this->imageOriginalName = null;
        $this->imagePreviewUrl = null;
    }

    public function save(): void
    {
        $rules = [
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'button_label' => ['nullable', 'string', 'max:40'],
            'button_url' => ['nullable', 'url'],
        ];

        $validated = $this->validate($rules);

        $imagePath = $this->currentImagePath;

        if ($this->imageData !== null) {
            $imagePath = $this->storeImageFromPayload();

            if ($this->currentImagePath && Storage::disk('public')->exists($this->currentImagePath)) {
                Storage::disk('public')->delete($this->currentImagePath);
                $this->deletePublicMirror($this->currentImagePath);
            }
        } elseif (! $this->editingId) {
            $this->addError('imageData', 'Gambar wajib diunggah.');
            return;
        }

        if ($this->editingId) {
            $slide = Slide::findOrFail($this->editingId);
            $slide->update([
                'title' => $validated['title'],
                'subtitle' => $validated['subtitle'],
                'description' => $validated['description'],
                'button_label' => $validated['button_label'],
                'button_url' => $validated['button_url'],
                'image_path' => $imagePath,
            ]);

            $message = 'Slide berhasil diperbarui.';
        } else {
            $position = Slide::max('position') + 1;

            Slide::create([
                'title' => $validated['title'],
                'subtitle' => $validated['subtitle'],
                'description' => $validated['description'],
                'button_label' => $validated['button_label'],
                'button_url' => $validated['button_url'],
                'image_path' => $imagePath,
                'position' => $position,
                'is_active' => true,
            ]);

            $message = 'Slide baru berhasil ditambahkan.';
        }

        $this->resetForm();
        session()->flash('status', $message);
    }

    private function storeImageFromPayload(): string
    {
        $binary = base64_decode($this->imageData ?? '', true);

        if ($binary === false) {
            throw ValidationException::withMessages(['imageData' => 'Data gambar tidak valid.']);
        }

        if (strlen($binary) > self::MAX_IMAGE_SIZE) {
            throw ValidationException::withMessages(['imageData' => 'Ukuran gambar melebihi batas 3MB.']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_buffer($finfo, $binary) ?: null;
        finfo_close($finfo);

        $mime = $detectedMime && array_key_exists($detectedMime, self::ALLOWED_MIMES)
            ? $detectedMime
            : ($this->imageMime && array_key_exists($this->imageMime, self::ALLOWED_MIMES) ? $this->imageMime : null);

        if (! $mime) {
            throw ValidationException::withMessages(['imageData' => 'Format gambar tidak didukung.']);
        }

        $extension = Arr::get(self::ALLOWED_MIMES, $mime);
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = 'slides/'.$filename;

        Storage::disk('public')->put($path, $binary);
        $this->mirrorBinaryToPublic($path, $binary);

        return $path;
    }

    private function resetForm(): void
    {
        $this->reset([
            'title',
            'subtitle',
            'description',
            'button_label',
            'button_url',
            'imageData',
            'imageMime',
            'imageOriginalName',
            'imagePreviewUrl',
            'editingId',
            'currentImagePath',
            'currentImageUrl',
        ]);
        $this->resetValidation();
    }

    public function edit(int $slideId): void
    {
        $slide = Slide::findOrFail($slideId);

        $this->editingId = $slide->id;
        $this->title = $slide->title;
        $this->subtitle = $slide->subtitle;
        $this->description = $slide->description;
        $this->button_label = $slide->button_label;
        $this->button_url = $slide->button_url;
        $this->currentImagePath = $slide->image_path;
        $this->currentImageUrl = $this->publicUrl($slide->image_path);
        $this->clearImagePayload();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function toggle(int $slideId): void
    {
        $slide = Slide::findOrFail($slideId);
        $slide->update(['is_active' => ! $slide->is_active]);
    }

    public function delete(int $slideId): void
    {
        $slide = Slide::findOrFail($slideId);

        if ($slide->image_path && Storage::disk('public')->exists($slide->image_path)) {
            Storage::disk('public')->delete($slide->image_path);
            $this->deletePublicMirror($slide->image_path);
        }

        $slide->delete();

        if ($this->editingId === $slideId) {
            $this->resetForm();
        }

        session()->flash('status', 'Slide dihapus.');
    }

    public function moveUp(int $slideId): void
    {
        $slide = Slide::findOrFail($slideId);
        $previous = Slide::where('position', '<', $slide->position)->orderBy('position', 'desc')->first();

        if ($previous) {
            [$slide->position, $previous->position] = [$previous->position, $slide->position];
            $slide->save();
            $previous->save();
        }
    }

    public function moveDown(int $slideId): void
    {
        $slide = Slide::findOrFail($slideId);
        $next = Slide::where('position', '>', $slide->position)->orderBy('position')->first();

        if ($next) {
            [$slide->position, $next->position] = [$next->position, $slide->position];
            $slide->save();
            $next->save();
        }
    }

    private function mirrorBinaryToPublic(string $relativePath, string $binary): void
    {
        if ($this->publicStorageIsSymlink()) {
            return;
        }

        $target = $this->publicMirrorFullPath($relativePath);
        $directory = dirname($target);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($target, $binary);
    }

    private function mirrorStoredFileToPublic(string $relativePath): void
    {
        if ($this->publicStorageIsSymlink()) {
            return;
        }

        $target = $this->publicMirrorFullPath($relativePath);

        if (File::exists($target)) {
            return;
        }

        $source = Storage::disk('public')->path($relativePath);

        if (! File::exists($source)) {
            return;
        }

        $directory = dirname($target);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::copy($source, $target);
    }

    private function deletePublicMirror(?string $relativePath): void
    {
        if (! $relativePath || $this->publicStorageIsSymlink()) {
            return;
        }

        $target = $this->publicMirrorFullPath($relativePath);

        if (File::exists($target)) {
            File::delete($target);
        }
    }

    private function publicMirrorFullPath(string $relativePath): string
    {
        $publicRoot = $this->publicStorageRoot();
        $normalised = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        return $publicRoot.DIRECTORY_SEPARATOR.$normalised;
    }

    private function publicStorageRoot(): string
    {
        return public_path('storage');
    }

    private function publicStorageIsSymlink(): bool
    {
        $root = $this->publicStorageRoot();

        return is_link($root);
    }

    private function publicUrl(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($relativePath);
    }
}
