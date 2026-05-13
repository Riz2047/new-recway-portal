<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/customer/dashboard';

    public function showResetForm(Request $request, string $token = ''): \Illuminate\View\View
    {
        return view('customer.auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email,
        ]);
    }
}
