<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\migrations\m260916_000000_sync_form_site_availability;
use verbb\formie\models\FormGroup;

it('backfills historical form availability without changing content or explicit site restrictions', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $primary = (int)Craft::$app->getSites()->getPrimarySite()->id;
    $regional = (int)$sites[1];
    $form = formie()->form(['title' => 'Historical shared form'])->singleLineTextField('message')->create();
    $submission = formie()->submission($form)->with(['message' => 'Historical content'])->save();
    $reference = $form->getFieldByHandle('message')->reference;
    Formie::$plugin->getFormSiteOverrides()->saveOverrides($form->id, $regional, ['title' => 'Titre local']);
    Db::delete('{{%elements_sites}}', ['and', ['elementId' => $form->id], ['not', ['siteId' => $primary]]]);
    expect(Form::find()->id($form->id)->siteId($regional)->one())->toBeNull();

    $group = new FormGroup(['name' => 'Preserved restriction', 'handle' => 'preservedRestriction' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$primary]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $restricted = formie()->form(['groupId' => $group->id])->singleLineTextField('message')->create();
    $snapshot = fn() => (new Query())->select(['siteId', 'title', 'enabled'])->from('{{%elements_sites}}')
        ->where(['elementId' => $form->id])->orderBy('siteId')->all();
    $migration = new m260916_000000_sync_form_site_availability();
    expect($migration->safeUp())->toBeTrue();
    $first = $snapshot();
    expect(array_map('intval', array_column($first, 'siteId')))->toBe($sites);
    expect(Form::find()->id($form->id)->siteId($regional)->one()?->title)->toBe('Titre local');
    expect(Form::find()->id($form->id)->siteId($primary)->one()?->getFieldByHandle('message')->reference)->toBe($reference);
    expect(Submission::find()->id($submission->id)->one()?->getFieldValue('message'))->toBe('Historical content');
    expect(Form::find()->id($restricted->id)->siteId($regional)->one())->toBeNull();
    expect($migration->safeUp())->toBeTrue();
    expect($snapshot())->toBe($first);
});
