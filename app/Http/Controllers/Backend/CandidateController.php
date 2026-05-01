<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Customer;
use App\Models\Place;
use App\Models\ServiceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidateController extends Controller
{
    public function index(): Renderable
    {
        $this->authorize('viewAny', Customer::class);

        $this->setBreadcrumbTitle(__('Candidates'));

        return $this->renderViewWithBreadcrumbs('backend.pages.candidates.index');
    }

    public function create(): Renderable
    {
        $this->authorize('create', Customer::class);

        $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('New Candidate'))
            ->addBreadcrumbItem(__('Candidates'), route($prefix . '.candidates.index'));

        $customers = Schema::hasTable('customers')
            ? Customer::query()->with('user')->get()->sortBy(fn ($customer) => $customer->user?->name ?? '')
            : collect();

        $serviceTypes = Schema::hasTable('service_types')
            ? ServiceType::query()->orderBy('name')->get()
            : collect();

        $places = Schema::hasTable('places')
            ? Place::query()->orderBy('name')->get()
            : collect();

        $staff = Schema::hasTable('users')
            ? User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Manager', 'Manager with statistics', 'Moderator', 'User']))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        return $this->renderViewWithBreadcrumbs('backend.pages.candidates.create', [
            'customers' => $customers,
            'serviceTypes' => $serviceTypes,
            'places' => $places,
            'staff' => $staff,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['required', 'integer', 'exists:service_types,id'],
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
            'security' => ['required', 'string', 'max:255'],
            'hasPersonalId' => ['nullable', 'boolean'],
            'vasc_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'place' => ['nullable', 'integer', 'exists:places,id'],
            'country' => ['nullable', 'string', 'max:255'],
            'referensperson' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        if (! Schema::hasTable('candidates')) {
            return back()
                ->withInput()
                ->with('error', __('Candidates table is not available in this environment.'));
        }

        $statusId = $this->resolveDefaultStatusId((int) $validated['interview_id']);

        DB::transaction(function () use ($validated, $statusId): void {
            Candidate::query()->create([
                'order_id' => $this->generateOrderId(),
                'vasc_id' => ($validated['vasc_id'] ?? null) ?: null,
                'security' => $validated['security'],
                'name' => $validated['name'],
                'surname' => $validated['surname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'place' => $validated['place'] ?? null,
                'country' => $validated['country'] ?? null,
                'referensperson' => $validated['referensperson'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'note' => $validated['note'] ?? null,
                'cus_id' => (int) $validated['cus_id'],
                'interview_id' => (int) $validated['interview_id'],
                'status' => $statusId,
                'staff_id' => ! empty($validated['staff_id']) ? (int) $validated['staff_id'] : null,
                'expired' => 0,
                'hasPersonalId' => ! empty($validated['hasPersonalId']) ? 1 : 0,
            ]);
        });

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        return to_route($prefix . '.candidates.index')
            ->with('success', __('Candidate has been created successfully.'));
    }

    public function services(Request $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $services = $this->getCustomerServices((int) $validated['cus_id']);

        return response()->json([
            'services' => $services,
            'selected_service_id' => $services->first()['id'] ?? null,
        ]);
    }

    public function form(Request $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['nullable', 'integer'],
        ]);

        $services = $this->getCustomerServices((int) $validated['cus_id']);
        $selectedServiceId = (int) ($validated['interview_id'] ?? 0);

        if (! $services->contains(fn (array $service): bool => (int) $service['id'] === $selectedServiceId)) {
            $selectedServiceId = (int) ($services->first()['id'] ?? 0);
        }

        $formBuilderData = $this->loadFormBuilderData((int) $validated['cus_id'], $selectedServiceId ?: null);

        return response()->json([
            'selected_service_id' => $selectedServiceId ?: null,
            'form_fields' => $formBuilderData['fields'],
            'has_form_builder' => $formBuilderData['has_form_builder'],
        ]);
    }

    private function generateOrderId(): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $candidate = '';
            for ($i = 0; $i < 6; $i++) {
                $candidate .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (Candidate::query()->where('order_id', $candidate)->exists());

        return $candidate;
    }

    private function resolveDefaultStatusId(int $serviceTypeId): ?int
    {
        if (! Schema::hasTable('service_types')) {
            return null;
        }

        $serviceCategoryId = ServiceType::query()
            ->whereKey($serviceTypeId)
            ->value('service_category_id');

        if (! $serviceCategoryId) {
            return null;
        }

        if (! Schema::hasTable('statuses')) {
            return null;
        }

        return Status::query()
            ->where('status_type', $serviceCategoryId)
            ->orderBy('id')
            ->value('id');
    }

    /** @return Collection<int, array{id:int,name:string,place:int,country:int}> */
    private function getCustomerServices(int $customerId): Collection
    {
        if (! Schema::hasTable('service_types') || ! Schema::hasTable('service_type_user')) {
            return collect();
        }

        return DB::table('service_type_user')
            ->join('service_types', 'service_types.id', '=', 'service_type_user.service_type_id')
            ->where('service_type_user.cus_id', $customerId)
            ->orderBy('service_types.name')
            ->select('service_types.id', 'service_types.name', 'service_types.place', 'service_types.country')
            ->get()
            ->map(fn (object $service): array => [
                'id' => (int) $service->id,
                'name' => (string) $service->name,
                'place' => (int) ((string) ($service->place ?? '0') === '1'),
                'country' => (int) ((string) ($service->country ?? '0') === '1'),
            ])
            ->values();
    }

    /**
     * @return array{
     *     fields: array<int, array{section:string,type:string,label:string,name:string,placeholder:string,required:bool,options:array<int,string>}>,
     *     has_form_builder: bool
     * }
     */
    private function loadFormBuilderData(int $customerId, ?int $serviceTypeId): array
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
