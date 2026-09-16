<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\services\Cleanup;

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
