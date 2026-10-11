<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

use Craft;
use craft\elements\Tag;
use craft\elements\User;

function elementFieldRequestUsers(): array
{
    $users = User::find()->status('active')->limit(2)->all();

    if (count($users) < 2) {
        $name = 'elementFieldRequest' . uniqid();
        $user = new User([
            'username' => $name,
            'email' => $name . '@example.test',
            'newPassword' => 'password123',
            'active' => true,
            'pending' => false,
        ]);

        expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
        $users = User::find()->status('active')->limit(2)->all();
    }

    return $users;
}

function elementFieldRequestForm(int $allowedUserId): mixed
{
    return formie()
        ->form(['title' => 'Element Request Validation ' . uniqid()])
        ->usersField('people', [
            'sourceType' => 'elements',
            'sourceElements' => [['id' => $allowedUserId]],
        ])
        ->create();
}

it('rejects element query expressions and keeps them away from the normalized query', function (array $payload): void {
    [$allowedUser] = elementFieldRequestUsers();
    $form = elementFieldRequestForm((int)$allowedUser->id);
    $submission = formie()
        ->submission($form)
        ->with(['people' => $payload])
        ->allowValidationFailure()
        ->save();

    expect($submission)->toHaveFieldError('people')
        ->and($submission->getFieldValue('people')->ids())->toBe([]);
})->with([
    'comparison expression' => [['> 0']],
    'operator array' => [['not', 1]],
    'nested expression' => [[['id' => ['> 0']]]],
]);

it('accepts only configured element choices for new submissions', function (): void {
    [$allowedUser, $otherUser] = elementFieldRequestUsers();
    $form = elementFieldRequestForm((int)$allowedUser->id);

    $valid = formie()
        ->submission($form)
        ->with(['people' => [(string)$allowedUser->id]])
        ->save();
    $invalid = formie()
        ->submission($form)
        ->with(['people' => [(string)$otherUser->id]])
        ->allowValidationFailure()
        ->save();

    expect($valid->getFieldValue('people')->ids())->toBe([(int)$allowedUser->id])
        ->and($invalid)->toHaveFieldError('people')
        ->and($invalid->getFieldValue('people')->ids())->toBe([]);
});

it('lets existing submissions retain or remove historical selections', function (): void {
    [$allowedUser, $historicalUser] = elementFieldRequestUsers();
    $form = elementFieldRequestForm((int)$allowedUser->id);
    $submission = new Submission();
    $submission->setForm($form);
    $submission->title = 'Historical Element Selection';
    $submission->setFieldValue('people', [(int)$historicalUser->id]);

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $submission = Submission::find()->id($submission->id)->status(null)->one();
    expect($submission)->not->toBeNull();

    $submission->setFieldValueFromRequest('people', [(string)$historicalUser->id, (string)$allowedUser->id]);

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue()
        ->and($submission->getFieldValue('people')->ids())->toBe([(int)$historicalUser->id, (int)$allowedUser->id]);

    $submission->setFieldValueFromRequest('people', []);

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue()
        ->and($submission->getFieldValue('people')->ids())->toBe([]);
});

it('keeps typed tag creation inside the configured tag group', function (): void {
    $group = Craft::$app->getTags()->getTagGroupByHandle('formieTestTags');
    expect($group)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Tag Request Validation ' . uniqid()])
        ->tagsField('topics', ['source' => 'taggroup:' . $group->uid])
        ->create();
    $title = 'Typed Tag ' . uniqid();
    $submission = formie()
        ->submission($form)
        ->with(['topics' => json_encode([['value' => $title]], JSON_THROW_ON_ERROR)])
        ->save();
    $tag = Tag::find()->group($group)->title($title)->one();

    expect($tag)->not->toBeNull()
        ->and($submission->getFieldValue('topics')->ids())->toBe([(int)$tag->id]);
});
