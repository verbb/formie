<?php

declare(strict_types=1);

use verbb\formie\fields\SingleLineText;

it('assembles a complete client-rendered definition from custom field hooks', function (): void {
    $field = new class(['handle' => 'reference', 'label' => 'Reference', 'defaultValue' => 'ABC']) extends SingleLineText {
        protected function defineClientRenderedType(): string
        {
            return 'custom-reference';
        }

        protected function defineClientRenderedInput(): array
        {
            return ['inputType' => 'text', 'format' => 'reference'];
        }
    };

    $definition = $field->getClientRenderedDefinition();

    expect($definition['type'])->toBe('custom-reference')
        ->and($definition['input']['format'])->toBe('reference')
        ->and($definition['input']['defaultValue'])->toBe('ABC')
        ->and($definition['input']['fieldKind'])->toBe($field->fieldKind())
        ->and($definition['client']['children']['model'])->toBe('scalar')
        ->and($definition['client']['valueType'])->not->toHaveKey('class')
        ->and($definition)->toHaveKeys(['id', 'uid', 'handle', 'condition', 'validation', 'moduleRefs']);
});
