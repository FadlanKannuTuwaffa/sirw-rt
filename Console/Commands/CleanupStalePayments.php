<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupStalePayments extends Command
{
    private const TARGET_STATUSES = ['failed', 'cancelled', 'expired'];

    protected $signature = 'payments:cleanup-stale {--dry-run : Hanya menampilkan jumlah transaksi tanpa menghapus}';

    protected $description = 'Hapus transaksi gagal/batal/kedaluwarsa yang tidak bergerak dalam 48 jam.';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subHours(48);

        $baseQuery = Payment::query()
            ->whereIn('status', self::TARGET_STATUSES)
            ->where('updated_at', '<=', $cutoff);

        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            $this->info('Tidak ada transaksi yang perlu dibersihkan.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line("Mode uji: {$total} transaksi akan dihapus.");
            return self::SUCCESS;
        }

        $deleted = 0;

        (clone $baseQuery)
            ->orderBy('id')
            ->chunkById(200, function ($payments) use (&$deleted) {
                foreach ($payments as $payment) {
                    $payment->delete();
                    $deleted++;
                }
            });

        $this->info("{$deleted} transaksi gagal/batal dihapus (cutoff {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}
