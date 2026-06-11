<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FormBuilderFieldService
{
    /**
     * @return array{
     *     fields: array<int, array{section:string,type:string,label:string,name:string,placeholder:string,required:bool,options:array<int,string>}>,
     *     has_form_builder: bool
     * }
     */
    public function load(int $customerId, ?int $serviceTypeId): array
    {
        if (! $serviceTypeId || ! Schema::hasTable('form_builders')) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $row = DB::table('form_builders')
            ->where('cus_id', $customerId)
            ->where('servicetype_id', $serviceTypeId)
            ->first();

        if (! $row || empty($row->form)) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $decoded = json_decode((string) $row->form, true);
        if (! is_array($decoded)) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $builder = $decoded['form_builder'] ?? $decoded;
        if (! is_array($builder)) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $personalFields = $this->mapBuilderSection($builder['personal_info'] ?? [], 'personal');
        $billingFields = $this->mapBuilderSection($builder['billing_info'] ?? [], 'billing');
        $fields = array_values(array_merge($personalFields, $billingFields));

        if ($fields === []) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        return [
            'fields' => $fields,
            'has_form_builder' => true,
        ];
    }

    /**
     * @param array<mixed, mixed> $section
     *
     * @return array<int, array{section:string,type:string,label:string,name:string,placeholder:string,required:bool,options:array<int,string>}>
     */
    private function mapBuilderSection(array $section, string $sectionName): array
    {
        $normalized = [];

        foreach ($section as $metaKey => $value) {
            $parts = explode(',', (string) $metaKey);

            $type = trim($parts[0] ?? 'text');
            $label = trim($parts[1] ?? '');
            $name = trim($parts[2] ?? '');
            $placeholder = trim($parts[3] ?? '');
            $required = trim($parts[4] ?? '') === 'required';
            $optionString = trim($parts[7] ?? '');

            if ($name === '') {
                continue;
            }

            if ($name === 'pref') {
                $name = 'referensperson';
            } elseif ($name === 'ref') {
                $name = 'reference';
            } elseif ($name === 'social_security_number' || strtolower($label) === 'social security number') {
                $name = 'security';
            }

            if ($placeholder === '') {
                $placeholder = is_string($value) ? trim($value) : '';
            }

            $options = [];
            if ($type === 'select' && $optionString !== '') {
                $options = collect(explode('|', $optionString))
                    ->map(fn (string $option): string => trim($option))
                    ->filter(fn (string $option): bool => $option !== '')
                    ->values()
                    ->all();
            }

            $normalized[] = [
                'section' => $sectionName,
                'type' => $type !== '' ? $type : 'text',
                'label' => $label !== '' ? $label : ucfirst(str_replace('_', ' ', $name)),
                'name' => $name,
                'placeholder' => $placeholder,
                'required' => $required,
                'options' => $options,
            ];
        }

        return $normalized;
    }

    /** @return array<int, array{section:string,type:string,label:string,name:string,placeholder:string,required:bool,options:array<int,string>}> */
    private function defaultCandidateFields(): array
    {
        return [
            ['section' => 'personal', 'type' => 'text', 'label' => 'Security / Date of Birth', 'name' => 'security', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'VASC ID', 'name' => 'vasc_id', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'Name', 'name' => 'name', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'Surname', 'name' => 'surname', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'email', 'label' => 'Email', 'name' => 'email', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'Phone', 'name' => 'phone', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'billing', 'type' => 'text', 'label' => 'Reference (Invoice Recipient)', 'name' => 'referensperson', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'billing', 'type' => 'text', 'label' => 'Reference', 'name' => 'reference', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'billing', 'type' => 'textarea', 'label' => 'Invoice Comment', 'name' => 'comment', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'billing', 'type' => 'textarea', 'label' => 'Note', 'name' => 'note', 'placeholder' => '', 'required' => false, 'options' => []],
        ];
    }
}
