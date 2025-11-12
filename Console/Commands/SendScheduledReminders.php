<?php

namespace App\Console\Commands;

use App\Jobs\DynamicReminderDispatcher;
use App\Jobs\ProcessAssistantMaintenance;
use Illuminate\Console\Command;

class SendScheduledReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Menjalankan dispatcher pengingat dinamis untuk memproses pengingat jatuh tempo';

    public function handle(): int
    {
        DynamicReminderDispatcher::dispatchSync();
        ProcessAssistantMaintenance::dispatch();

        $this->info('Dispatcher pengingat dijalankan untuk menit ini.');

        return self::SUCCESS;
    }
}
