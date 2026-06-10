<?php

declare(strict_types=1);

use verbb\formie\factories\FormFactory;
use verbb\formie\fields\SingleLineText;

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
