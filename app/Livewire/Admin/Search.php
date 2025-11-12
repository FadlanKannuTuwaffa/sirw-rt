<?php

namespace App\Livewire\Admin;

use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Support\SensitiveData;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Search extends Component
{
    public string $q = '';

    protected $queryString = [
        'q' => ['except' => ''],
    ];

    public function mount(?string $q = null): void
    {
        $this->q = (string) ($q ?? request()->get('q', ''));
    }

    public function render()
    {
        $term = trim($this->q);
        $users = collect();
        $bills = collect();
        $payments = collect();
        $events = collect();
        $total = 0;

        if ($term !== '') {
            $keyword = '%' . $this->escapeLike($term) . '%';
            $numericDigits = preg_replace('/[^\d]/', '', $term);
            $hasNumeric = $numericDigits !== '' && ctype_digit($numericDigits);
            $numeric = $hasNumeric ? (int) $numericDigits : null;

            $users = User::query()
                ->latest('updated_at')
                ->where(function ($query) use ($keyword, $hasNumeric, $numericDigits) {
                    $query->where('name', 'like', $keyword)
                        ->orWhere('email', 'like', $keyword)
                        ->orWhere('username', 'like', $keyword)
                        ->orWhere('status', 'like', $keyword)
                        ->orWhere('registration_status', 'like', $keyword)
                        ->orWhere('notes', 'like', $keyword);

                    if ($hasNumeric) {
                        if (strlen($numericDigits) === 16) {
                            $query->orWhere('nik_hash', SensitiveData::hash($numericDigits));
                        }

                        if (in_array(strlen($numericDigits), [10, 11, 12, 13, 14], true)) {
                            $query->orWhere('phone_hash', SensitiveData::hash($numericDigits));
                        }
                    }
                })
                ->limit(8)
                ->get();

            $bills = Bill::query()
                ->with('user:id,name')
                ->latest('issued_at')
                ->where(function ($query) use ($keyword, $hasNumeric, $numeric) {
                    $query->where('title', 'like', $keyword)
                        ->orWhere('invoice_number', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $keyword));

                    if ($hasNumeric) {
                        $query->orWhere('amount', $numeric);
                    }
                })
                ->limit(8)
                ->get();

            $payments = Payment::query()
                ->with(['user:id,name', 'bill:id,title,invoice_number'])
                ->latest('paid_at')
                ->where(function ($query) use ($keyword, $hasNumeric, $numeric) {
                    $query->where('reference', 'like', $keyword)
                        ->orWhere('gateway', 'like', $keyword)
                        ->orWhere('status', 'like', $keyword)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $keyword))
                        ->orWhereHas('bill', function ($bill) use ($keyword) {
                            $bill->where('title', 'like', $keyword)
                                ->orWhere('invoice_number', 'like', $keyword);
                        });

                    if ($hasNumeric) {
                        $query->orWhere('amount', $numeric);
                    }
                })
                ->limit(8)
                ->get();

            $events = Event::query()
                ->latest('start_at')
                ->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword)
                        ->orWhere('location', 'like', $keyword);
                })
                ->limit(8)
                ->get();

            $total = collect([$users, $bills, $payments, $events])->sum(fn (Collection $items) => $items->count());
        }

        $title = $term !== '' ? "Pencarian: {$term}" : 'Pencarian';

        return view('livewire.admin.search', [
            'term' => $term,
            'users' => $users,
            'bills' => $bills,
            'payments' => $payments,
            'events' => $events,
            'totalResults' => $total,
            'title' => $title,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
