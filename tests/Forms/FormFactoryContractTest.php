<?php

declare(strict_types=1);

use verbb\formie\factories\FormFactory;
use verbb\formie\fields\SingleLineText;

it('continues generating valid unique handles after exhausting two letters', function (): void {
    $counter = new ReflectionProperty(FormFactory::class, 'autoHandleCounter');
    $before = $counter->getValue();

    try {
        $counter->setValue(null, 675);
        $forms = [formie()->form()->create(), formie()->form()->create()];
        expect($forms[0]->handle)->not->toBe($forms[1]->handle)
            ->and(strlen($forms[1]->handle))->toBeGreaterThanOrEqual(3);
    } finally {
        $counter->setValue(null, $before);
    }
});

it('exposes fluent shortcut methods for all canonical form field families', function (): void {
    $factory = formie()->form();
    $reflection = new \ReflectionClass($factory);

    $expectedMethods = [
        'addressField',
        'agreeField',
        'calculationsField',
        'categoriesField',
        'checkboxesField',
        'dateField',
        'dropdownField',
        'emailField',
        'entriesField',
        'fileUploadField',
        'formsField',
        'groupField',
        'headingField',
        'hiddenField',
        'htmlField',
        'contentField',
        'missingField',
        'multiLineTextField',
        'nameField',
        'numberField',
        'passwordField',
        'paymentField',
        'phoneField',
        'productsField',
        'radioField',
        'recipientsField',
        'repeaterField',
        'sectionField',
        'signatureField',
        'singleLineTextField',
        'submissionsField',
        'summaryField',
        'tableField',
        'tagsField',
        'usersField',
        'variantsField',
    ];

    foreach ($expectedMethods as $method) {
        expect($reflection->hasMethod($method))->toBeTrue();
    }
});

it('accepts explicit field classes and rejects non-field classes', function (): void {
    $validFactory = formie()->form()
        ->addField(SingleLineText::class, 'explicitClassField');

    expect($validFactory)->toBeInstanceOf(FormFactory::class);

    expect(fn() => formie()->form()->addField(stdClass::class, 'invalidClass'))
        ->toThrow(\InvalidArgumentException::class);
});

it('keeps required and page-selection fluent APIs chainable', function (): void {
    $form = formie()
        ->form(['title' => 'Factory Fluency'])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('first')
        ->required('first')
        ->onPage(2)
        ->singleLineTextField('second')
        ->create();

    expect($form->hasMultiplePages())->toBeTrue()
        ->and($form->getFieldByHandle('first'))->not->toBeNull()
        ->and($form->getFieldByHandle('second'))->not->toBeNull();
});


it('continues generating unique handles beyond the two-letter namespace', function (): void {
    $handles = [];
    for ($i = 0; $i < 700; $i++) {
        $form = formie()->form()->create();
        $handles[] = $form->handle;
    }
    expect(array_unique($handles))->toHaveCount(700);
})->group('slow');
