<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\{Db, Json};
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\jobs\UpdateSubmissionContent;

it('preserves ordinary edits saved after a relocation job selects its submissions', function (): void {
    $form = formie()->form()->singleLineTextField('unrelated')->groupField('details', ['rows' => [['fields' => [[
        'type' => SingleLineText::class, 'handle' => 'message', 'label' => 'Message',
    ]]]]])->create();
    $submission = formie()->submission($form)->with(['unrelated' => 'Initial unrelated'])->save();
    $group = $form->getFieldByHandle('details');
    $child = $group->getFieldByHandle('message');
    $unrelated = $form->getFieldByHandle('unrelated');
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => [$child->uid => 'Old answer', $unrelated->uid => 'Initial unrelated']], ['id' => $submission->id]);
    $queue = new class extends \craft\queue\Queue {
        public ?Closure $callback = null;
        public function setProgress(int $progress, ?string $label = null): void {
            if ($this->callback) { $callback = $this->callback; $this->callback = null; $callback(); }
        }
    };
    $queue->callback = function () use ($submission): void {
        $fresh = Submission::find()->id($submission->id)->status(null)->one();
        $fresh->setFieldValue('unrelated', 'Saved during migration');
        $fresh->setFieldValue('details', ['message' => 'New answer']);
        expect(Craft::$app->getElements()->saveElement($fresh))->toBeTrue();
    };
    $job = new UpdateSubmissionContent(['formId' => $form->id]);
    $job->execute($queue);
    $read = fn() => Json::decode((new Query())->select('content')->from(Table::FORMIE_SUBMISSIONS)->where(['id' => $submission->id])->scalar());
    expect($read()[$unrelated->uid])->toBe('Saved during migration');
    expect($read()[$group->uid][$child->uid])->toBe('New answer');
    expect($read())->not->toHaveKey($child->uid);
    $job->execute($queue);
    expect($read()[$unrelated->uid])->toBe('Saved during migration');
    expect($read()[$group->uid][$child->uid])->toBe('New answer');
});
