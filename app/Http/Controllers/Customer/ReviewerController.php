<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Reviewer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ReviewerController extends Controller
{
    private function getCustomerId(): ?int
    {
        return Customer::where('user_id', Auth::id())->value('id');
    }

    public function index(): View
    {
        $customerId = $this->getCustomerId();
        $reviewers  = Reviewer::where('cus_id', $customerId)
            ->orderBy('email')
            ->get();

        return view('customer.reviewers.index', compact('reviewers'));
    }

    public function create(): View
    {
        return view('customer.reviewers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $customerId = $this->getCustomerId();

        $request->validate([
            'email'    => 'required|email|unique:reviewers,email',
            'password' => 'required|min:6',
        ]);

        Reviewer::create([
            'cus_id'   => $customerId,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('customer.reviewers.index')
            ->with('success', __('Reviewer added successfully.'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $customerId = $this->getCustomerId();
        $reviewer   = Reviewer::where('cus_id', $customerId)->findOrFail($id);

        return view('customer.reviewers.edit', compact('reviewer'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $customerId = $this->getCustomerId();
        $reviewer   = Reviewer::where('cus_id', $customerId)->findOrFail($id);

        $request->validate([
            'email'    => 'required|email|unique:reviewers,email,' . $id,
            'password' => 'nullable|min:6',
        ]);

        $reviewer->email = $request->email;
        if ($request->filled('password')) {
            $reviewer->password = Hash::make($request->password);
        }
        $reviewer->save();

        return redirect()->route('customer.reviewers.index')
            ->with('success', __('Reviewer updated successfully.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $customerId = $this->getCustomerId();
        Reviewer::where('cus_id', $customerId)->findOrFail($id)->delete();

        return redirect()->route('customer.reviewers.index')
            ->with('success', __('Reviewer removed.'));
    }
}
