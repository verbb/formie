<?php

declare(strict_types=1);

use verbb\formie\helpers\SpamHelper;

it('parses email allowlists into domains and full addresses', function (): void {
    $allowlist = SpamHelper::parseEmailAllowlist("company.com\nuser@example.com\n TRUSTED@Company.COM \n");

    expect($allowlist['domains'])->toBe(['company.com'])
        ->and($allowlist['emails'])->toBe(['trusted@company.com']);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('matches allowlisted domains and full email addresses', function (): void {
    $allowlist = SpamHelper::parseEmailAllowlist("company.com\nuser@example.com\n");

    expect(SpamHelper::emailMatchesAllowlist('staff@company.com', $allowlist))->toBeTrue()
        ->and(SpamHelper::emailMatchesAllowlist('user@example.com', $allowlist))->toBeTrue()
        ->and(SpamHelper::emailMatchesAllowlist('other@example.com', $allowlist))->toBeFalse();
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');
