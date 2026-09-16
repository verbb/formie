<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\Asset;
use Tests\Support\UploadTestHelper;
use verbb\formie\elements\{Form, Submission};
use verbb\formie\helpers\Table;

it('restores form fields and all cascade-deleted submission states while retaining individually trashed answers', function (bool $regional): void {
    $config = [];
    if ($regional) {
        if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
        $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
        $group = new \verbb\formie\models\FormGroup(['name' => 'Regional restore', 'handle' => 'regionalRestore' . bin2hex(random_bytes(5)),
            'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
        expect(\verbb\formie\Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
        $config = ['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId];
    }
    $form = formie()->form($config)->singleLineTextField('message')->create();
    $layoutId = $form->layoutId;
    $uid = $form->getFieldByHandle('message')->uid;
    $ids = [];
    foreach (['complete', 'incomplete', 'spam', 'previouslyTrashed'] as $state) {
        $submission = new Submission(['siteId' => $form->siteId, 'title' => 'Restore ' . $state]);
        $submission->setForm($form);
        $submission->setFieldValue('message', $state);
        $submission->isIncomplete = $state === 'incomplete';
        $submission->isSpam = $state === 'spam';
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        $ids[$state] = $submission->id;
        if ($state === 'previouslyTrashed') { expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue(); }
    }
    expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();
    foreach ($ids as $id) {
        expect((new Query())->select('dateDeleted')->from('{{%elements}}')->where(['id' => $id])->scalar())->not->toBeNull();
    }
    $trashed = Form::find()->id($form->id)->siteId($form->siteId)->status(null)->trashed(true)->one();
    expect(Craft::$app->getElements()->restoreElement($trashed))->toBeTrue();
    $restored = Form::find()->id($form->id)->siteId($form->siteId)->one();
    expect($restored->layoutId)->toBe($layoutId);
    expect($restored->getFieldByHandle('message')->uid)->toBe($uid);
    foreach (['complete', 'incomplete', 'spam'] as $state) {
        $submission = Submission::find()->id($ids[$state])->site('*')->status(null)->one();
        expect($submission)->not->toBeNull();
        expect($submission->getFieldValue('message'))->toBe($state);
        expect($submission->deletedWithOwner)->toBeNull();
    }
    expect(Submission::find()->id($ids['previouslyTrashed'])->site('*')->status(null)->one())->toBeNull();
    expect(Submission::find()->id($ids['previouslyTrashed'])->site('*')->status(null)->trashed(true)->one())->not->toBeNull();
})->with([false, true]);

it('permanently removes child elements and configured uploads when deleting a form', function (bool $trashFirst): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['fileUploadsAction' => 'delete'])->singleLineTextField('message')->fileUploadField('documents', ['restrictFiles' => false, 'allowedKinds' => ['text']])->create();
    $asset = UploadTestHelper::seedAsset('form-cascade.txt', 'owned cascade fixture', $volume);
    $layoutId = $form->layoutId;
    $ids = [];
    foreach (['complete', 'incomplete', 'spam', 'previouslyTrashed'] as $state) {
        $submission = formie()->submission($form)->with(['message' => $state, 'documents' => $state === 'complete' ? [$asset->id] : []])->save();
        $submission->isIncomplete = $state === 'incomplete';
        $submission->isSpam = $state === 'spam';
        expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
        $ids[] = $submission->id;
        if ($state === 'previouslyTrashed') { expect(Craft::$app->getElements()->deleteElement($submission))->toBeTrue(); }
    }
    if ($trashFirst) {
        expect(Craft::$app->getElements()->deleteElement($form))->toBeTrue();
        expect(Asset::find()->id($asset->id)->one())->not->toBeNull();
        $form = Form::find()->id($form->id)->status(null)->trashed(true)->one();
    }
    expect(Craft::$app->getElements()->deleteElement($form, true))->toBeTrue();
    expect((int)(new Query())->from('{{%elements}}')->where(['id' => $ids])->count())->toBe(0);
    expect((int)(new Query())->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $ids])->count())->toBe(0);
    expect((int)(new Query())->from(Table::FORMIE_FIELD_LAYOUTS)->where(['id' => $layoutId])->count())->toBe(0);
    expect(Asset::find()->id($asset->id)->one())->toBeNull();
})->with([false, true]);
