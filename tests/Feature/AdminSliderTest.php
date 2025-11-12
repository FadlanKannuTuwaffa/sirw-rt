<?php

namespace Tests\Feature;

use App\Livewire\Admin\Pengaturan\Slider as SliderComponent;
use App\Models\Slide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSliderTest extends TestCase
{
    use RefreshDatabase;

    private function imagePayload(): array
    {
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';

        return [
            'base64' => $base64,
            'binary' => base64_decode($base64),
            'mime' => 'image/png',
            'name' => 'slide.png',
        ];
    }

    public function test_admin_can_create_slider_with_image(): void
    {
        Storage::fake('public');
        $image = $this->imagePayload();

        Livewire::test(SliderComponent::class)
            ->set('title', 'Test Slide')
            ->set('subtitle', 'Subjudul')
            ->set('description', 'Deskripsi slide')
            ->set('button_label', 'Selengkapnya')
            ->set('button_url', 'https://example.com')
            ->call('receiveImagePayload', $image['base64'], $image['mime'], $image['name'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('slides', 1);

        $slide = Slide::first();
        $this->assertNotNull($slide);
        $this->assertEquals('Test Slide', $slide->title);
        $this->assertEquals('Subjudul', $slide->subtitle);
        $this->assertEquals('Deskripsi slide', $slide->description);
        $this->assertEquals('Selengkapnya', $slide->button_label);
        $this->assertEquals('https://example.com', $slide->button_url);
        $this->assertNotNull($slide->image_path);

        Storage::disk('public')->assertExists($slide->image_path);
    }

    public function test_admin_can_update_existing_slide_without_reuploading_image(): void
    {
        Storage::fake('public');
        $image = $this->imagePayload();
        $existingPath = 'slides/existing.png';
        Storage::disk('public')->put($existingPath, $image['binary']);

        $slide = Slide::create([
            'title' => 'Old Title',
            'subtitle' => 'Old Subtitle',
            'description' => 'Old Description',
            'image_path' => $existingPath,
            'position' => 1,
            'is_active' => true,
        ]);

        Livewire::test(SliderComponent::class)
            ->call('edit', $slide->id)
            ->set('title', 'Updated Title')
            ->set('subtitle', 'New Subtitle')
            ->set('description', 'New Description')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('slides', [
            'id' => $slide->id,
            'title' => 'Updated Title',
            'subtitle' => 'New Subtitle',
            'description' => 'New Description',
            'image_path' => $existingPath,
        ]);
    }

    public function test_failed_validation_does_not_require_reuploading_image(): void
    {
        Storage::fake('public');
        $image = $this->imagePayload();

        $component = Livewire::test(SliderComponent::class)
            ->set('title', 'Test Slide')
            ->set('subtitle', 'Subjudul')
            ->set('description', 'Deskripsi')
            ->set('button_label', 'Label')
            ->set('button_url', 'invalid-url')
            ->call('receiveImagePayload', $image['base64'], $image['mime'], $image['name'])
            ->call('save')
            ->assertHasErrors(['button_url' => 'url']);

        // Fix the URL but do not re-upload the image
        $component
            ->set('button_url', 'https://example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('slides', 1);
        $slide = Slide::first();
        $this->assertNotNull($slide?->image_path);
        Storage::disk('public')->assertExists($slide->image_path);
    }
}
