<?php

declare(strict_types=1);

use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\models\RichText;

it('normalizes legacy markdown instructions into rich text schema', function (): void {
    $field = new SingleLineText([
        'handle' => 'example',
        'label' => 'Example',
        'instructions' => 'Read **this guide** for [details](https://example.test).',
    ]);

    expect($field->hasInstructions())->toBeTrue();
    expect((string)$field->getInstructionsHtml())->toContain('<strong>this guide</strong>');
    expect((string)$field->getInstructionsHtml())->toContain('href="https://example.test"');
});

it('normalizes legacy plain-text instructions into rich text schema', function (): void {
    $field = new SingleLineText([
        'handle' => 'example',
        'label' => 'Example',
        'instructions' => 'Choose carefully and compare each option.',
    ]);

    expect($field->hasInstructions())->toBeTrue();
    expect($field->instructions->getSchema())->toBe([
        [
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Choose carefully and compare each option.',
                ],
            ],
        ],
    ]);
});

it('renders instructions html with entry links and target blank', function (): void {
    $field = new Checkboxes([
        'handle' => 'options',
        'label' => 'Options',
        'instructions' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Read more in ',
                    ],
                    [
                        'type' => 'text',
                        'marks' => [
                            [
                                'type' => 'link',
                                'attrs' => [
                                    'href' => 'https://example.test/workshop',
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                        'text' => 'this guide',
                    ],
                    [
                        'type' => 'text',
                        'text' => '.',
                    ],
                ],
            ],
        ],
        'options' => [
            ['label' => 'Option A', 'value' => 'a'],
        ],
    ]);

    expect($field)->toBeInstanceOf(Checkboxes::class);
    expect((string)$field->getInstructionsHtml())->toContain('href="https://example.test/workshop"');
    expect((string)$field->getInstructionsHtml())->toContain('target="_blank"');
});

it('serializes instructions as rich text schema in field settings', function (): void {
    $field = new SingleLineText([
        'handle' => 'example',
        'label' => 'Example',
        'instructions' => 'Legacy instructions',
    ]);

    expect($field->getSettings()['instructions'])->toBe($field->instructions->getSchema());
});

it('exposes instructions rich text config for field settings', function (): void {
    $config = RichTextHelper::getRichTextConfig('fields.instructions');

    expect($config['buttons'])->toBe(['bold', 'italic', 'link']);
    expect($config['rows'])->toBe(4);
    expect($config['linkOptions'])->not->toBeEmpty();
});
