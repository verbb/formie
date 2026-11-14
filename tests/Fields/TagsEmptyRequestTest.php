<?php

declare(strict_types=1);

use craft\base\Element;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Tags;

function emptyTagsSource(): string
{
    $group = Craft::$app->getTags()->getTagGroupByHandle('formieTestTags');
    expect($group)->not->toBeNull();

    return 'taggroup:' . $group->uid;
}

it('saves an optional Tags field left blank by a visitor', function (mixed $posted): void {
    $form = formie()->form()->tagsField('topics', ['source' => emptyTagsSource()])->create();

    $submission = formie()->submission($form)->with(['topics' => $posted])->allowValidationFailure()->save();

    expect($submission->getErrors())->toBe([])
        ->and($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('topics')->ids())->toBe([]);
})->with([
    'empty hidden input' => [''],
    'only spaces' => ['   '],
]);

it('saves a blank Tags field inside a Group and a Repeater', function (string $method, array $values): void {
    $form = formie()->form()->$method('wrap', ['rows' => [['fields' => [
        ['type' => Tags::class, 'handle' => 'topics', 'label' => 'Topics', 'source' => emptyTagsSource()],
    ]]]])->create();

    $submission = formie()->submission($form)->with(['wrap' => $values])->allowValidationFailure()->save();

    expect($submission->getErrors())->toBe([]);
})->with([
    'group' => ['groupField', ['topics' => '']],
    'repeater' => ['repeaterField', [['topics' => '']]],
]);

it('still reports a required blank Tags field as blank', function (): void {
    $form = formie()->form()->tagsField('topics', ['source' => emptyTagsSource(), 'required' => true])->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setScenario(Element::SCENARIO_LIVE);
    $submission->setFieldValueFromRequest('topics', '');

    expect(Craft::$app->getElements()->saveElement($submission))->toBeFalse()
        ->and($submission->getErrors('topics'))->toBe(['Topics cannot be blank.']);
});

it('still rejects a tag with no title in a posted list', function (): void {
    $form = formie()->form()->tagsField('topics', ['source' => emptyTagsSource()])->create();

    $submission = formie()->submission($form)->with(['topics' => json_encode([['value' => ' ']], JSON_THROW_ON_ERROR)])->allowValidationFailure()->save();

    expect($submission->getErrors('topics'))->toBe(['Select only valid options.']);
});
