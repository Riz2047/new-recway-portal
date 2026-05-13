<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\CustomerQuestion;
use Livewire\Component;

class BackgroundQuestions extends Component
{
    public int $customerId;

    /** @var array<int, array{type: string, qs: string, option: list<string>}> */
    public array $questions = [];

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->loadQuestions();
    }

    public function addQuestion(string $type): void
    {
        if (! in_array($type, ['radio', 'free_text'], true)) {
            return;
        }

        $this->questions[] = [
            'type' => $type,
            'qs' => '',
            'option' => $type === 'radio' ? [''] : [],
        ];
    }

    public function removeQuestion(int $index): void
    {
        if (! isset($this->questions[$index])) {
            return;
        }

        array_splice($this->questions, $index, 1);
    }

    public function addOption(int $questionIndex): void
    {
        if (! isset($this->questions[$questionIndex])) {
            return;
        }

        $this->questions[$questionIndex]['option'][] = '';
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        if (! isset($this->questions[$questionIndex]['option'][$optionIndex])) {
            return;
        }

        array_splice($this->questions[$questionIndex]['option'], $optionIndex, 1);
    }

    public function save(): void
    {
        $cleaned = [];

        foreach ($this->questions as $q) {
            $type = $q['type'] ?? '';
            $qs = trim((string) ($q['qs'] ?? ''));

            if ($qs === '') {
                continue;
            }

            $entry = ['type' => $type, 'qs' => $qs];

            if ($type === 'radio') {
                $entry['option'] = array_values(
                    array_filter(
                        array_map('trim', $q['option'] ?? []),
                        fn (string $o): bool => $o !== ''
                    )
                );
            }

            $cleaned[] = $entry;
        }

        CustomerQuestion::updateOrCreate(
            ['cus_id' => $this->customerId],
            ['meta_data' => $cleaned]
        );

        $this->questions = $cleaned;

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Saved'),
            'message' => __('Background questions saved successfully.'),
        ]);
    }

    private function loadQuestions(): void
    {
        $record = CustomerQuestion::where('cus_id', $this->customerId)->first();

        if (! $record) {
            $record = CustomerQuestion::where('cus_id', 0)->first();
        }

        if (! $record || empty($record->meta_data)) {
            $this->questions = [];

            return;
        }

        $this->questions = array_values(
            array_map(function (array $q): array {
                return [
                    'type' => $q['type'] ?? 'free_text',
                    'qs' => $q['qs'] ?? '',
                    'option' => array_values($q['option'] ?? []),
                ];
            }, (array) $record->meta_data)
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.customer.tabs.background-questions');
    }
}
