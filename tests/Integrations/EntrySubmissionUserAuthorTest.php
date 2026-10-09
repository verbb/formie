<?php

declare(strict_types=1);

use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\helpers\StringHelper;
use verbb\formie\integrations\elements\Entry;

function entryAuthorIntegrationConfig(string $entryTitle, int $fallbackUserId): Entry
{
    $section = Craft::$app->getEntries()->getSectionByHandle('formieTestEntries');
    $entryType = $section ? Craft::$app->getEntries()->getEntryTypesBySectionId($section->id)[0] ?? null : null;

    if (!$section || !$entryType) {
        test()->markTestSkipped('Formie test section is not available.');
    }

    return new Entry([
        'name' => 'Entry',
        'handle' => 'entry',
        'entryTypeSection' => $section->uid . ':' . $entryType->uid,
        'defaultAuthorId' => $fallbackUserId,
        'useSubmissionUserAsAuthor' => true,
        'attributeMapping' => [
            'title' => $entryTitle,
            'slug' => StringHelper::slugify($entryTitle),
        ],
    ]);
}

it('uses the submission user as the entry author when enabled', function (): void {
    $section = Craft::$app->getEntries()->getSectionByHandle('formieTestEntries');

    if (!$section) {
        test()->markTestSkipped('Formie test section is not available.');
    }

    $submissionUser = User::find()->status(null)->username('formie-seed-user')->one();
    $fallbackUser = User::find()->status(null)->admin(true)->one();

    if (!$submissionUser || !$fallbackUser) {
        test()->markTestSkipped('Seed users are not available.');
    }

    $title = 'Entry Author Test ' . uniqid();
    $submission = new \verbb\formie\elements\Submission();
    $submission->siteId = Craft::$app->getSites()->getPrimarySite()->id;
    $submission->userId = $submissionUser->id;

    $slug = StringHelper::slugify($title);
    $integration = entryAuthorIntegrationConfig($title, $fallbackUser->id);

    expect($integration->sendPayload($submission)->status)->toBe(\verbb\formie\enums\IntegrationStatus::Succeeded);

    $entry = EntryElement::find()
        ->status(null)
        ->sectionId($section->id)
        ->slug($slug)
        ->one();

    expect($entry)->not->toBeNull()
        ->and($entry->authorId)->toBe($submissionUser->id);
});

it('falls back to the default entry author when no submission user is available', function (): void {
    $section = Craft::$app->getEntries()->getSectionByHandle('formieTestEntries');

    if (!$section) {
        test()->markTestSkipped('Formie test section is not available.');
    }

    $fallbackUser = User::find()->status(null)->admin(true)->one();

    if (!$fallbackUser) {
        test()->markTestSkipped('Fallback admin user is not available.');
    }

    $title = 'Entry Author Fallback Test ' . uniqid();
    $submission = new \verbb\formie\elements\Submission();
    $submission->siteId = Craft::$app->getSites()->getPrimarySite()->id;

    $slug = StringHelper::slugify($title);
    $integration = entryAuthorIntegrationConfig($title, $fallbackUser->id);

    expect($integration->sendPayload($submission)->status)->toBe(\verbb\formie\enums\IntegrationStatus::Succeeded);

    $entry = EntryElement::find()
        ->status(null)
        ->sectionId($section->id)
        ->slug($slug)
        ->one();

    expect($entry)->not->toBeNull()
        ->and($entry->authorId)->toBe($fallbackUser->id);
});

it('creates an entry with a thin queue job and stores prepared element evidence', function (): void {
    $author = User::find()->status(null)->admin(true)->one();
    $title = 'Queued Entry ' . uniqid();
    $integration = entryAuthorIntegrationConfig($title, $author->id);
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $attempts = \verbb\formie\Formie::$plugin->getDeliveryAttempts();
    $uid = $attempts->prepare(new \verbb\formie\models\IntegrationExecutionContext($submission->id, $form->id, 'entry', StringHelper::UUID()), 'dispatch');
    $integration->setDeliveryContext($attempts->context($uid), $uid);
    $integration->setQueueJob(new \verbb\formie\jobs\TriggerIntegration(['deliveryAttemptUid' => $uid]));
    $result = $integration->sendPayload($submission);
    expect($result->status)->toBe(\verbb\formie\enums\IntegrationStatus::Succeeded);
    $entry = EntryElement::find()->status(null)->slug(StringHelper::slugify($title))->one();
    expect($entry)->not->toBeNull();
    $attempt = $attempts->get($uid);
    expect((new \craft\db\Query())->from($attempts::DIAGNOSTICS)->where(['attemptId' => $attempt['id'], 'checkpoint' => 'request-prepared'])->exists())->toBeTrue();
    Craft::$app->getElements()->deleteElement($entry);
});
