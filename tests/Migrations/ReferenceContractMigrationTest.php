<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m260928_010000_reference_contract;
use verbb\formie\models\Notification;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

it('migrates beta nested tokens across persisted reference-bearing settings idempotently', function (): void {
    $rows = [['fields' => [[
        'type' => SingleLineText::class,
        'handle' => 'innerText',
        'label' => 'Inner Text',
    ]]]];
    $form = formie()->form()
        ->groupField('contact', ['rows' => $rows])
        ->nameField('person', ['useMultipleFields' => true])
        ->hiddenField('tracking')
        ->create();
    $group = $form->getFieldByHandle('contact');
    $child = $group->getFieldByHandle('innerText');
    $name = $form->getFieldByHandle('person');
    $hidden = $form->getFieldByHandle('tracking');
    $legacyToken = References::field((string)$group->reference, 'innerText');
    $canonicalToken = References::field((string)$child->reference);
    $canonicalSelectorToken = References::field((string)$name->reference, 'firstName');

    $formSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $formSettings['integrations'] = ['fixture' => ['fieldMapping' => [
        'GROUP_CHILD' => $legacyToken,
        'NAME_PART' => $canonicalSelectorToken,
    ]]];
    $formSettings['integrationPolicies'] = ['rerun' => ['fixture' => ['policy' => 'onEdit']]];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($formSettings)], ['id' => $form->id])->execute();

    $fieldSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORM_FIELDS)->where(['id' => $hidden->id])->scalar());
    $fieldSettings['defaultValue'] = 'Tracked: ' . $legacyToken;
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode($fieldSettings)], ['id' => $hidden->id])->execute();

    $definitionSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $hidden->definitionId])->scalar());
    $definitionSettings['defaultValue'] = 'Defined: ' . $legacyToken;
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($definitionSettings)], ['id' => $hidden->definitionId])->execute();

    $notification = new Notification([
        'formId' => $form->id,
        'name' => 'Reference migration',
        'handle' => 'referenceMigration',
        'subject' => 'Hello ' . $legacyToken,
        'to' => 'migration@example.test',
        'enabled' => false,
    ]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();

    $stencilData = new StencilData();
    $stencilData->populateFormData($form);
    $serializedStencilData = $stencilData->getSerializedData();
    $serializedStencilData['notifications'] = [[
        'name' => 'Stencil reference migration',
        'handle' => 'stencilReferenceMigration',
        'subject' => 'Stencil: ' . $legacyToken,
        'to' => 'migration@example.test',
        'enabled' => false,
    ]];
    $stencil = new Stencil([
        'name' => 'Reference migration',
        'handle' => 'referenceMigration',
        'scope' => StencilsService::SCOPE_SITE,
        'data' => new StencilData($serializedStencilData),
    ]);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

    foreach ([1, 2] as $run) {
        expect((new m260928_010000_reference_contract())->safeUp())->toBeTrue();

        $storedFormSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
        $storedFieldSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORM_FIELDS)->where(['id' => $hidden->id])->scalar());
        $storedDefinitionSettings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $hidden->definitionId])->scalar());
        $storedSubject = (new Query())->select('subject')->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $notification->id])->scalar();
        $storedStencilData = Json::decode((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->scalar());

        expect($storedFormSettings['integrations']['fixture']['fieldMapping']['GROUP_CHILD'])->toBe($canonicalToken)
            ->and($storedFormSettings['integrations']['fixture']['fieldMapping']['NAME_PART'])->toBe($canonicalSelectorToken)
            ->and($storedFormSettings['integrations']['fixture']['trigger'])->toBe(['policy' => 'onEdit'])
            ->and($storedFormSettings)->not->toHaveKey('integrationPolicies')
            ->and($storedFieldSettings['defaultValue'])->toBe('Tracked: ' . $canonicalToken)
            ->and($storedDefinitionSettings['defaultValue'])->toBe('Defined: ' . $canonicalToken)
            ->and($storedSubject)->toBe('Hello ' . $canonicalToken)
            ->and($storedStencilData['notifications'][0]['subject'])->toBe('Stencil: ' . $canonicalToken);
    }
});
