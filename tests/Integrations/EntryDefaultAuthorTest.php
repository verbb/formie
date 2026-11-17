<?php

declare(strict_types=1);

use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\helpers\StringHelper;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\models\IntegrationRunContext;

// The builder saves Default Entry Author as `[{id, siteId}]` and Formie 3 forms keep `["id"]`, so both shapes,
// plus a plain ID, must resolve to the selected user rather than an integer cast of the array.
dataset('stored default author shapes', [
    'builder reference' => [fn(User $user): array => [['id' => $user->id, 'siteId' => Craft::$app->getSites()->getPrimarySite()->id]]],
    'Formie 3 ID list' => [fn(User $user): array => [(string)$user->id]],
    'plain ID' => [fn(User $user): int => $user->id],
]);

it('assigns the selected default author to created entries', function(Closure $storedValue): void {
    $section = Craft::$app->getEntries()->getSectionByHandle('formieTestEntries');
    $entryType = $section ? Craft::$app->getEntries()->getEntryTypesBySectionId($section->id)[0] ?? null : null;
    $author = User::find()->status(null)->username('formie-seed-user')->one();

    if (!$section || !$entryType || !$author) {
        test()->markTestSkipped('The Formie test section or seed user is not available.');
    }

    // User 1 is what an integer cast of any non-empty array gives, so the selected user must be someone else.
    expect($author->id)->not->toBe(1);

    $title = 'Entry Default Author ' . uniqid();
    $integration = new Entry([
        'name' => 'Entry',
        'handle' => 'entry',
        'entryTypeSection' => $section->uid . ':' . $entryType->uid,
        'defaultAuthorId' => $storedValue($author),
        'attributeMapping' => ['title' => $title, 'slug' => StringHelper::slugify($title)],
    ]);

    $submission = new Submission();
    $submission->siteId = Craft::$app->getSites()->getPrimarySite()->id;

    expect($integration->execute(IntegrationRunContext::forIntegration($integration, $submission))->status)->toBe(IntegrationStatus::Succeeded);

    $entry = EntryElement::find()->status(null)->sectionId($section->id)->slug(StringHelper::slugify($title))->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getAuthorIds())->toBe([$author->id]);

    Craft::$app->getElements()->deleteElement($entry, true);
})->with('stored default author shapes');
