<?php

namespace Tests\Feature;

use App\Mail\ContactMessageSubmitted;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_contact_message(): void
    {
        Mail::fake();

        $response = $this->post('/kontak', [
            'name' => 'Rani',
            'email' => 'rani@example.com',
            'phone' => '08123456789',
            'message' => 'Saya ingin menanyakan prosedur registrasi.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Rani',
            'email' => 'rani@example.com',
        ]);

        Mail::assertQueued(ContactMessageSubmitted::class);
    }
}
