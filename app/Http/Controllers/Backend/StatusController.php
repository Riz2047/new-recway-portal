<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\Status;
use App\Models\Interview;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StatusController extends Controller
{
    /**
     * Display a listing of statuses for a service category
     */
    public function index(int $serviceCategory): Renderable
    {
        $serviceCategoryModel = ServiceCategory::findOrFail($serviceCategory);
        
        $this->authorize('viewAny', Status::class);
        
        $this->setBreadcrumbTitle(__('Statuses'))
            ->addBreadcrumbItem(__('Services'), route('admin.service-category.index'))
            ->addBreadcrumbItem($serviceCategoryModel->name, route('admin.service-category.edit', $serviceCategoryModel->id));

        $statuses = Status::where('status_type', $serviceCategoryModel->id)
            ->orderBy('status')
            ->get();

        return $this->renderViewWithBreadcrumbs('backend.pages.status.index', [
            'serviceCategory' => $serviceCategoryModel,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Show the form for creating a new status
     */
    public function create(int $serviceCategory): Renderable
    {
        $serviceCategoryModel = ServiceCategory::findOrFail($serviceCategory);
        
        $this->authorize('create', Status::class);
        
        $this->setBreadcrumbTitle(__('New Status'))
            ->addBreadcrumbItem(__('Services'), route('admin.service-category.index'))
            ->addBreadcrumbItem($serviceCategoryModel->name, route('admin.service-category.edit', $serviceCategoryModel->id))
            ->addBreadcrumbItem(__('Statuses'), route('admin.status.index', $serviceCategoryModel->id));

        // Get interviews/services for this service category (if table exists)
        $interviews = collect([]);
        try {
            if (\Schema::hasTable('interviews')) {
                $interviews = Interview::where('service_cat_id', $serviceCategoryModel->id)->get();
            }
        } catch (\Exception $e) {
            // Table doesn't exist, use empty collection
            $interviews = collect([]);
        }

        return $this->renderViewWithBreadcrumbs('backend.pages.status.create', [
            'serviceCategory' => $serviceCategoryModel,
            'interviews' => $interviews,
        ]);
    }

    /**
     * Store a newly created status
     */
    public function store(Request $request, int $serviceCategory): RedirectResponse
    {
        $serviceCategoryModel = ServiceCategory::findOrFail($serviceCategory);
        
        $this->authorize('create', Status::class);

        $rules = [
            'variable' => 'required|string|max:255|unique:statuses,variable',
            'status' => 'required|string|max:255',
            'status_sv' => 'nullable|string|max:255',
            'status_detail' => 'nullable|string',
            'status_icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'services' => 'nullable|array',
            'message' => 'nullable|string',
            'msg_col' => 'nullable|string|max:255',
        ];
        
        // Only validate services if interviews table exists
        if (Schema::hasTable('interviews')) {
            $rules['services.*'] = 'exists:interviews,id';
        }
        
        $validated = $request->validate($rules);

        $status = Status::create([
            'variable' => $validated['variable'],
            'status' => $validated['status'],
            'status_sv' => $validated['status_sv'] ?? null,
            'status_detail' => $validated['status_detail'] ?? null,
            'status_icon' => $validated['status_icon'] ?? null,
            'color' => $validated['color'] ?? null,
            'status_type' => $serviceCategoryModel->id,
        ]);

        // Attach services/interviews if provided and table exists
        if (!empty($validated['services']) && Schema::hasTable('interviews')) {
            foreach ($validated['services'] as $serviceId) {
                try {
                    $status->services()->attach($serviceId, [
                        'msg_col' => $validated['msg_col'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    // Skip if attachment fails (e.g., interview doesn't exist)
                    continue;
                }
            }
        }

        session()->flash('success', __('Status has been created.'));

        return redirect()->route('admin.status.index', $serviceCategoryModel->id);
    }

    /**
     * Show the form for editing a status
     */
    public function edit(int $serviceCategory, int $status): Renderable
    {
        $serviceCategoryModel = ServiceCategory::findOrFail($serviceCategory);
        $statusModel = Status::findOrFail($status);
        
        // Ensure status belongs to this service category
        if ($statusModel->status_type !== $serviceCategoryModel->id) {
            abort(404);
        }
        
        $this->authorize('update', $statusModel);
        
        $this->setBreadcrumbTitle(__('Edit Status'))
            ->addBreadcrumbItem(__('Services'), route('admin.service-category.index'))
            ->addBreadcrumbItem($serviceCategoryModel->name, route('admin.service-category.edit', $serviceCategoryModel->id))
            ->addBreadcrumbItem(__('Statuses'), route('admin.status.index', $serviceCategoryModel->id));

        return $this->renderViewWithBreadcrumbs('backend.pages.status.edit', [
            'serviceCategory' => $serviceCategoryModel,
            'status' => $statusModel,
        ]);
    }

    /**
     * Update a status
     */
    public function update(Request $request, int $serviceCategory, int $status): RedirectResponse
    {
        $serviceCategoryModel = ServiceCategory::findOrFail($serviceCategory);
        $statusModel = Status::findOrFail($status);
        
        // Ensure status belongs to this service category
        if ($statusModel->status_type !== $serviceCategoryModel->id) {
            abort(404);
        }
        
        $this->authorize('update', $statusModel);

        $validated = $request->validate([
            'variable' => 'required|string|max:255|unique:statuses,variable,' . $statusModel->id,
            'status' => 'required|string|max:255',
            'status_sv' => 'nullable|string|max:255',
            'status_detail' => 'nullable|string',
            'status_icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
        ]);

        $statusModel->update($validated);

        session()->flash('success', __('Status has been updated.'));

        return redirect()->route('admin.status.index', $serviceCategoryModel->id);
    }

    /**
     * Remove a status
     */
    public function destroy(int $serviceCategory, int $status): RedirectResponse
    {
        $serviceCategoryModel = ServiceCategory::findOrFail($serviceCategory);
        $statusModel = Status::findOrFail($status);
        
        // Ensure status belongs to this service category
        if ($statusModel->status_type !== $serviceCategoryModel->id) {
            abort(404);
        }
        
        $this->authorize('delete', $statusModel);

        $statusModel->delete();

        session()->flash('success', __('Status has been deleted.'));

        return redirect()->route('admin.status.index', $serviceCategoryModel->id);
    }
}
