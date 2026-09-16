<?php

declare(strict_types=1);

use craft\helpers\Json;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\UpdateSubmissionContent;

it('preserves stored values when fields move into and out of a group', function (mixed $value, string $direction, bool $regional): void {
    $config = [];
    if ($regional) {
        if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
        $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
        $group = new \verbb\formie\models\FormGroup(['name' => 'Regional relocation', 'handle' => 'regionalMove' . bin2hex(random_bytes(5)),
            'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
        expect(\verbb\formie\Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
        $config = ['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId];
    }
    $form = formie()->form($config)->singleLineTextField('outside')->groupField('group', ['rows' => [['fields' => [
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'inside', 'label' => 'Inside'],
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'sibling', 'label' => 'Sibling'],
    ]]]])->create();
    $submission = new \verbb\formie\elements\Submission(['siteId' => $form->siteId, 'title' => 'Relocation submission']);
    $submission->setForm($form);
    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $group = $form->getFieldByHandle('group');
    $outside = $form->getFieldByHandle('outside')->uid;
    $inside = $group->getFieldByHandle('inside')->uid;
    $sibling = $group->getFieldByHandle('sibling')->uid;
    // Seed the persisted shape from before the layout change, then execute the actual queued migration.
    $content = $direction === 'into'
        ? [$inside => $value, $sibling => 'Keep']
        : [$group->uid => [$outside => $value, $sibling => 'Keep']];
    $expected = $direction === 'into'
        ? [$group->uid => [$inside => $value, $sibling => 'Keep']]
        : [$group->uid => [$sibling => 'Keep'], $outside => $value];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission->id])->execute();
    $read = fn() => Json::decode((new \craft\db\Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar());
    $canonicalize = function (array $values) use (&$canonicalize): array {
        foreach ($values as &$item) {
            if (is_array($item)) { $item = $canonicalize($item); }
        }
        ksort($values);
        return $values;
    };
    $job = new UpdateSubmissionContent(['formId' => $form->id]);
    $job->execute(Craft::$app->getQueue());
    expect($canonicalize($read()))->toBe($canonicalize($expected));
    $job->execute(Craft::$app->getQueue());
    expect($canonicalize($read()))->toBe($canonicalize($expected));
})->with([
    'zero' => [0], 'false' => [false], 'empty string' => [''], 'null' => [null], 'empty array' => [[]], 'text' => ['Retain'],
])->with(['into', 'out'])->with(['primary' => false, 'regional' => true]);

it('relocates values between groups without replacing newer answers', function (mixed $value, bool $enabled): void {
    $form = formie()->form(['enabled' => $enabled])->groupField('before', ['rows' => [['fields' => [
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'sibling', 'label' => 'Sibling'],
    ]]]])->groupField('after', ['rows' => [['fields' => [
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'moved', 'label' => 'Moved'],
    ]]]])->create();
    $submission = formie()->submission($form)->save();
    $before = $form->getFieldByHandle('before');
    $after = $form->getFieldByHandle('after');
    $moved = $after->getFieldByHandle('moved');
    $sibling = $before->getFieldByHandle('sibling');
    $content = [$before->uid => [$moved->uid => $value, $sibling->uid => 'Keep sibling']];
    \craft\helpers\Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission->id]);
    $read = function () use ($submission): array {
        $content = Json::decode((new \craft\db\Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar());
        ksort($content);
        return $content;
    };
    $expected = [$before->uid => [$sibling->uid => 'Keep sibling'], $after->uid => [$moved->uid => $value]];
    ksort($expected);
    $job = new UpdateSubmissionContent(['formId' => $form->id]);
    $job->execute(Craft::$app->getQueue());
    expect($read())->toBe($expected);
    $job->execute(Craft::$app->getQueue());
    expect($read())->toBe($expected);
    // A visitor may already have saved at the new location before the delayed job executes.
    $content[$after->uid] = [$moved->uid => null];
    \craft\helpers\Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission->id]);
    $job->execute(Craft::$app->getQueue());
    expect($read()[$after->uid][$moved->uid])->toBeNull();
    expect($read()[$before->uid][$sibling->uid])->toBe('Keep sibling');
})->with(['text' => ['Retain'], 'zero' => [0], 'false' => [false], 'empty' => [''], 'null' => [null], 'array' => [[]]])
    ->with(['enabled' => true, 'disabled' => false]);
