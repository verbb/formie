<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;

it('keeps named form status filters inside the configured site availability', function (string $status): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $group = new FormGroup(['name' => 'Status availability', 'handle' => 'statusAvailability' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $statusId = Formie::$plugin->getFormStatuses()->getStatusByHandle($status)->id;
    $form = formie()->form(['groupId' => $group->id, 'formStatusId' => $statusId])->singleLineTextField('message')->create();
    $settings = $group->getSettingsModel();
    $settings->sitePolicy['enabledSiteIds'] = [$sites[1]];
    $group->setSettingsModel($settings);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    expect((bool)(new Query())->select('enabled')->from('{{%elements_sites}}')->where(['elementId' => $form->id, 'siteId' => $sites[0]])->scalar())->toBeFalse();
    expect(Form::find()->id($form->id)->siteId($sites[1])->status($status)->one()?->id)->toBe($form->id);
    // Explicitly disabling status filtering remains available to maintenance callers.
    expect(Form::find()->id($form->id)->siteId($sites[0])->status(null)->one()?->id)->toBe($form->id);
    expect(Form::find()->id($form->id)->siteId($sites[0])->status($status)->one())->toBeNull();
})->with(['active', 'draft', 'archived']);
