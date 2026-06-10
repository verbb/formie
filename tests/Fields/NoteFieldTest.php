<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\fields\Note;

it('registers note as a builder field', function (): void {
    $field = new Note();

    expect($field->getIsBuilderField())->toBeTrue()
        ->and($field->getIsCosmetic())->toBeTrue()
        ->and(Note::displayName())->toBe('Note')
        ->and($field->defineFormBuilderAdvancedSchema())->toBe([]);
})->group('fields');

it('auto-generates label and handle for new note fields', function (): void {
    $field = new Note();

    $settings = $field->modifyFieldSettings([
        'type' => Note::class,
        'noteText' => 'Builder guidance',
        'noteStyle' => 'warning',
    ]);

    expect($settings['label'] ?? '')->toStartWith('Note ')
        ->and($settings['handle'] ?? '')->toStartWith('note')
        ->and($settings['noteText'])->toBe('Builder guidance');
})->group('fields');

it('preserves provided label and handle in modifyFieldSettings for new note fields', function (): void {
    $field = new Note();

    $settings = $field->modifyFieldSettings([
        'type' => Note::class,
        'label' => 'Custom Note Label',
        'handle' => 'customNoteHandle',
        'noteText' => 'Builder guidance',
    ]);

    expect($settings['label'])->toBe('Custom Note Label')
        ->and($settings['handle'])->toBe('customNoteHandle');
})->group('fields');

it('auto-generates label and handle when note field is created from config', function (): void {
    $field = Formie::$plugin->getFields()->createField([
        'type' => Note::class,
        'noteText' => 'Do not remove this field.',
    ]);

    expect($field)->toBeInstanceOf(Note::class)
        ->and($field->label)->toStartWith('Note ')
        ->and($field->handle)->toStartWith('note');
})->group('fields');

it('excludes builder note fields from front-end client payload', function (): void {
    $form = formie()
        ->form(['title' => 'Builder Note Exclusion'])
        ->singleLineTextField('fullName')
        ->noteField('editorNote', [
            'noteText' => 'Do not remove the payment field.',
            'noteStyle' => 'warning',
        ])
        ->create();

    $page = $form->getFieldLayout()?->getPages()[0];
    $handles = [];

    foreach ($page?->getRows() ?? [] as $row) {
        foreach ($row->getClientPayload()['fields'] as $fieldPayload) {
            $handles[] = $fieldPayload['handle'];
        }
    }

    expect($handles)->toBe(['fullName']);
})->group('fields');

it('does not render builder note fields on the front end', function (): void {
    $form = formie()
        ->form(['title' => 'Builder Note Render'])
        ->noteField('editorNote', [
            'noteText' => 'Builder-only guidance.',
        ])
        ->create();

    $field = $form->getFieldByHandle('editorNote');

    expect($field)->toBeInstanceOf(Note::class)
        ->and(Formie::$plugin->getRendering()->renderField($form, $field))->toBeNull();
})->group('fields');

it('validates note styles', function (): void {
    $field = new Note([
        'handle' => 'note',
        'noteText' => 'Valid note',
        'noteStyle' => 'invalid-style',
    ]);

    expect($field->validate(['noteStyle']))->toBeFalse();
})->group('fields');
