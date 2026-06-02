<?php

declare(strict_types=1);

use Tests\Support\FieldCapabilityMatrix;
use verbb\formie\factories\FormFactory;

it('keeps capability matrix methods aligned with form factory shortcuts', function (): void {
    $requiredCases = FieldCapabilityMatrix::requiredFlagMethods();
    $unsupported = FieldCapabilityMatrix::requiredUnsupportedMethods();

    foreach ($requiredCases as [$method]) {
        expect(method_exists(FormFactory::class, $method))->toBeTrue();
    }

    foreach ($unsupported as [$method]) {
        expect(method_exists(FormFactory::class, $method))->toBeTrue();
    }
});

it('enforces explicit unsupported required-flag exceptions with reasons', function (): void {
    foreach (FieldCapabilityMatrix::requiredUnsupportedMethods() as [$method, $reason]) {
        expect($reason)->not->toBeEmpty();

        $form = formie()
            ->form(['title' => 'Unsupported Required ' . $method . ' ' . uniqid()])
            ->{$method}('fieldHandle', ['required' => true])
            ->create();

        $field = $form->getFieldByHandle('fieldHandle');

        expect((bool)($field?->required ?? false))->toBeFalse();
    }
});

it('enforces explicit integration conversion exclusions with reasons', function (): void {
    foreach (FieldCapabilityMatrix::integrationExcludedHandles() as [$handle, $reason]) {
        expect($handle)->not->toBeEmpty()
            ->and($reason)->not->toBeEmpty();
    }
});
