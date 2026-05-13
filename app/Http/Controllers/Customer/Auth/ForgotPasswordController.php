<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function showLinkRequestForm(): \Illuminate\View\View
    {
        return view('customer.auth.passwords.email');
    }

    protected function sendResetLinkResponse(Request $request, $response): \Illuminate\Http\RedirectResponse
    {
        return back()->with('success', trans($response));
    }
}
