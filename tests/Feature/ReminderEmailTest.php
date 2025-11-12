<?php

namespace Tests\Feature;

use App\Jobs\SendReminderEmails;
use App\Models\Bill;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReminderMail;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReminderEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_command_dispatches_email_jobs(): void
    {
        Bus::fake([SendReminderEmails::class]);

        Carbon::setTestNow(now()->seconds(0));

        $user = User::factory()->create(['email' => 'test@example.com']);

        $bill = Bill::create([
            'user_id' => $user->id,
            'type' => 'iuran',
            'title' => 'Iuran Kebersihan',
            'amount' => 50000,
            'due_date' => now()->addDay(),
            'status' => 'unpaid',
            'invoice_number' => 'INV-TEST',
            'issued_at' => now(),
            'created_by' => $user->id,
        ]);

        $reminder = Reminder::create([
            'model_type' => Bill::class,
            'model_id' => $bill->id,
            'channel' => 'email',
            'send_at' => now(),
            'status' => 'scheduled',
        ]);

        $this->artisan('reminders:send')->assertExitCode(0);

        Carbon::setTestNow();

        Bus::assertDispatched(SendReminderEmails::class, function (SendReminderEmails $job) use ($reminder) {
            return $job->reminderId === $reminder->id;
        });
    }

    public function test_send_reminder_job_sends_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $bill = Bill::create([
            'user_id' => $user->id,
            'type' => 'iuran',
            'title' => 'Iuran Kebersihan',
            'amount' => 60000,
            'due_date' => now()->addDays(2),
            'status' => 'unpaid',
            'invoice_number' => 'INV-REM',
            'issued_at' => now(),
            'created_by' => $user->id,
        ]);

        $reminder = Reminder::create([
            'model_type' => Bill::class,
            'model_id' => $bill->id,
            'channel' => 'email',
            'send_at' => now()->subMinute(),
            'status' => 'scheduled',
        ]);

        SendReminderEmails::dispatchSync($reminder->fresh()->id);

        Mail::assertQueued(ReminderMail::class, function (ReminderMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
        $this->assertEquals('sent', $reminder->fresh()->status);
    }
}
