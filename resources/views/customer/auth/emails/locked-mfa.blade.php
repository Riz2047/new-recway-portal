<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px;">
<div style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:8px;padding:32px;border:1px solid #e5e7eb;">
    <h2 style="color:#1f3564;margin:0 0 16px;">{{ __('Account Recovery Code') }}</h2>
    <p style="color:#374151;font-size:14px;">{{ __('Hello :name,', ['name' => $name]) }}</p>
    <p style="color:#374151;font-size:14px;">
        {{ __('We received a request to unlock your account. Use the code below to verify your identity.') }}
    </p>
    <div style="text-align:center;margin:24px 0;">
        <span style="display:inline-block;background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;
                     padding:16px 32px;font-size:32px;font-weight:bold;letter-spacing:8px;color:#1f3564;">
            {{ $otp }}
        </span>
    </div>
    <p style="color:#6b7280;font-size:13px;">
        {{ __('This code expires in 24 hours. If you did not request this, please contact support immediately.') }}
    </p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
    <p style="color:#9ca3af;font-size:12px;margin:0;">{{ config('app.name') }}</p>
</div>
</body>
</html>
