<?php

namespace Tests\Feature;

use App\Models\CitizenRecord;
use App\Models\EmailVerificationOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResidentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_register_using_preloaded_data(): void
    {
        $nik = '1234567890123456';

        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'username' => null,
            'phone' => null,
            'nik' => $nik,
            'alamat' => 'Jl. Melati No. 10',
            'role' => 'warga',
            'status' => 'aktif',
            'registration_status' => 'pending',
            'password' => Hash::make(Str::random(32)),
        ]);

        CitizenRecord::create([
            'nik' => $nik,
            'nama' => $user->name,
            'email' => $user->email,
            'alamat' => $user->alamat,
            'status' => 'available',
        ]);

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'username' => null,
            'phone' => null,
            'nik' => $nik,
            'alamat' => 'Jl. Melati No. 10',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('resident.verification.notice'));
        $response->assertSessionHas('status');

        $freshUser = $user->fresh();
        $this->assertNull($freshUser->email_verified_at);
        $this->assertNull($freshUser->pending_email);
        $this->assertEquals('active', $freshUser->registration_status);
        $this->assertTrue(Hash::check('password123', $freshUser->password));
        $this->assertAuthenticatedAs($freshUser);

        $this->assertTrue(
            EmailVerificationOtp::query()
                ->where('user_id', $freshUser->id)
                ->where('purpose', EmailVerificationOtp::PURPOSE_INITIAL)
                ->exists()
        );

        $this->assertEquals('claimed', CitizenRecord::where('nik', $nik)->value('status'));
    }

    public function test_registration_rejected_when_data_mismatch(): void
    {
        $nik = '7777777777777777';

        $user = User::create([
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'nik' => $nik,
            'alamat' => 'Jl. Kenanga No. 5',
            'role' => 'warga',
            'status' => 'aktif',
            'registration_status' => 'pending',
            'password' => Hash::make(Str::random(32)),
        ]);

        CitizenRecord::create([
            'nik' => $nik,
            'nama' => $user->name,
            'email' => $user->email,
            'alamat' => $user->alamat,
            'status' => 'available',
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'username' => null,
            'phone' => null,
            'nik' => $nik,
            'alamat' => 'Alamat yang berbeda',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('nik');

        $this->assertEquals('pending', $user->fresh()->registration_status);
    }

    public function test_registration_cannot_repeat_for_active_account(): void
    {
        $nik = '9999999999999999';

        $user = User::create([
            'name' => 'Rahmat Hidayat',
            'email' => 'rahmat@example.com',
            'nik' => $nik,
            'alamat' => 'Jl. Dahlia No. 3',
            'role' => 'warga',
            'status' => 'aktif',
            'registration_status' => 'active',
            'password' => Hash::make('secret123'),
        ]);

        CitizenRecord::create([
            'nik' => $nik,
            'nama' => $user->name,
            'email' => $user->email,
            'alamat' => $user->alamat,
            'status' => 'claimed',
            'claimed_by' => $user->id,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Rahmat Hidayat',
            'email' => 'rahmat@example.com',
            'username' => null,
            'phone' => null,
            'nik' => $nik,
            'alamat' => 'Jl. Dahlia No. 3',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('nik');
    }
}
