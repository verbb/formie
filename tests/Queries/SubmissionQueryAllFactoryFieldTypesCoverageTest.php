<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\factories\FormFactory;

it('classifies factory query contracts and verifies the scalar equality subset', function (): void {
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
    $coveredElsewhere = [
        'dateField' => 'SubmissionQueryDatePartsCriteriaTest.php',
        'groupField' => 'SubmissionQueryNestedParentMatrixTest.php',
        'repeaterField' => 'SubmissionQueryNestedParentMatrixTest.php',
        'nameField' => 'SubmissionQueryNamePermutationMatrixTest.php',
        'checkboxesField' => 'SubmissionQueryMultiOptionCriteriaTest.php',
        'phoneField' => 'SubmissionQueryAllFieldTypesMatrixTest.php',
        'addressField' => 'SubmissionQueryNestedParentMatrixTest.php',
    ];
    $notApplicable = [
        'headingField', 'htmlField', 'contentField', 'noteField', 'missingField', 'sectionField', 'summaryField',
    ];
    $notCoveredHere = [
        'agreeField' => 'Boolean-style semantics need dedicated operator coverage (not scalar-equality only).',
        'calculationsField' => 'Computed/runtime field; query semantics depend on calculation pipeline.',
        'categoriesField' => 'Element relation field; requires category fixtures and relational query assertions.',
        'entriesField' => 'Element relation field; requires entry fixtures and relational query assertions.',
        'fileUploadField' => 'Asset relation field; requires asset fixtures and relation query semantics.',
        'formsField' => 'Form relation-like semantics; requires dedicated fixture setup.',
        'passwordField' => 'Masked/security-sensitive semantics need dedicated query contract decision.',
        'paymentField' => 'Payment/runtime integration semantics require integration-aware fixtures.',
        'productsField' => 'Commerce relation field; requires product fixtures and relational assertions.',
        'recipientsField' => 'Option-array semantics need dedicated contains/overlap operator coverage.',
        'signatureField' => 'Blob/image-like value semantics need dedicated query contract decision.',
        'submissionsField' => 'Submission relation field; requires relational fixture setup.',
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

    $classifiedMethods = array_merge(array_keys($queryableProfiles), array_keys($coveredElsewhere), $notApplicable, array_keys($notCoveredHere));
    sort($classifiedMethods);

    foreach ($coveredElsewhere as $file) {
        expect(is_file(__DIR__ . '/' . $file))->toBeTrue();
    }
    // Classification is an inventory, not evidence that every field is covered.
    expect($classifiedMethods)->toEqual($factoryMethods);
});
