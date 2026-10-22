<?php

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\Date;
use verbb\formie\fields\Group;
use verbb\formie\fields\Repeater;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\DateDayNumber;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\ValidationMessagesHelper;
use craft\base\Element;
use craft\helpers\Json;

it('preserves nested validation messages through builder saves and database reloads', function (string $container, string $type): void {
    $form = formie()->form()->{$container}('details', [
        'rows' => [['fields' => [[
            'type' => $type,
            'handle' => 'answer',
            'label' => 'Answer',
            'required' => true,
        ]]]],
    ])->create();
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $messages = ['required' => 'Please complete {label}.', 'invalid' => 'Check {label}.'];
    $pages[0]['rows'][0]['fields'][0]['rows'][0]['fields'][0]['validationMessages'] = $messages;

    for ($save = 0; $save < 2; $save++) {
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder(Json::decode(Json::encode($pages)), $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $child = $form->getFieldByHandle('details')->getFieldByHandle('answer');

        expect($child->required)->toBeTrue()
            ->and($child->validationMessages)->toBe($messages)
            ->and($child->getValidationMessage('required'))->toBe('Please complete Answer.')
            ->and($child->getValidationMessage('invalid'))->toBe('Check Answer.')
            ->and($child->getValidationMessageBrowserAttribute('required'))->toBe('Please complete Answer.');

        $form->title .= ' edited';
        $pages = $form->getFormLayout()->getFormBuilderConfig();
        expect($pages[0]['rows'][0]['fields'][0]['rows'][0]['fields'][0]['validationMessages'])->toBe($messages);
    }

    if ($type === SingleLineText::class) {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setScenario(Element::SCENARIO_LIVE);
        $submission->setFieldValue('details', $container === 'repeaterField' ? [['answer' => '']] : ['answer' => '']);
        expect($submission->validate())->toBeFalse()
            ->and($submission->getErrorSummary(true))->toContain('Please complete Answer.');
    }
})->with(['groupField', 'repeaterField'])->with([SingleLineText::class, Calculations::class]);

it('keeps ordinary nested field overrides independent of container messages', function (string $container): void {
    $parent = new $container([
        'handle' => 'details',
        'validationMessages' => ['required' => 'Container message.'],
    ]);
    $child = new SingleLineText([
        'handle' => 'answer',
        'label' => 'Answer',
        'errorMessage' => 'Legacy {label}.',
        'validationMessages' => ['invalid' => 'Check {label}.'],
    ]);
    $child->setParentField($parent);
    $bound = $child->withParentField($parent, 0);

    expect($child->errorMessage)->toBe('Legacy {label}.')
        ->and($bound->getValidationMessage('required'))->toBe('Legacy Answer.')
        ->and($bound->getValidationMessage('invalid'))->toBe('Check Answer.');

    $child->errorMessage = null;
    unset($child->validationMessages['required']);
    expect(ValidationMessagesHelper::override($child, 'required'))->toBeNull()
        ->and($child->getValidationMessage('required'))->toBe('Answer cannot be blank.');
})->with([Group::class, Repeater::class]);

it('retains compound field validation messages when the compound field is nested', function (string $container): void {
    $parent = new $container(['handle' => 'details']);
    $date = new Date([
        'handle' => 'birthday',
        'displayType' => 'inputs',
        'validationMessages' => ['required' => 'Please provide {label}.'],
    ]);
    $date->setParentField($parent);
    $day = new DateDayNumber(['handle' => 'day', 'label' => 'Day']);
    $day->setParentField($date);

    expect($date->validationMessages)->toBe(['required' => 'Please provide {label}.'])
        ->and($day->getValidationMessage('required'))->toBe('Please provide Day.')
        ->and($day->getValidationMessageBrowserAttribute('required'))->toBe('Please provide Day.');
})->with([Group::class, Repeater::class]);
