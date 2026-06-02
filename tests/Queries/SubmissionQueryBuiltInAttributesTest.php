<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\Status;
use verbb\formie\elements\Submission;

it('queries submissions by built-in attributes with exact result sets', function (): void {
    $form = formie()
        ->form(['title' => 'Query Built-In Attributes'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionA = formie()->submission($form)->with(['fullName' => 'Alpha'])->save();
    $submissionB = formie()->submission($form)->with(['fullName' => 'Bravo'])->save();
    $submissionC = formie()->submission($form)->with(['fullName' => 'Charlie'])->save();

    $seedUser = User::find()->status(null)->username('formie-seed-user')->one();
    expect($seedUser)->not->toBeNull();

    $statuses = array_values(array_filter(
        Formie::$plugin->getStatuses()->getAllStatuses(),
        static fn($status): bool => $status instanceof Status
    ));
    expect($statuses)->not->toBeEmpty();

    $primaryStatus = $statuses[0];
    $secondaryStatus = $statuses[1] ?? $statuses[0];

    $submissionA->userId = $seedUser->id;
    $submissionA->isSpam = true;
    $submissionA->isIncomplete = true;
    $submissionA->statusId = $primaryStatus->id;

    $submissionB->userId = null;
    $submissionB->isSpam = false;
    $submissionB->isIncomplete = true;
    $submissionB->statusId = $secondaryStatus->id;

    $submissionC->userId = null;
    $submissionC->isSpam = false;
    $submissionC->isIncomplete = false;
    $submissionC->statusId = $secondaryStatus->id;

    expect(\Craft::$app->elements->saveElement($submissionA))->toBeTrue();
    expect(\Craft::$app->elements->saveElement($submissionB))->toBeTrue();
    expect(\Craft::$app->elements->saveElement($submissionC))->toBeTrue();

    // Stabilize date-window assertions with deterministic timestamps.
    \Craft::$app->getDb()->createCommand()->update(
        Table::FORMIE_SUBMISSIONS,
        ['dateCreated' => '2026-01-01 00:00:00', 'dateUpdated' => '2026-01-01 00:00:00'],
        ['id' => $submissionA->id]
    )->execute();
    \Craft::$app->getDb()->createCommand()->update(
        Table::FORMIE_SUBMISSIONS,
        ['dateCreated' => '2026-02-01 00:00:00', 'dateUpdated' => '2026-02-01 00:00:00'],
        ['id' => $submissionB->id]
    )->execute();
    \Craft::$app->getDb()->createCommand()->update(
        Table::FORMIE_SUBMISSIONS,
        ['dateCreated' => '2026-03-01 00:00:00', 'dateUpdated' => '2026-03-01 00:00:00'],
        ['id' => $submissionC->id]
    )->execute();

    $assertIds = static function(array $submissions, array $expectedIds): void {
        $actualIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $submissions);
        sort($actualIds, SORT_NUMERIC);
        sort($expectedIds, SORT_NUMERIC);

        expect($actualIds)->toBe($expectedIds);
    };

    $byUserId = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->userId($seedUser->id)
        ->all();
    $assertIds($byUserId, [$submissionA->id]);

    $byUserHandle = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->user('formie-seed-user')
        ->all();
    $assertIds($byUserHandle, [$submissionA->id]);

    $bySpamTrue = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->isSpam(true)
        ->all();
    $assertIds($bySpamTrue, [$submissionA->id]);

    $byIncompleteTrue = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->isIncomplete(true)
        ->all();
    $assertIds($byIncompleteTrue, [$submissionA->id, $submissionB->id]);

    $byBefore = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->before('2026-02-15')
        ->all();
    $assertIds($byBefore, [$submissionA->id, $submissionB->id]);

    $byAfter = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->after('2026-02-01')
        ->all();
    $assertIds($byAfter, [$submissionB->id, $submissionC->id]);

    $expectedSecondaryStatusIds = $secondaryStatus->id === $primaryStatus->id
        ? [$submissionA->id, $submissionB->id, $submissionC->id]
        : [$submissionB->id, $submissionC->id];

    $byStatusId = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->statusId($secondaryStatus->id)
        ->all();
    $assertIds($byStatusId, $expectedSecondaryStatusIds);

    $byStatusHandle = Submission::find()
        ->formId($form->id)
        ->anyStatus()
        ->status($secondaryStatus->handle)
        ->all();
    $assertIds($byStatusHandle, $expectedSecondaryStatusIds);
});
