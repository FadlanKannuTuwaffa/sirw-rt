<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\AssistantReasoningLesson;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ReasoningLessonManager extends Component
{
    use WithPagination;

    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public ?int $editingId = null;
    public string $intent = '';
    public string $title = '';
    public string $stepsInput = '';
    public string $status = 'active';
    public string $source = '';
    public string $notes = '';
    public int $priority = 0;
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function startCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
    }

    public function editLesson(int $lessonId): void
    {
        $lesson = AssistantReasoningLesson::findOrFail($lessonId);

        $this->editingId = $lesson->id;
        $this->intent = $lesson->intent;
        $this->title = $lesson->title;
        $this->stepsInput = implode(PHP_EOL, $lesson->steps ?? []);
        $this->status = $lesson->status;
        $this->source = $lesson->source ?? '';
        $this->notes = $lesson->notes ?? '';
        $this->priority = (int) $lesson->priority;
    }

    public function saveLesson(): void
    {
        $data = $this->validate($this->rules());
        $steps = $this->normalizeSteps($this->stepsInput);

        if ($steps === []) {
            $this->addError('stepsInput', 'Minimal satu langkah reasoning dibutuhkan.');
            return;
        }

        $payload = [
            'intent' => $data['intent'],
            'title' => $data['title'],
            'steps' => $steps,
            'status' => $data['status'],
            'priority' => $data['priority'],
            'source' => $data['source'] !== '' ? $data['source'] : null,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ];

        if ($this->editingId === null) {
            AssistantReasoningLesson::create($payload);
        } else {
            AssistantReasoningLesson::whereKey($this->editingId)->update($payload);
        }

        session()->flash('banner-message', [
            'type' => 'success',
            'message' => 'Reasoning lesson disimpan.',
        ]);

        $this->resetForm();
    }

    public function deleteLesson(int $lessonId): void
    {
        AssistantReasoningLesson::whereKey($lessonId)->delete();

        if ($this->editingId === $lessonId) {
            $this->resetForm();
        }

        session()->flash('banner-message', [
            'type' => 'success',
            'message' => 'Reasoning lesson dihapus.',
        ]);
    }

    public function render()
    {
        $lessons = AssistantReasoningLesson::query()
            ->when($this->search !== '', function ($query) {
                $term = '%' . strtolower($this->search) . '%';
                $query->whereRaw('LOWER(intent) like ?', [$term])
                    ->orWhereRaw('LOWER(title) like ?', [$term])
                    ->orWhereRaw('LOWER(notes) like ?', [$term]);
            })
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.admin.pengaturan.reasoning-lesson-manager', [
            'lessons' => $lessons,
        ])->title('Reasoning Lesson Manager');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->intent = '';
        $this->title = '';
        $this->stepsInput = '';
        $this->status = 'active';
        $this->source = '';
        $this->notes = '';
        $this->priority = 0;
        $this->resetErrorBag();
    }

    private function rules(): array
    {
        return [
            'intent' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'stepsInput' => ['required', 'string'],
            'status' => ['required', Rule::in(['active', 'draft', 'archived'])],
            'source' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int,string>
     */
    private function normalizeSteps(string $input): array
    {
        $parts = preg_split('/\r\n|\r|\n/', $input) ?: [];

        return array_values(array_filter(array_map(function ($line) {
            return trim($line);
        }, $parts), fn ($line) => $line !== ''));
    }
}
