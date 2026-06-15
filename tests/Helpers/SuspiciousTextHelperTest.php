<?php

declare(strict_types=1);

use verbb\formie\helpers\SuspiciousTextHelper;

it('flags obvious keyboard spam', function (): void {
    $analysis = SuspiciousTextHelper::analyze('asdfghjkl qwertyuiop zxcvbnm');

    expect($analysis['is_suspicious'])->toBeTrue()
        ->and($analysis['score'])->toBeGreaterThanOrEqual(2);
});

it('allows normal prose and common short words', function (): void {
    $analysis = SuspiciousTextHelper::analyze('Hi John, thanks for getting in touch about the project.');

    expect($analysis['is_suspicious'])->toBeFalse();
});

it('allows configured terms such as product codes', function (): void {
    $analysis = SuspiciousTextHelper::analyze('Please send pricing for SKU RFP-2026.', ['RFP']);

    expect($analysis['is_suspicious'])->toBeFalse();
});

it('flags repeated low variety strings', function (): void {
    $analysis = SuspiciousTextHelper::analyze('zzzzzzzzzz');

    expect($analysis['is_suspicious'])->toBeTrue();
});

it('ignores urls and email addresses during analysis', function (): void {
    $analysis = SuspiciousTextHelper::analyze('Contact me at hello@example.com or https://example.com');

    expect($analysis['is_suspicious'])->toBeFalse();
});

it('flags clusters of short junk tokens', function (): void {
    $analysis = SuspiciousTextHelper::analyze('asd qwe zxc bbb');

    expect($analysis['is_suspicious'])->toBeTrue();
});
