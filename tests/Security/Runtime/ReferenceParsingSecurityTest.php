<?php

declare(strict_types=1);

use verbb\formie\helpers\References;

it('keeps non-reference brace content literal during reference parsing', function (): void {
    $form = formie()
        ->form(['title' => 'Reference Parsing Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Security Tester',
    ])->save();

    $content = 'Literal braces: { not-a-token } and {field:' . 'missing';
    $parsed = References::parseContent($content, $submission);

    expect($parsed)->toBe($content);
})->group('security');

it('only resolves exact token strings through parseValue and leaves mixed strings untouched', function (): void {
    $form = formie()
        ->form(['title' => 'Reference ParseValue Security'])
        ->singleLineTextField('fullName')
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $submission = formie()->submission($form)->with([
        'fullName' => 'Security Tester',
    ])->save();

    $token = References::field((string)$field->reference);
    $mixed = 'prefix ' . $token . ' suffix';

    expect(References::parseValue($token, $submission))->toBe('Security Tester')
        ->and(References::parseValue($mixed, $submission))->toBe($mixed);
})->group('security');

it('keeps submitted hidden field template and reference payloads literal', function (): void {
    $payload = "{{ 7 * 7 }} {{ craft.app.cache.cachePath }} {system:email}";

    $form = formie()
        ->form(['title' => 'Hidden SSTI Security'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => 'safe-default',
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'trackingToken' => $payload,
    ])->save();

    expect($submission->getFieldValue('trackingToken'))->toBe($payload);
})->group('security');

it('still resolves admin-authored hidden field defaults when no value is submitted', function (): void {
    $form = formie()
        ->form(['title' => 'Hidden Default Reference Security'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => 'fallback-{form:handle}',
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'trackingToken' => '',
    ])->save();

    expect($submission->getFieldValue('trackingToken'))->toBe('fallback-' . $form->handle);
})->group('security');
