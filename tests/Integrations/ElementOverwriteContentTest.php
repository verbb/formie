<?php

declare(strict_types=1);

use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\helpers\StringHelper;
use verbb\formie\base\Element;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\models\IntegrationRunContext;

function overwriteContentRun(string $answer): array
{
    $section = Craft::$app->getEntries()->getSectionByHandle('formieTestEntries');
    $entryType = Craft::$app->getEntries()->getEntryTypesBySectionId($section->id)[0];
    $slug = 'existing-' . StringHelper::randomString(10, false);
    $existing = new EntryElement(['sectionId' => $section->id, 'typeId' => $entryType->id, 'slug' => $slug, 'authorId' => User::find()->admin()->one()->id]);
    expect(Craft::$app->getElements()->saveElement($existing))->toBeTrue();

    $form = formie()->form(['title' => 'Overwrite Content'])->singleLineTextField('entryId')->singleLineTextField('slugAnswer')->create();
    $saved = formie()->submission($form)->with(['entryId' => (string)$existing->id, 'slugAnswer' => $answer])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();

    $integration = new Entry([
        'name' => 'Entry',
        'handle' => 'entryOverwrite',
        'entryTypeSection' => $section->uid . ':' . $entryType->uid,
        'defaultAuthorId' => $existing->authorId,
        'updateElement' => true,
        'overwriteValues' => false,
        'updateElementMapping' => ['id' => References::field($form->getFieldByHandle('entryId')->reference)],
        'attributeMapping' => ['slug' => References::field($form->getFieldByHandle('slugAnswer')->reference)],
    ]);

    $result = $integration->execute(IntegrationRunContext::forIntegration($integration, $submission));
    expect($result->status)->toBe(\verbb\formie\enums\IntegrationStatus::Succeeded);

    $updated = EntryElement::find()->id($existing->id)->status(null)->one();
    return [$updated, $slug];
}

it('leaves existing content alone when an answer is empty and Overwrite Content is off', function(): void {
    [$entry, $slug] = overwriteContentRun('');

    expect($entry->slug)->toBe($slug);
});

it('updates existing content when an answer is provided and Overwrite Content is off', function(): void {
    expect(overwriteContentRun('new-slug-' . uniqid('', false))[0]->slug)->toStartWith('new-slug-');
});

it('keeps zero and false when removing empty mapped values', function(): void {
    $method = new ReflectionMethod(Element::class, 'withoutEmptyMappedValues');
    $integration = new Entry(['name' => 'Entry', 'handle' => 'entryOverwrite']);

    expect($method->invoke($integration, ['a' => 0, 'b' => false, 'c' => '0', 'd' => '', 'e' => null, 'f' => []]))
        ->toBe(['a' => 0, 'b' => false, 'c' => '0']);
});
