<?php

use craft\base\Element;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\ValidationMessagesHelper;

it('persists clearing required messages through repeated builder saves', function (string $container): void {
    $settings = [
        'label' => 'Answer',
        'required' => true,
        'validationMessages' => ['required' => 'Custom {label}.', 'invalid' => 'Invalid {label}.'],
    ];
    $factory = formie()->form();

    if ($container === 'ordinary') {
        $factory->singleLineTextField('answer', $settings);
    } else {
        $factory->{$container}('details', ['rows' => [['fields' => [array_merge($settings, [
            'type' => SingleLineText::class,
            'handle' => 'answer',
        ])]]]]);
    }

    $form = $factory->create();
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $node = &$pages[0]['rows'][0]['fields'][0];

    if ($container !== 'ordinary') {
        $node = &$node['rows'][0]['fields'][0];
    }

    expect($node['errorMessage'])->toBe('Custom {label}.');
    $node['validationMessages']['required'] = '';
    unset($node);

    for ($save = 0; $save < 2; $save++) {
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder(Json::decode(Json::encode($pages)), $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $field = $container === 'ordinary' ? $form->getFieldByHandle('answer') : $form->getFieldByHandle('details')->getFieldByHandle('answer');

        expect($field->validationMessages)->toBe(['required' => '', 'invalid' => 'Invalid {label}.'])
            ->and($field->errorMessage)->toBeNull()
            ->and($field->getValidationMessage('required'))->toBe('Answer cannot be blank.')
            ->and($field->getValidationMessageBrowserAttribute('required'))->toBe('Answer cannot be blank.');
        $pages = $form->getFormLayout()->getFormBuilderConfig();
    }

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setScenario(Element::SCENARIO_LIVE);
    $submission->setFieldValues($container === 'ordinary' ? ['answer' => ''] : [
        'details' => $container === 'repeaterField' ? [['answer' => '']] : ['answer' => ''],
    ]);
    expect($submission->validate())->toBeFalse()
        ->and($submission->getErrorSummary(true))->toContain('Answer cannot be blank.');
})->with(['ordinary', 'groupField', 'repeaterField']);

it('honors explicit required messages over the legacy alias', function (mixed $message, ?string $expected): void {
    $field = new SingleLineText([
        'handle' => 'answer',
        'label' => 'Answer',
        'errorMessage' => 'Legacy {attribute}.',
        'validationMessages' => ['required' => $message],
    ]);

    expect(ValidationMessagesHelper::override($field, 'required'))->toBe($expected)
        ->and($field->errorMessage)->toBe($expected);
})->with([
    'empty' => ['', null],
    'null' => [null, null],
    'whitespace' => ['  ', null],
    'zero' => ['0', '0'],
    'custom' => ['New {name}.', 'New {label}.'],
]);

it('retains legacy-only required messages when the canonical key is absent', function (array $messages): void {
    $field = new SingleLineText([
        'errorMessage' => 'Legacy {attribute}.',
        'validationMessages' => $messages,
    ]);

    expect(ValidationMessagesHelper::override($field, 'required'))->toBe('Legacy {label}.')
        ->and($field->validationMessages['required'])->toBe('Legacy {label}.');
})->with([ [[]], [['invalid' => 'Invalid.']] ]);

it('clears site required messages without restoring the source legacy alias', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        test()->markTestSkipped('Requires the multisite runtime.');
    }

    $form = formie()->form()->singleLineTextField('answer', [
        'label' => 'Answer',
        'required' => true,
        'validationMessages' => ['required' => 'Source {label}.', 'invalid' => 'Invalid {label}.'],
    ])->create();
    $service = Formie::$plugin->getFormSiteOverrides();
    $sourceSiteId = $service->getSourceSiteId($form);
    $field = $form->getFieldByHandle('answer');

    foreach (Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($form) as $siteId) {
        if ($siteId === $sourceSiteId) {
            continue;
        }

        foreach (['Translated {label}.', ''] as $message) {
            $service->saveTranslationBundle((int)$form->id, $siteId, [
                'fieldOverrides' => [(string)$field->definitionId => ['validationMessages' => ['required' => $message]]],
            ]);
            $localized = $service->applyToForm($form, $siteId, true);
            $localizedField = $localized->getFieldByHandle('answer');
            $expected = $message === '' ? 'Answer cannot be blank.' : 'Translated Answer.';

            expect($localizedField->getValidationMessage('required'))->toBe($expected)
                ->and($localizedField->getValidationMessageBrowserAttribute('required'))->toBe($expected)
                ->and($localizedField->getValidationMessage('invalid'))->toBe('Invalid Answer.')
                ->and($field->getValidationMessage('required'))->toBe('Source Answer.');
        }
    }
});
