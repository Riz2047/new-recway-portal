<?php

declare(strict_types=1);

use App\Support\EmailTemplateVariable;

it('replaces whitespace with underscores', function () {
    expect(EmailTemplateVariable::fromTitle('Welcome Email'))->toBe('Welcome_Email');
});

it('collapses multiple spaces', function () {
    expect(EmailTemplateVariable::fromTitle('A  B   C'))->toBe('A_B_C');
});

it('returns underscore for empty string after trim', function () {
    expect(EmailTemplateVariable::fromTitle('   '))->toBe('_');
});
