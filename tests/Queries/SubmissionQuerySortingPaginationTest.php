<?php

declare(strict_types=1);

use craft\db\Table as CraftTable;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

it('supports ordering and pagination contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Query Sorting'])
        ->singleLineTextField('fullName')
        ->create();

    $a = formie()->submission($form)->with(['fullName' => 'A'])->save();
    $b = formie()->submission($form)->with(['fullName' => 'B'])->save();
    $c = formie()->submission($form)->with(['fullName' => 'C'])->save();

    $asc = Submission::find()->formId($form->id)->orderBy(['elements.id' => SORT_ASC])->all();
    $desc = Submission::find()->formId($form->id)->orderBy(['elements.id' => SORT_DESC])->all();
    $paged = Submission::find()->formId($form->id)->orderBy(['elements.id' => SORT_ASC])->limit(1)->offset(1)->all();

    $ascIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $asc);
    $descIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $desc);
    $pagedIds = array_map(static fn(Submission $submission): int => (int)$submission->id, $paged);

    expect($ascIds)->toBe([$a->id, $b->id, $c->id])
        ->and($descIds)->toBe([$c->id, $b->id, $a->id])
        ->and($pagedIds)->toBe([$b->id]);
});

it('orders submission title sorts chronologically via dateCreated', function (): void {
    $form = formie()
        ->form(['title' => 'Query Title Sort'])
        ->singleLineTextField('fullName')
        ->create();

    $older = formie()->submission($form)->with(['fullName' => 'Older'])->save();
    $newer = formie()->submission($form)->with(['fullName' => 'Newer'])->save();

    // Titles that would sort in the opposite order alphabetically (F before M).
    $older->title = 'Fri, 01 Jan 2024 00:00:00';
    $newer->title = 'Mon, 01 Dec 2025 00:00:00';
    expect(\Craft::$app->elements->saveElement($older))->toBeTrue();
    expect(\Craft::$app->elements->saveElement($newer))->toBeTrue();

    $db = \Craft::$app->getDb();

    foreach ([$older, $newer] as $submission) {
        $createdAt = $submission->id === $older->id ? '2024-01-01 00:00:00' : '2025-12-01 00:00:00';

        $db->createCommand()->update(
            Table::FORMIE_SUBMISSIONS,
            ['dateCreated' => $createdAt, 'dateUpdated' => $createdAt],
            ['id' => $submission->id],
        )->execute();

        $db->createCommand()->update(
            CraftTable::ELEMENTS,
            ['dateCreated' => $createdAt, 'dateUpdated' => $createdAt],
            ['id' => $submission->id],
        )->execute();
    }

    $asc = Submission::find()
        ->formId($form->id)
        ->orderBy(['elements.dateCreated' => SORT_ASC])
        ->ids();

    $desc = Submission::find()
        ->formId($form->id)
        ->orderBy(['elements.dateCreated' => SORT_DESC])
        ->ids();

    expect($asc)->toBe([$older->id, $newer->id])
        ->and($desc)->toBe([$newer->id, $older->id]);
});

it('orders date fields chronologically for submission index sorting', function (): void {
    $form = formie()
        ->form(['title' => 'Query Date Sort'])
        ->dateField('signedAt', [
            'label' => 'Date Signed',
            'displayType' => 'calendar',
        ])
        ->create();

    $older = formie()->submission($form)->with([
        'signedAt' => '2024-09-11 20:48:00',
    ])->save();
    $newer = formie()->submission($form)->with([
        'signedAt' => '2025-12-09 10:00:00',
    ])->save();

    $field = $form->getFields()[0];
    $sortSql = $field->getSortOption()['orderBy'][0];

    $asc = Submission::find()
        ->formId($form->id)
        ->orderBy([$sortSql => SORT_ASC, 'elements.id' => SORT_ASC])
        ->ids();

    $desc = Submission::find()
        ->formId($form->id)
        ->orderBy([$sortSql => SORT_DESC, 'elements.id' => SORT_DESC])
        ->ids();

    expect($asc)->toBe([$older->id, $newer->id])
        ->and($desc)->toBe([$newer->id, $older->id]);
});
