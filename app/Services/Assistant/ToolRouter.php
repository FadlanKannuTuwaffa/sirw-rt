<?php

namespace App\Services\Assistant;

use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\RTOfficial;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ToolRouter
{
    private ToolSchemaRegistry $registry;
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $executionLog = [];

    public function __construct(?ToolSchemaRegistry $registry = null)
    {
        $this->registry = $registry ?? new ToolSchemaRegistry();
    }

    public function getToolDefinitions(): array
    {
        return $this->registry->definitions();
    }

    /**
     * @param  array<string,mixed>  $args
     * @param  array<string,mixed>  $lexicalContext
     * @return array{valid:bool,parameters:array,errors:array,clarification?:string,autofixed?:array}
     */
    public function validateAndCoerce(string $toolName, array $args, string $message = '', array $lexicalContext = []): array
    {
        return $this->registry->validate($toolName, $args, $message, $lexicalContext);
    }

    public function execute(string $toolName, array $args): array
    {
        try {
            Log::info('Tool execution started', ['tool' => $toolName, 'args' => $args]);
            
            $result = match ($toolName) {
                'get_outstanding_bills' => $this->getOutstandingBills($args['resident_id']),
                'get_payment_status' => $this->getPaymentStatus($args['resident_id'], $args['month'] ?? null, $args['type'] ?? null),
                'get_payments_this_month' => $this->getPaymentsThisMonth($args['resident_id']),
                'get_agenda' => $this->getAgenda($args['range'] ?? 'month', $args['resident_id']),
                'export_financial_recap' => $this->exportFinancialRecap($args['resident_id'], $args['period'] ?? 'this_month'),
                'search_directory' => $this->searchDirectory($args['query'], $args['resident_id'], $args['status'] ?? 'all'),
                'get_rt_contacts' => $this->getRTContacts($args['position'] ?? 'all'),
                'rag_search' => app(RAGService::class)->search($args['query']),
                default => ['error' => 'Tool tidak dikenal: ' . $toolName, 'success' => false],
            };
            
            Log::info('Tool execution completed', ['tool' => $toolName, 'success' => $result['success'] ?? true]);
            $this->executionLog[] = [
                'name' => $toolName,
                'success' => (bool) ($result['success'] ?? true),
                'count' => $result['count'] ?? null,
                'timestamp' => now()->toIso8601String(),
            ];
            return $result;
            
        } catch (\Throwable $e) {
            Log::error('Tool execution failed', [
                'tool' => $toolName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->executionLog[] = [
                'name' => $toolName,
                'success' => false,
                'count' => null,
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ];
            
            return [
                'success' => false,
                'error' => 'Maaf, terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi atau hubungi admin RT.',
                'technical_error' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    public function suggestions(int $residentId): array
    {
        $suggestions = [];
        
        $billCount = Bill::where('user_id', $residentId)->where('status', '!=', 'paid')->count();
        if ($billCount > 0) {
            $suggestions[] = 'Tagihanku bulan ini berapa?';
        }
        
        $eventCount = Event::where('start_at', '>=', now())->where('is_public', true)->count();
        if ($eventCount > 0) {
            $suggestions[] = 'Apa agenda minggu ini?';
        }
        
        $suggestions[] = 'Berapa total warga di RT ini?';
        $suggestions[] = 'Bagaimana cara menghubungi ketua RT?';
        
        return array_slice($suggestions, 0, 3);
    }

    private function getOutstandingBills(int $residentId): array
    {
        $cacheKey = "bills_outstanding_{$residentId}";
        
        return Cache::remember($cacheKey, 300, function() use ($residentId) {
            $bills = Bill::where('user_id', $residentId)
                ->where('status', '!=', 'paid')
                ->orderBy('due_date')
                ->limit(10)
                ->get(['title', 'amount', 'due_date', 'status']);

            return [
                'success' => true,
                'count' => $bills->count(),
                'total' => $bills->sum('amount'),
                'items' => $bills->map(fn($b) => [
                    'title' => $b->title,
                    'amount' => 'Rp ' . number_format($b->amount, 0, ',', '.'),
                    'due_date' => Carbon::parse($b->due_date)->format('d M Y'),
                    'status' => $b->status,
                ])->toArray(),
                'message' => $bills->count() > 0 
                    ? "Anda memiliki {$bills->count()} tagihan belum dibayar dengan total Rp" . number_format($bills->sum('amount'), 0, ',', '.') 
                    : "Tidak ada tagihan tertunggak. Mantap!"
            ];
        });
    }

    private function getPaymentsThisMonth(int $residentId): array
    {
        $now = Carbon::now();
        $payments = Payment::where('user_id', $residentId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
            ->with('bill:id,title,amount')
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        return [
            'success' => true,
            'count' => $payments->count(),
            'total' => $payments->sum('amount'),
            'items' => $payments->map(fn($p) => [
                'title' => $p->bill->title ?? 'Tagihan tidak diketahui',
                'amount' => 'Rp ' . number_format($p->amount, 0, ',', '.'),
                'paid_at' => $p->paid_at->format('d M Y'),
            ])->toArray(),
            'message' => $payments->count() > 0 
                ? "Total {$payments->count()} pembayaran bulan ini: Rp" . number_format($payments->sum('amount'), 0, ',', '.') 
                : "Belum ada pembayaran bulan ini."
        ];
    }

    private function getAgenda(string $range, int $residentId): array
    {
        $cacheKey = "agenda_{$range}_" . now()->format('Y-m-d');
        
        return Cache::remember($cacheKey, 1800, function() use ($range) {
            $now = Carbon::now();
            $end = $range === 'week' ? $now->copy()->addWeek() : $now->copy()->addMonth();

            $events = Event::where('start_at', '>=', $now)
                ->where('start_at', '<=', $end)
                ->where('is_public', true)
                ->orderBy('start_at')
                ->limit(10)
                ->get(['title', 'start_at', 'location']);

            return [
                'success' => true,
                'count' => $events->count(),
                'items' => $events->map(fn($e) => [
                    'title' => $e->title,
                    'start_at' => Carbon::parse($e->start_at)->format('d M Y H:i'),
                    'location' => $e->location ?? 'Lokasi belum ditentukan',
                ])->toArray(),
                'message' => $events->count() > 0 
                    ? "Ada {$events->count()} agenda " . ($range === 'week' ? 'minggu' : 'bulan') . " ini." 
                    : "Tidak ada agenda " . ($range === 'week' ? 'minggu' : 'bulan') . " ini."
            ];
        });
    }

    private function exportFinancialRecap(int $residentId, string $period): array
    {
        $now = Carbon::now();
        
        [$start, $end] = match($period) {
            'last_month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
        
        $bills = Bill::where('user_id', $residentId)
            ->whereBetween('created_at', [$start, $end])
            ->get();
            
        $payments = Payment::where('user_id', $residentId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->get();
        
        $totalBills = $bills->sum('amount');
        $totalPaid = $payments->sum('amount');
        $outstanding = $bills->where('status', '!=', 'paid')->sum('amount');
        
        return [
            'period' => $period,
            'total_tagihan' => $totalBills,
            'total_dibayar' => $totalPaid,
            'total_tunggakan' => $outstanding,
            'jumlah_tagihan' => $bills->count(),
            'jumlah_pembayaran' => $payments->count(),
            'message' => 'Rekap keuangan berhasil dibuat. Untuk download PDF, silakan buka menu Laporan di dashboard.'
        ];
    }

    private function getPaymentStatus(int $residentId, ?string $month, ?string $type): array
    {
        $query = Payment::where('user_id', $residentId)->where('status', 'paid');
        
        if ($month) {
            $date = Carbon::parse($month);
            $query->whereBetween('paid_at', [$date->startOfMonth(), $date->copy()->endOfMonth()]);
        } else {
            $now = Carbon::now();
            $query->whereBetween('paid_at', [$now->startOfMonth(), $now->endOfMonth()]);
        }
        
        if ($type) {
            $query->whereHas('bill', function($q) use ($type) {
                $q->where('title', 'like', "%{$type}%");
            });
        }
        
        $payments = $query->with('bill:id,title,amount')->get();
        
        return [
            'success' => true,
            'count' => $payments->count(),
            'total' => $payments->sum('amount'),
            'items' => $payments->map(fn($p) => [
                'title' => $p->bill->title ?? 'Unknown',
                'amount' => $p->amount,
                'paid_at' => $p->paid_at->format('d M Y'),
            ])->toArray(),
            'message' => $payments->count() > 0 
                ? "Ditemukan {$payments->count()} pembayaran." 
                : "Tidak ada pembayaran ditemukan."
        ];
    }

    private function searchDirectory(string $query, int $residentId, string $status): array
    {
        if (in_array(strtolower($query), ['*', 'all', 'semua', 'total', 'jumlah', ''])) {
            $baseQuery = User::where('role', 'warga');
            
            if ($status !== 'all') {
                $baseQuery->where('status', $status);
            }
            
            $total = $baseQuery->count();
            $statusText = $status === 'all' ? '' : " dengan status {$status}";
            
            return [
                'success' => true,
                'total_warga' => $total,
                'status' => $status,
                'message' => "Saat ini ada {$total} warga{$statusText} terdaftar di RT."
            ];
        }
        
        $usersQuery = User::where('role', 'warga')
            ->where('name', 'like', "%{$query}%");
            
        if ($status !== 'all') {
            $usersQuery->where('status', $status);
        }
        
        $users = $usersQuery->limit(5)->get(['name', 'status', 'phone']);

        return [
            'success' => true,
            'count' => $users->count(),
            'items' => $users->map(fn($u) => [
                'name' => $u->name,
                'status' => $u->status,
                'phone' => $u->masked_phone ?? 'Tidak tersedia',
            ])->toArray(),
            'message' => $users->count() > 0 
                ? "Ditemukan {$users->count()} warga." 
                : "Tidak ada warga dengan nama '{$query}' ditemukan."
        ];
    }

    private function getRTContacts(string $position): array
    {
        return Cache::remember('rt_contacts_' . $position, 3600, function() use ($position) {
            $query = RTOfficial::where('is_active', true)->orderBy('order');
            
            if ($position !== 'all') {
                $query->where('position', $position);
            }
            
            $officials = $query->get();
            
            if ($officials->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Data kontak pengurus RT belum tersedia. Silakan hubungi admin untuk informasi lebih lanjut.',
                ];
            }
            
            if ($position === 'all') {
                return [
                    'success' => true,
                    'contacts' => $officials->map(fn($o) => [
                        'name' => $o->name,
                        'position' => ucfirst($o->position),
                        'phone' => $o->phone,
                        'email' => $o->email,
                    ])->toArray(),
                    'message' => 'Berikut adalah kontak pengurus RT yang dapat dihubungi.'
                ];
            }
            
            $official = $officials->first();
            
            return [
                'success' => true,
                'contact' => [
                    'name' => $official->name,
                    'position' => ucfirst($official->position),
                    'phone' => $official->phone,
                    'email' => $official->email,
                ],
                'message' => "Kontak {$official->position}: {$official->name} - {$official->phone}"
            ];
        });
    }

    /**
     * Ambil log eksekusi tool dan reset buffer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pullExecutionLog(): array
    {
        $log = $this->executionLog;
        $this->executionLog = [];

        return $log;
    }

    public function resetExecutionLog(): void
    {
        $this->executionLog = [];
    }
}
