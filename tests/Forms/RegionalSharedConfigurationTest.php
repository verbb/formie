<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\models\{FormGroup, FormStatus, FormTemplate};

function regionalSharedConfiguration(array $config = []): \verbb\formie\elements\Form
{
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Shared configuration region', 'handle' => 'sharedRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    return formie()->form(array_merge(['handle' => 'regionalShared' . bin2hex(random_bytes(6)), 'groupId' => $group->id,
        'siteId' => $siteId, 'sourceSiteId' => $siteId], $config))->singleLineTextField('message')->create();
}

it('rejects a shared handle already used by a regional form', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $regional = regionalSharedConfiguration();
    $other = formie()->form()->singleLineTextField('message')->create();
    $original = $other->handle;
    $other->handle = $regional->handle;
    expect(Craft::$app->getElements()->saveElement($other))->toBeFalse();
    expect($other->hasErrors('handle'))->toBeTrue();
    expect((new Query())->select('handle')->from('{{%formie_forms}}')->where(['id' => $other->id])->scalar())->toBe($original);
});

it('keeps statuses and templates in use by regional forms protected from deletion', function (string $kind, bool $disabled): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    if ($kind === 'status') {
        $item = new FormStatus(['name' => 'Regional status', 'handle' => 'regionalStatus' . bin2hex(random_bytes(6)), 'color' => 'blue']);
        expect(Formie::$plugin->getFormStatuses()->saveStatus($item))->toBeTrue();
        $key = 'formStatusId';
    } else {
        $item = new FormTemplate(['name' => 'Regional template', 'handle' => 'regionalTemplate' . bin2hex(random_bytes(6))]);
        expect(Formie::$plugin->getFormTemplates()->saveTemplate($item))->toBeTrue();
        $key = 'templateId';
    }
    expect($item->canDelete())->toBeTrue();
    $form = regionalSharedConfiguration([$key => $item->id, 'enabled' => !$disabled]);
    expect((int)(new Query())->select($key)->from('{{%formie_forms}}')->where(['id' => $form->id])->scalar())->toBe((int)$item->id);
    expect($item->canDelete())->toBeFalse();
    expect(Craft::$app->getElements()->deleteElement($form, true))->toBeTrue();
    expect($item->canDelete())->toBeTrue();
})->with(['status', 'template'])->with(['enabled' => false, 'disabled' => true]);


it('avoids regional handle collisions when restoring a form', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $form = formie()->form(['handle' => 'restoreShared' . bin2hex(random_bytes(6))])->singleLineTextField('message')->create();
    $handle = $form->handle;
    expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();
    $regional = regionalSharedConfiguration(['handle' => $handle]);
    expect(Craft::$app->getElements()->restoreElement($form))->toBeTrue();
    expect((new Query())->select('handle')->from('{{%formie_forms}}')->where(['id' => $form->id])->scalar())->toBe($handle . '1');
    expect((new Query())->select('handle')->from('{{%formie_forms}}')->where(['id' => $regional->id])->scalar())->toBe($handle);
});
