<?php

declare(strict_types=1);

use verbb\formie\gql\types\FormSettingsType;

it('exposes stable form settings fields in graphql type surface', function (): void {
    $type = FormSettingsType::getType();
    $fields = array_keys($type->getFields());

    expect($fields)->toContain('displayFormTitle')
        ->and($fields)->toContain('displayPageTabs')
        ->and($fields)->toContain('submitMethod')
        ->and($fields)->toContain('completionBehavior')
        ->and($fields)->toContain('completionRedirectSource')
        ->and($fields)->toContain('submitAction')
        ->and($fields)->toContain('successMessageHtml')
        ->and($fields)->toContain('successMessageJson')
        ->and($fields)->toContain('errorMessageJson')
        ->and($fields)->toContain('redirectUrl')
        ->and($fields)->toContain('integrations');
});

it('keeps risky html-bearing graphql settings fields explicitly named as html contracts', function (): void {
    $type = FormSettingsType::getType();
    $fields = $type->getFields();
    $successMessageField = $fields['successMessageHtml'];
    $errorMessageField = $fields['errorMessageHtml'];

    expect($successMessageField->getType()->name ?? null)->toBe('String')
        ->and($errorMessageField->getType()->name ?? null)->toBe('String')
        ->and($successMessageField->description ?? null)->toContain('success message')
        ->and($errorMessageField->description ?? null)->toContain('error message');
});
