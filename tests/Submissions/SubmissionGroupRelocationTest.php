<?php

declare(strict_types=1);

use craft\helpers\Json;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\UpdateSubmissionContent;

it('preserves stored values when fields move into and out of a group', function (mixed $value, string $direction): void {
    $form = formie()->form()->singleLineTextField('outside')->groupField('group', ['rows' => [['fields' => [
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'inside', 'label' => 'Inside'],
        ['type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'sibling', 'label' => 'Sibling'],
    ]]]])->create();
    $submission = formie()->submission($form)->save();
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
])->with(['into', 'out']);
