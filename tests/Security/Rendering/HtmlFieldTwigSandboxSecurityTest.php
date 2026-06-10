<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\fields\Html;
use Twig\Sandbox\SecurityNotAllowedPropertyError;

it('defaults html fields to twig disabled', function (): void {
    $field = new Html();

    expect($field->allowTwig)->toBeFalse();
})->group('security');

it('blocks craft environment access in sandboxed html field twig', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Sandbox'])
        ->htmlField('notice', [
            'htmlContent' => '{{ craft.app.cache.cachePath }}',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $field = $form->getFieldByHandle('notice');

    expect(fn() => $field?->getRenderedHtmlBlock($form, null, null))
        ->toThrow(SecurityNotAllowedPropertyError::class);
})->group('security');

it('allows sandboxed html field twig to read form and submission context', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Context'])
        ->htmlField('notice', [
            'htmlContent' => '<p>{{ form.title }} · {{ submission.id ?? "new" }}</p>',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $submission = formie()->submission($form)->save();
    $field = $form->getFieldByHandle('notice');
    $html = $field?->getRenderedHtmlBlock($form, null, $submission);

    expect($html)->toBeString()
        ->toContain('<p>HTML Field Twig Context · ' . $submission->id . '</p>')
        ->not->toContain('{{');
})->group('security');

it('still purifies hostile twig output from html fields', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Purify'])
        ->htmlField('notice', [
            'htmlContent' => '<p>{{ "safe-text" }}</p>' . MaliciousPayloads::storedXssProbe(),
            'allowTwig' => true,
            'purifyContent' => true,
        ])
        ->create();

    $field = $form->getFieldByHandle('notice');
    $html = $field?->getRenderedHtmlBlock($form, null, null);

    expect($html)->toBeString()
        ->toContain('safe-text')
        ->not->toContain('<script')
        ->not->toContain('onerror=');
})->group('security');
