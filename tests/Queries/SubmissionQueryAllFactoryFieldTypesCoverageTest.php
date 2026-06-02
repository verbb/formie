<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\factories\FormFactory;

it('tracks submission-query coverage across all factory field methods', function (): void {
    $options = [
        ['label' => 'One', 'value' => 'one'],
        ['label' => 'Two', 'value' => 'two'],
    ];

    // Field types with deterministic scalar-like query semantics in current architecture.
    $queryableProfiles = [
        'singleLineTextField' => [
            'handle' => 'singleValue',
            'fieldConfig' => [],
            'matchValue' => 'Single Match',
            'otherValue' => 'Single Other',
            'queryValue' => 'Single Match',
        ],
        'multiLineTextField' => [
            'handle' => 'multiValue',
            'fieldConfig' => [],
            'matchValue' => 'Multi Match',
            'otherValue' => 'Multi Other',
            'queryValue' => 'Multi Match',
        ],
        'emailField' => [
            'handle' => 'emailValue',
            'fieldConfig' => [],
            'matchValue' => 'match@example.test',
            'otherValue' => 'other@example.test',
            'queryValue' => 'match@example.test',
        ],
        'numberField' => [
            'handle' => 'numberValue',
            'fieldConfig' => [],
            'matchValue' => '42',
            'otherValue' => '99',
            'queryValue' => '42',
        ],
        'dropdownField' => [
            'handle' => 'dropdownValue',
            'fieldConfig' => ['options' => $options],
            'matchValue' => 'one',
            'otherValue' => 'two',
            'queryValue' => 'one',
        ],
        'radioField' => [
            'handle' => 'radioValue',
            'fieldConfig' => ['options' => $options],
            'matchValue' => 'one',
            'otherValue' => 'two',
            'queryValue' => 'one',
        ],
        'hiddenField' => [
            'handle' => 'hiddenValue',
            'fieldConfig' => [],
            'matchValue' => 'hidden-match',
            'otherValue' => 'hidden-other',
            'queryValue' => 'hidden-match',
        ],
    ];

    // Field types currently not deterministic for simple value-equality query tests,
    // or requiring external fixtures/entities/runtime dependencies in test setup.
    $unsupportedProfiles = [
        'addressField' => 'Complex value object with nested selectors; equality semantics not finalized.',
        'agreeField' => 'Boolean-style semantics need dedicated operator coverage (not scalar-equality only).',
        'calculationsField' => 'Computed/runtime field; query semantics depend on calculation pipeline.',
        'categoriesField' => 'Element relation field; requires category fixtures and relational query assertions.',
        'checkboxesField' => 'Array/options semantics require dedicated contains/overlap operator coverage.',
        'dateField' => 'Date query syntax should follow Craft operator/range semantics; value handling in progress.',
        'entriesField' => 'Element relation field; requires entry fixtures and relational query assertions.',
        'fileUploadField' => 'Asset relation field; requires asset fixtures and relation query semantics.',
        'formsField' => 'Form relation-like semantics; requires dedicated fixture setup.',
        'groupField' => 'Nested parent-field semantics require selector-path query coverage.',
        'headingField' => 'Presentation-only field with no stored submission value.',
        'htmlField' => 'Presentation-only field with no stored submission value.',
        'missingField' => 'Placeholder/recovery field, not a deterministic query target.',
        'nameField' => 'Fixed-parent field with variant storage semantics; requires dedicated selector coverage.',
        'passwordField' => 'Masked/security-sensitive semantics need dedicated query contract decision.',
        'paymentField' => 'Payment/runtime integration semantics require integration-aware fixtures.',
        'phoneField' => 'Structured value semantics; requires dedicated canonicalization query coverage.',
        'productsField' => 'Commerce relation field; requires product fixtures and relational assertions.',
        'recipientsField' => 'Option-array semantics need dedicated contains/overlap operator coverage.',
        'repeaterField' => 'Nested repeatable semantics require path/contains query coverage.',
        'sectionField' => 'Presentation-only field with no stored submission value.',
        'signatureField' => 'Blob/image-like value semantics need dedicated query contract decision.',
        'submissionsField' => 'Submission relation field; requires relational fixture setup.',
        'summaryField' => 'Presentation/runtime field, not a primary stored submission value target.',
        'tableField' => 'Complex tabular/nested value semantics require dedicated query operators.',
        'tagsField' => 'Element relation field; requires tag fixtures and relational query assertions.',
        'usersField' => 'Element relation field; requires user fixtures and relational query assertions.',
        'variantsField' => 'Commerce relation field; requires variant fixtures and relational assertions.',
    ];

    foreach ($queryableProfiles as $method => $profile) {
        $form = formie()
            ->form(['title' => "Factory Query Coverage {$method}"]);

        $form->{$method}($profile['handle'], $profile['fieldConfig']);
        $form = $form->create();

        $matching = formie()->submission($form)->with([
            $profile['handle'] => $profile['matchValue'],
        ])->save();

        formie()->submission($form)->with([
            $profile['handle'] => $profile['otherValue'],
        ])->save();

        $results = Submission::find()
            ->formId($form->id)
            ->field($profile['handle'], $profile['queryValue'])
            ->all();

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe($matching->id);
    }

    $factoryMethods = array_values(array_filter(
        get_class_methods(FormFactory::class),
        static fn(string $method) => str_ends_with($method, 'Field') && $method !== 'addField'
    ));

    sort($factoryMethods);

    $classifiedMethods = array_merge(array_keys($queryableProfiles), array_keys($unsupportedProfiles));
    sort($classifiedMethods);

    // Coverage contract: every factory field builder must be explicitly classified.
    expect($classifiedMethods)->toEqual($factoryMethods);
});
