<?php

declare(strict_types=1);

use verbb\formie\Formie;

it('registers built-in client event templates', function (): void {
    $templates = Formie::$plugin->getClientEventTemplates()->getBuilderConfig();

    expect($templates)->not->toBeEmpty();

    $handles = array_column($templates, 'handle');

    expect($handles)->toContain('gtm-page-submit')
        ->and($handles)->toContain('ga4-generate-lead')
        ->and($handles)->toContain('meta-lead')
        ->and($handles)->toContain('blank');
});

it('exposes builder-safe template config', function (): void {
    $template = Formie::$plugin->getClientEventTemplates()->getTemplate('gtm-page-submit');

    expect($template)->not->toBeNull()
        ->and($template['event'])->toBe('formPageSubmission')
        ->and($template['payload'])->not->toBeEmpty();
});
