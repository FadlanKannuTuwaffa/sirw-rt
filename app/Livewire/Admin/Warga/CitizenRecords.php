<?php

namespace App\Livewire\Admin\Warga;

use App\Models\CitizenRecord;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CitizenRecords extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'available';

    public string $nik = '';
    public string $nama = '';
    public ?string $email = null;
    public ?string $alamat = null;

    protected $rules = [
        'nik' => ['required', 'string', 'size:16', 'unique:citizen_records,nik'],
        'nama' => ['required', 'string', 'max:160'],
        'email' => ['nullable', 'email', 'max:160'],
        'alamat' => ['nullable', 'string', 'max:255'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $data = $this->validate();

        CitizenRecord::create([
            'nik' => $data['nik'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'alamat' => $data['alamat'],
            'status' => 'available',
        ]);

        $this->reset(['nik', 'nama', 'email', 'alamat']);
        session()->flash('status', 'Data penduduk siap pendaftaran berhasil ditambahkan.');
    }

    public function markAvailable(int $recordId): void
    {
        CitizenRecord::whereKey($recordId)->update(['status' => 'available', 'claimed_by' => null]);
        session()->flash('status', 'Status data dipulihkan menjadi available.');
    }

    public function delete(int $recordId): void
    {
        CitizenRecord::whereKey($recordId)->delete();
        session()->flash('status', 'Data penduduk dihapus.');
    }

    public function render()
    {
        $records = CitizenRecord::query()
            ->when($this->search, function ($q) {
                $keyword = '%' . $this->search . '%';
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('nama', 'like', $keyword)
                        ->orWhere('nik', 'like', $keyword)
                        ->orWhere('email', 'like', $keyword);
                });
            })
            ->when($this->status !== 'semua', fn ($q) => $q->where('status', $this->status))
            ->orderBy('nama')
            ->paginate(10);

        return view('livewire.admin.warga.citizen-records', [
            'records' => $records,
            'title' => 'Pra-Registrasi Warga',
        ]);
    }
}
