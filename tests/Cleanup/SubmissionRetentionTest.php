<?php

declare(strict_types=1);

use craft\elements\db\ElementQuery;
use craft\events\PopulateElementsEvent;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\elements\db\SubmissionQuery;
use verbb\formie\events\PruneSubmissionEvent;
use verbb\formie\services\Cleanup;
use verbb\formie\services\Submissions;
use yii\base\Event;

beforeEach(function (): void {
    // Retention scans every form. Roll back its database changes so unrelated
    // fixtures are not deleted when the suite changes execution order.
    $this->retentionTransaction = Craft::$app->getDb()->beginTransaction();
});
afterEach(function (): void {
    $this->retentionTransaction->rollBack();
    Formie::$plugin->getForms()->invalidateFormCaches();
});

function retentionSubmission($form, string $age, bool $incomplete = false, bool $spam = false): Submission
{
    $submission = formie()->submission($form)->with(['name' => 'Retain the correct records'])->save();
    $submission->isIncomplete = $incomplete;
    $submission->isSpam = $spam;
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $date = gmdate('Y-m-d H:i:s', strtotime($age));
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['dateCreated' => $date, 'dateUpdated' => $date], ['id' => $submission->id], [], false)->execute();
    return $submission;
}

function survivingRetentionIds(array $submissions): array
{
    return array_map('intval', Submission::find()->id(array_map(fn($s) => $s->id, $submissions))
        ->status(null)->isIncomplete(null)->isSpam(null)->orderBy('elements.id ASC')->ids());
}

it('deletes only expired submissions in forms with retention enabled and is idempotent', function (): void {
    $expiring = formie()->form(['dataRetention' => 'days', 'dataRetentionValue' => '30'])->singleLineTextField('name')->create();
    $forever = formie()->form(['dataRetention' => 'forever'])->singleLineTextField('name')->create();
    $rows = [
        retentionSubmission($expiring, '-31 days'),
        retentionSubmission($expiring, '-31 days', true),
        retentionSubmission($expiring, '-31 days', false, true),
        retentionSubmission($expiring, '-29 days'),
        retentionSubmission($forever, '-90 days'),
        retentionSubmission($expiring, '-30 days -1 hour'),
        retentionSubmission($expiring, '-30 days +1 hour'),
    ];
    $expected = [$rows[3]->id, $rows[4]->id, $rows[6]->id];
    Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_DATA_RETENTION_SUBMISSIONS);
    expect(survivingRetentionIds($rows))->toBe($expected);
    Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_DATA_RETENTION_SUBMISSIONS);
    expect(survivingRetentionIds($rows))->toBe($expected);
});

it('prunes stale incomplete submissions while preserving complete and recent drafts', function (): void {
    Formie::$plugin->getSettings()->maxIncompleteSubmissionAge = 30;
    Formie::$plugin->getSettings()->saveSpam = false;
    $form = formie()->form()->singleLineTextField('name')->create();
    $rows = [retentionSubmission($form, '-31 days', true), retentionSubmission($form, '-29 days', true), retentionSubmission($form, '-90 days')];
    Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_INCOMPLETE_SUBMISSIONS);
    expect(survivingRetentionIds($rows))->toBe([$rows[1]->id, $rows[2]->id]);
});

it('enforces the spam limit when incomplete submissions are kept indefinitely', function (): void {
    Formie::$plugin->getSettings()->maxIncompleteSubmissionAge = 0;
    Formie::$plugin->getSettings()->saveSpam = true;
    Formie::$plugin->getSettings()->spamLimit = 1;
    $form = formie()->form()->singleLineTextField('name')->create();
    // The newest spam row is dated ahead so other fixtures' spam can't push it past the limit.
    $rows = [
        retentionSubmission($form, '+1 day', false, true),
        retentionSubmission($form, '-1 day', false, true),
        retentionSubmission($form, '-90 days', true),
    ];
    Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_INCOMPLETE_SUBMISSIONS);
    expect(survivingRetentionIds($rows))->toBe([$rows[0]->id, $rows[2]->id]);
});

it('prunes a large retention backlog in batches, keeping the same rows and events', function (): void {
    $form = formie()->form(['dataRetention' => 'days', 'dataRetentionValue' => '30'])->singleLineTextField('name')->create();
    $ids = [];

    for ($i = 0; $i < 101; $i++) {
        $ids[] = (int)formie()->submission($form)->with(['name' => "Backlog {$i}"])->save()->id;
    }

    $date = gmdate('Y-m-d H:i:s', strtotime('-31 days'));
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['dateCreated' => $date, 'dateUpdated' => $date], ['id' => $ids], [], false)->execute();
    $recent = retentionSubmission($form, '-29 days');
    $expected = array_map('intval', Submission::find()->status(null)->isIncomplete(null)->isSpam(null)->formId($form->id)
        ->dateCreated('< ' . gmdate('Y-m-d H:i:s', strtotime('-30 days')))->ids());
    expect($expected)->toHaveCount(101);

    $largestLoad = 0;
    $pruned = [];
    $loadHandler = static function(PopulateElementsEvent $event) use (&$largestLoad): void {
        $largestLoad = max($largestLoad, count($event->elements));
    };
    $pruneHandler = static function(PruneSubmissionEvent $event) use (&$pruned): void {
        $pruned[] = (int)$event->submission->id;
    };
    Event::on(SubmissionQuery::class, ElementQuery::EVENT_AFTER_POPULATE_ELEMENTS, $loadHandler);
    Formie::$plugin->getSubmissions()->on(Submissions::EVENT_AFTER_PRUNE_SUBMISSION, $pruneHandler);

    try {
        Formie::$plugin->getCleanup()->runTask(Cleanup::TASK_DATA_RETENTION_SUBMISSIONS);
    } finally {
        Event::off(SubmissionQuery::class, ElementQuery::EVENT_AFTER_POPULATE_ELEMENTS, $loadHandler);
        Formie::$plugin->getSubmissions()->off(Submissions::EVENT_AFTER_PRUNE_SUBMISSION, $pruneHandler);
    }

    // Every row shares one date, so compare the pruned set rather than relying on how ties are ordered.
    sort($pruned);
    sort($expected);

    expect($pruned)->toBe($expected)
        ->and(array_map('intval', Submission::find()->id([...$ids, $recent->id])->status(null)->isIncomplete(null)->isSpam(null)->ids()))->toBe([(int)$recent->id])
        // Each submission carries its own copy of the form, so loading them all at once grows memory with the backlog.
        ->and($largestLoad)->toBeLessThanOrEqual(100);
});
