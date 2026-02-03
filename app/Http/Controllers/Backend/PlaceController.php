<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Place\StorePlaceRequest;
use App\Http\Requests\Place\UpdatePlaceRequest;
use App\Http\Requests\Common\BulkDeleteRequest;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;

class PlaceController extends Controller
{
    public function __construct(
        private readonly PlaceService $placeService
    ) {
    }

    public function index(): Renderable
    {
        $this->authorize('viewAny', Place::class);

        $this->setBreadcrumbTitle(__('Places'));

        return $this->renderViewWithBreadcrumbs('backend.pages.place.index');
    }

    public function create(): Renderable
    {
        $this->authorize('create', Place::class);

        $this->setBreadcrumbTitle(__('New Place'))
            ->addBreadcrumbItem(__('Places'), route('admin.place.index'));

        return $this->renderViewWithBreadcrumbs('backend.pages.place.create');
    }

    public function store(StorePlaceRequest $request): RedirectResponse
    {
        $this->authorize('create', Place::class);

        $this->placeService->create($request->validated());

        session()->flash('success', __('Place has been created.'));

        return redirect()->route('admin.place.index');
    }

    public function edit(int $id): Renderable|RedirectResponse
    {
        $place = $this->placeService->findById($id);

        if (! $place) {
            session()->flash('error', __('Place not found.'));

            return back();
        }

        $this->authorize('update', $place);

        $this->setBreadcrumbTitle(__('Edit Place'))
            ->addBreadcrumbItem(__('Places'), route('admin.place.index'));

        return $this->renderViewWithBreadcrumbs('backend.pages.place.edit', [
            'place' => $place,
        ]);
    }

    public function update(UpdatePlaceRequest $request, int $id): RedirectResponse
    {
        $place = $this->placeService->findById($id);

        if (! $place) {
            session()->flash('error', __('Place not found.'));

            return back();
        }

        $this->authorize('update', $place);

        $this->placeService->update($place, $request->validated());

        session()->flash('success', __('Place has been updated.'));

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $place = $this->placeService->findById($id);

        if (! $place) {
            session()->flash('error', __('Place not found.'));

            return back();
        }

        $this->authorize('delete', $place);

        $this->placeService->delete($place);

        session()->flash('success', __('Place has been deleted.'));

        return redirect()->route('admin.place.index');
    }

    /**
     * Delete multiple places at once
     */
    public function bulkDelete(BulkDeleteRequest $request): RedirectResponse
    {
        $this->authorize('bulkDelete', Place::class);

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('admin.place.index')
                ->with('error', __('No places selected for deletion'));
        }

        $deletedCount = 0;

        foreach ($ids as $id) {
            $place = $this->placeService->findById((int) $id);
            if (! $place) {
                continue;
            }

            $this->placeService->delete($place);
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            session()->flash('success', __(':count places deleted successfully', ['count' => $deletedCount]));
        } else {
            session()->flash('error', __('No places were deleted.'));
        }

        return redirect()->route('admin.place.index');
    }
}

