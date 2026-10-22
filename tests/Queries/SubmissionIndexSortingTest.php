<?php

declare(strict_types=1);

use craft\db\Table;
use craft\elements\User;
use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;

it('keeps the selected submission index sort ahead of previous column sorts', function (string $attribute, string $direction, array $history, bool $allForms): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $otherForm = $allForms ? formie()->form()->singleLineTextField('message')->create() : $form;
    $older = formie()->submission($form)->with(['message' => 'Older'])->save();
    $newer = formie()->submission($otherForm)->with(['message' => 'Newer'])->save();

    foreach ([$older, $newer] as $submission) {
        $isOlder = $submission->id === $older->id;
        Craft::$app->getDb()->createCommand()->update(Table::ELEMENTS, [
            'dateCreated' => $isOlder ? '2024-01-01 00:00:00' : '2025-12-01 00:00:00',
            // Updated order deliberately differs from creation order.
            'dateUpdated' => $isOlder ? '2025-12-02 00:00:00' : '2025-12-01 00:00:00',
        ], ['id' => $submission->id])->execute();
    }

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $older, $newer, $attribute, $direction, $history, $allForms): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        $query = Submission::find()->id([$older->id, $newer->id])->status(null);

        // Exercise Craft's real index ordering, including its persisted sort history.
        $html = Submission::indexHtml($query, null, [
            'mode' => 'table',
            'order' => $attribute,
            'sort' => $direction,
            'orderHistory' => $history,
            'tableColumns' => ['dateCreated', 'dateUpdated'],
        ], $allForms ? '*' : "form:{$form->id}", 'index', false, false, false);

        $oldestFirst = ($direction === 'asc') !== ($attribute === 'dateUpdated');
        $expected = $oldestFirst ? [$older->id, $newer->id] : [$newer->id, $older->id];
        expect($query->ids())->toBe($expected);
        preg_match_all('/<tr\b[^>]*data-id="(\d+)"/', $html, $matches);
        expect(array_map('intval', $matches[1]))->toBe($expected);
    });
})->with([
    'title ascending after creation descending' => ['title', 'asc', [['dateCreated', 'desc']]],
    'title descending after creation ascending' => ['title', 'desc', [['dateCreated', 'asc']]],
    'creation ascending after title descending' => ['dateCreated', 'asc', [['title', 'desc']]],
    'creation descending after title ascending' => ['dateCreated', 'desc', [['title', 'asc']]],
    'title after switching through updated' => ['title', 'asc', [['dateUpdated', 'desc'], ['dateCreated', 'desc']]],
    'creation after switching through updated' => ['dateCreated', 'desc', [['dateUpdated', 'asc'], ['title', 'asc']]],
    'updated ascending remains independent' => ['dateUpdated', 'asc', [['title', 'asc'], ['dateCreated', 'desc']]],
    'updated descending remains independent' => ['dateUpdated', 'desc', [['dateCreated', 'desc'], ['title', 'asc']]],
])->with(['all forms' => true, 'individual form' => false]);
