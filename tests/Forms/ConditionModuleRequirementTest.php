<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields\Group;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\BrowserModule;

// The conditions module is required by the page, so asking for it with nothing to attach to stops the form submitting.
function conditionModuleIds(Form $form): array
{
    $manifest = Formie::$plugin->getBrowserModuleManifestBuilder()->buildForSurface($form, BrowserModule::SURFACE_SERVER_RENDERED)->toArray();

    return array_column($manifest['entries'], 'moduleId');
}

function conditionModuleSettings(array $rows): array
{
    return ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => $rows];
}

it('does not ask for the conditions module when enabled conditions have no rules', function (array $rows): void {
    $saved = formie()->form(['title' => 'No condition rules'])
        ->singleLineTextField('target', ['enableConditions' => true, 'conditions' => conditionModuleSettings($rows)])
        ->create();

    expect(conditionModuleIds(Form::find()->id($saved->id)->one()))->not->toContain('formie:conditions');
})->with([
    'an empty list' => [[]],
    'a blank row' => [[['id' => 'new1', 'field' => '', 'value' => '', 'condition' => '']]],
]);

it('does not ask for the conditions module when conditions are switched off', function (): void {
    $saved = formie()->form(['title' => 'Conditions off'])
        ->singleLineTextField('source')
        ->singleLineTextField('target', ['enableConditions' => false, 'conditions' => conditionModuleSettings([['field' => '{field:source}', 'value' => 'yes', 'condition' => '=']])])
        ->create();

    expect(conditionModuleIds(Form::find()->id($saved->id)->one()))->not->toContain('formie:conditions');
});

it('still asks for the conditions module when a field has a real rule', function (): void {
    $saved = formie()->form(['title' => 'Real condition rule'])
        ->singleLineTextField('source')
        ->singleLineTextField('target', ['enableConditions' => true, 'conditions' => conditionModuleSettings([['field' => '{field:source}', 'value' => 'yes', 'condition' => '=']])])
        ->create();

    $form = Form::find()->id($saved->id)->one();

    expect(conditionModuleIds($form))->toContain('formie:conditions')
        ->and($form->getFieldByHandle('target')->getBrowserConditionsJson())->not->toBeNull();
});

it('checks fields inside a group the same way', function (array $rows, bool $expected): void {
    $saved = formie()->form(['title' => 'Nested condition rules'])
        ->singleLineTextField('source')
        ->groupField('group', ['rows' => [[
            'fields' => [[
                'type' => SingleLineText::class,
                'label' => 'Child',
                'handle' => 'child',
                'enableConditions' => true,
                'conditions' => conditionModuleSettings($rows),
            ]],
        ]]])
        ->create();

    $form = Form::find()->id($saved->id)->one();

    expect($form->getFieldByHandle('group'))->toBeInstanceOf(Group::class)
        ->and(in_array('formie:conditions', conditionModuleIds($form), true))->toBe($expected);
})->with([
    'no rules' => [[], false],
    'a real rule' => [[['field' => '{field:source}', 'value' => 'yes', 'condition' => '=']], true],
]);
