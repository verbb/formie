<?php

declare(strict_types=1);

use verbb\formie\fields\Agree;
use verbb\formie\fields\Content;
use verbb\formie\fields\SingleLineText;
use verbb\formie\gql\interfaces\FieldInterface;
use verbb\formie\gql\types\FormSettingsType;
use verbb\formie\helpers\Gql as FormieGql;
use verbb\formie\models\FormSettings;
use verbb\formie\models\RichText;

it('resolves stored rich text as a doc json payload for graphql', function (): void {
    $richText = RichText::from([
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Hello'],
            ],
        ],
    ]);

    expect(FormieGql::resolveRichTextJson($richText))->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Hello'],
                ],
            ],
        ],
    ]);
});

it('returns null for empty rich text graphql json fields', function (): void {
    expect(FormieGql::resolveRichTextJson(RichText::from(null)))->toBeNull();
});

it('exposes json siblings for form settings html message fields', function (): void {
    $type = FormSettingsType::getType();
    $fields = $type->getFields();

    expect(array_keys($fields))->toContain('submitActionMessageJson', 'errorMessageJson')
        ->and($fields['submitActionMessageJson']->getType()->name)->toBe('Json')
        ->and($fields['errorMessageJson']->getType()->name)->toBe('Json');
});

it('resolves form settings message json fields from rich text storage', function (): void {
    $settings = new FormSettings([
        'submitActionMessage' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Thanks!'],
                ],
            ],
        ],
        'errorMessage' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Oops'],
                ],
            ],
        ],
    ]);

    expect(FormieGql::resolveRichTextJson($settings->submitActionMessage))->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Thanks!'],
                ],
            ],
        ],
    ])->and(FormieGql::resolveRichTextJson($settings->errorMessage))->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Oops'],
                ],
            ],
        ],
    ]);
});

it('exposes instructionsJson on the field interface', function (): void {
    $definitions = FieldInterface::getFieldDefinitions();

    expect($definitions)->toHaveKey('instructionsJson')
        ->and($definitions['instructionsJson']['type']->name)->toBe('Json');
});

it('resolves field and agree rich text json fields', function (): void {
    $field = new SingleLineText([
        'handle' => 'example',
        'label' => 'Example',
        'instructions' => 'Plain instructions',
    ]);

    $definitions = FieldInterface::getFieldDefinitions();
    $instructionsJson = $definitions['instructionsJson'];

    expect($instructionsJson['resolve']($field, [], null, null))->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Plain instructions'],
                ],
            ],
        ],
    ]);

    $agree = new Agree([
        'handle' => 'agree',
        'label' => 'Agree',
        'description' => '<p>Please agree</p>',
    ]);

    $descriptionJson = $agree->getSettingGqlTypes()['descriptionJson'];

    expect($descriptionJson['resolve']($agree, [], null, null))->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Please agree'],
                ],
            ],
        ],
    ]);

    $content = new Content([
        'handle' => 'content',
        'label' => 'Content',
        'content' => '<p>Body copy</p>',
    ]);

    $contentJson = $content->getSettingGqlTypes()['contentJson'];

    expect($contentJson['resolve']($content, [], null, null))->toBe([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Body copy'],
                ],
            ],
        ],
    ]);
});
