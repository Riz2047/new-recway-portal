<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailTemplate\StoreEmailTemplateRequest;
use App\Http\Requests\EmailTemplate\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Support\EmailTemplateVariable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;

class EmailTemplateController extends Controller
{
    protected function routePrefix(): string
    {
        return request()->segment(1) === 'staff' ? 'staff' : 'admin';
    }

    public function index(): Renderable
    {
        $this->authorize('viewAny', EmailTemplate::class);

        $this->setBreadcrumbTitle(__('Email Templates'));

        return $this->renderViewWithBreadcrumbs('backend.pages.email-templates.index', [
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function create(): Renderable
    {
        $this->authorize('create', EmailTemplate::class);

        $prefix = $this->routePrefix();

        $this->setBreadcrumbTitle(__('New Email Template'))
            ->addBreadcrumbItem(__('Email Templates'), route("{$prefix}.email-templates.index"));

        return $this->renderViewWithBreadcrumbs('backend.pages.email-templates.create', [
            'routePrefix' => $prefix,
        ]);
    }

    public function store(StoreEmailTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', EmailTemplate::class);

        $data = $request->validated();
        $prefix = $this->routePrefix();

        EmailTemplate::query()->create([
            'title' => $data['title'],
            'variable' => EmailTemplateVariable::fromTitle($data['title']),
            'body' => $data['body'] ?? null,
        ]);

        session()->flash('success', __('Email template has been created.'));

        return redirect()->route("{$prefix}.email-templates.index");
    }

    public function edit(int $emailTemplate): Renderable|RedirectResponse
    {
        $template = EmailTemplate::query()->find($emailTemplate);

        if ($template === null) {
            session()->flash('error', __('Email template not found.'));

            return back();
        }

        $this->authorize('update', $template);

        $prefix = $this->routePrefix();

        $this->setBreadcrumbTitle(__('Edit Email Template'))
            ->addBreadcrumbItem(__('Email Templates'), route("{$prefix}.email-templates.index"));

        return $this->renderViewWithBreadcrumbs('backend.pages.email-templates.edit', [
            'template' => $template,
            'routePrefix' => $prefix,
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, int $emailTemplate): RedirectResponse
    {
        $template = EmailTemplate::query()->find($emailTemplate);

        if ($template === null) {
            session()->flash('error', __('Email template not found.'));

            return back();
        }

        $this->authorize('update', $template);

        $data = $request->validated();
        $prefix = $this->routePrefix();

        $template->update([
            'title' => $data['title'],
            'variable' => EmailTemplateVariable::fromTitle($data['title']),
            'body' => $data['body'] ?? null,
        ]);

        session()->flash('success', __('Email template has been updated.'));

        return redirect()->route("{$prefix}.email-templates.index");
    }

    public function destroy(int $emailTemplate): RedirectResponse
    {
        $template = EmailTemplate::query()->find($emailTemplate);

        if ($template === null) {
            session()->flash('error', __('Email template not found.'));

            return back();
        }

        $this->authorize('delete', $template);

        $prefix = $this->routePrefix();

        $template->delete();

        session()->flash('success', __('Email template has been deleted.'));

        return redirect()->route("{$prefix}.email-templates.index");
    }
}
