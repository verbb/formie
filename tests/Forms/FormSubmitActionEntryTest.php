<?php

declare(strict_types=1);

use craft\elements\Entry;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\SchemaHelper;

it('reads the first element-select {id, siteId} from a list payload', function (): void {
    expect(SchemaHelper::firstElementSelectIds([
        ['id' => 1049, 'siteId' => 4],
    ]))->toBe([1049, 4])
        ->and(SchemaHelper::firstElementSelectIds([]))->toBe([null, null])
        ->and(SchemaHelper::firstElementSelectIds(null))->toBe([null, null]);
});

it('persists a Behaviour-tab redirect entry from the builder list payload', function (): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Submit Action Entry Persist'])
        ->singleLineTextField('fullName')
        ->create();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $entry): void {
        $request->setBodyParams([
            'id' => $form->id,
            'title' => $form->title,
            'handle' => $form->handle,
            'submitActionEntry' => [[
                'id' => $entry->id,
                'siteId' => $entry->siteId,
            ]],
            'settings' => [
                'submitAction' => 'entry',
            ],
        ]);

        $populated = Formie::$plugin->getForms()->buildFormFromPost();

        expect($populated->submitActionEntryId)->toBe($entry->id)
            ->and($populated->submitActionEntrySiteId)->toBe($entry->siteId);

        expect(Craft::$app->getElements()->saveElement($populated))->toBeTrue();
    }, [
        'method' => 'POST',
    ]);

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->submitActionEntryId)->toBe($entry->id)
        ->and($reloaded->submitActionEntrySiteId)->toBe($entry->siteId);
});

it('clears a saved redirect entry when the builder posts an empty list', function (): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Submit Action Entry Clear'])
        ->singleLineTextField('fullName')
        ->create();

    $form->submitActionEntryId = $entry->id;
    $form->submitActionEntrySiteId = $entry->siteId;
    $form->settings->setAttributes(['submitAction' => 'entry'], false);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form): void {
        $request->setBodyParams([
            'id' => $form->id,
            'title' => $form->title,
            'handle' => $form->handle,
            'submitActionEntry' => [],
            'settings' => [
                'submitAction' => 'message',
            ],
        ]);

        $populated = Formie::$plugin->getForms()->buildFormFromPost();

        expect($populated->submitActionEntryId)->toBeNull()
            ->and($populated->submitActionEntrySiteId)->toBeNull();

        expect(Craft::$app->getElements()->saveElement($populated))->toBeTrue();
    }, [
        'method' => 'POST',
    ]);

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded?->submitActionEntryId)->toBeNull()
        ->and($reloaded?->submitActionEntrySiteId)->toBeNull();
});
