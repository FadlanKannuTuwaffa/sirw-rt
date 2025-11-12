<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\KbArticle;
use App\Services\Assistant\RAGService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class KnowledgeBaseManager extends Component
{
    use WithFileUploads;

    public $document;
    public string $title = '';
    public string $manualContent = '';
    public array $articles = [];
    public bool $isSyncing = false;

    protected array $rules = [
        'title' => 'nullable|string|max:160',
        'manualContent' => 'nullable|string|min:20',
        'document' => 'nullable|file|mimes:txt,md|max:2048',
    ];

    protected array $messages = [
        'manualContent.required_without_all' => 'Isi konten manual atau unggah dokumen.',
        'document.required_without_all' => 'Unggah dokumen atau isi konten manual.',
    ];

    public function mount(): void
    {
        $this->loadArticles();
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.knowledge-base-manager');
    }

    public function ingest(RAGService $rag): void
    {
        $this->validate([
            'title' => $this->rules['title'],
            'manualContent' => 'required_without:document|nullable|string|min:20',
            'document' => 'required_without:manualContent|nullable|file|mimes:txt,md|max:4096',
        ], $this->messages);

        $content = $this->resolveContent();
        $title = $this->resolveTitle();

        Storage::disk('local')->makeDirectory('kb');

        if ($this->document) {
            $filename = Str::slug($title) ?: Str::random(8);
            Storage::disk('local')->put("kb/{$filename}.md", $content);
        }

        $rag->ingest($title, $content);

        $this->reset(['document', 'title', 'manualContent']);
        $this->loadArticles();
        session()->flash('kb_message', 'Dokumen berhasil diingest ke knowledge base.');
    }

    public function syncFromStorage(RAGService $rag): void
    {
        $this->isSyncing = true;

        try {
            $files = Storage::disk('local')->files('kb');
            if ($files === []) {
                session()->flash('kb_warning', 'Folder storage/app/kb masih kosong.');
                return;
            }

            \App\Models\KbChunk::truncate();
            KbArticle::truncate();

            foreach ($files as $path) {
                if (!Str::endsWith($path, '.md')) {
                    continue;
                }

                $content = Storage::disk('local')->get($path);
                $title = basename($path, '.md');
                $rag->ingest($title, $content);
            }

            $this->loadArticles();
            session()->flash('kb_message', 'Sinkronisasi ulang knowledge base selesai.');
        } finally {
            $this->isSyncing = false;
        }
    }

    private function loadArticles(): void
    {
        $this->articles = KbArticle::query()
            ->withCount('chunks')
            ->latest()
            ->limit(8)
            ->get(['id', 'title', 'updated_at'])
            ->map(fn (KbArticle $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'chunks' => $article->chunks_count,
                'updated_at' => $article->updated_at?->diffForHumans() ?? '-',
            ])
            ->toArray();
    }

    private function resolveContent(): string
    {
        if ($this->document) {
            return (string) $this->document->get();
        }

        return trim($this->manualContent);
    }

    private function resolveTitle(): string
    {
        if ($this->title !== '') {
            return $this->title;
        }

        if ($this->document) {
            return pathinfo($this->document->getClientOriginalName(), PATHINFO_FILENAME);
        }

        return 'Dokumen ' . now()->format('d M Y H:i');
    }
}
