<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCategory\StoreServiceCategoryRequest;
use App\Http\Requests\ServiceCategory\UpdateServiceCategoryRequest;
use App\Http\Requests\Common\BulkDeleteRequest;
use App\Models\ServiceCategory;
use App\Services\ServiceCategoryService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;

class ServiceCategoryController extends Controller
{
    public function __construct(
        private readonly ServiceCategoryService $serviceCategoryService
    ) {
    }

    public function index(): Renderable
    {
        $this->authorize('viewAny', ServiceCategory::class);

        $this->setBreadcrumbTitle(__('Services'));

        return $this->renderViewWithBreadcrumbs('backend.pages.service-category.index');
    }

    public function create(): Renderable
    {
        $this->authorize('create', ServiceCategory::class);

        $this->setBreadcrumbTitle(__('New Service'))
            ->addBreadcrumbItem(__('Services'), route('admin.service-category.index'));

        return $this->renderViewWithBreadcrumbs('backend.pages.service-category.create');
    }

    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ServiceCategory::class);

        $this->serviceCategoryService->create($request->validated());

        session()->flash('success', __('Service has been created.'));

        return redirect()->route('admin.service-category.index');
    }

    public function edit(int $id): Renderable|RedirectResponse
    {
        $serviceCategory = $this->serviceCategoryService->findById($id);

        if (! $serviceCategory) {
            session()->flash('error', __('Service not found.'));

            return back();
        }

        $this->authorize('update', $serviceCategory);

        $this->setBreadcrumbTitle(__('Edit Service'))
            ->addBreadcrumbItem(__('Services'), route('admin.service-category.index'));

        return $this->renderViewWithBreadcrumbs('backend.pages.service-category.edit', [
            'serviceCategory' => $serviceCategory,
        ]);
    }

    public function update(UpdateServiceCategoryRequest $request, int $id): RedirectResponse
    {
        $serviceCategory = $this->serviceCategoryService->findById($id);

        if (! $serviceCategory) {
            session()->flash('error', __('Service not found.'));

            return back();
        }

        $this->authorize('update', $serviceCategory);

        $this->serviceCategoryService->update($serviceCategory, $request->validated());

        session()->flash('success', __('Service has been updated.'));

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $serviceCategory = $this->serviceCategoryService->findById($id);

        if (! $serviceCategory) {
            session()->flash('error', __('Service not found.'));

            return back();
        }

        $this->authorize('delete', $serviceCategory);

        $this->serviceCategoryService->delete($serviceCategory);

        session()->flash('success', __('Service has been deleted.'));

        return redirect()->route('admin.service-category.index');
    }

    /**
     * Delete multiple service categories at once
     */
    public function bulkDelete(BulkDeleteRequest $request): RedirectResponse
    {
        $this->authorize('bulkDelete', ServiceCategory::class);

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('admin.service-category.index')
                ->with('error', __('No services selected for deletion'));
        }

        $deletedCount = 0;

        foreach ($ids as $id) {
            $serviceCategory = $this->serviceCategoryService->findById((int) $id);
            if (! $serviceCategory) {
                continue;
            }

            $this->serviceCategoryService->delete($serviceCategory);
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            session()->flash('success', __(':count services deleted successfully', ['count' => $deletedCount]));
        } else {
            session()->flash('error', __('No services were deleted.'));
        }

        return redirect()->route('admin.service-category.index');
    }
}
