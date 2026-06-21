<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use verbb\formie\elements\Submission;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\resolvers\mutations\SubmissionResolver;

it('registers a generic saveSubmission mutation when submission scopes are available', function (): void {
    withSaveSubmissionGraphqlScope([
        'formieSubmissions.all:create',
    ], function (): void {
        $mutations = SubmissionMutation::getMutations();

        expect($mutations)->toHaveKey('saveSubmission')
            ->and($mutations['saveSubmission']['name'])->toBe('saveSubmission')
            ->and(array_keys($mutations['saveSubmission']['args']))->toContain('formHandle', 'fields', 'captchas');
    });
});

it('creates a submission through saveSubmission with a fields map', function (): void {
    $form = formie()
        ->form([
            'title' => 'Generic GraphQL Save ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->emailField('emailAddress', ['required' => true])
        ->create();

    $initialCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();
    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);

    $submission = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($resolver, $form, $resolveInfo): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'fields' => [
                'yourName' => 'Peter Sherman',
                'emailAddress' => 'peter@example.test',
            ],
        ], null, $resolveInfo);
    });

    $finalCount = (int)Submission::find()->formId($form->id)->status(null)->isSpam(null)->isIncomplete(null)->count();

    expect($submission)->toBeInstanceOf(Submission::class)
        ->and($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('yourName'))->toBe('Peter Sherman')
        ->and($submission->getFieldValue('emailAddress'))->toBe('peter@example.test')
        ->and($finalCount)->toBe($initialCount + 1);
});

it('updates an existing submission through saveSubmission', function (): void {
    $form = formie()
        ->form([
            'title' => 'Generic GraphQL Save Update ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();

    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);

    $created = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
        'formieSubmissions.' . $form->uid . ':save',
    ], function () use ($resolver, $form, $resolveInfo): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'fields' => [
                'yourName' => 'Before',
            ],
        ], null, $resolveInfo);
    });

    $updated = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
        'formieSubmissions.' . $form->uid . ':save',
    ], function () use ($resolver, $form, $resolveInfo, $created): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'id' => $created->id,
            'fields' => [
                'yourName' => 'After',
            ],
        ], null, $resolveInfo);
    });

    expect($updated->id)->toBe($created->id)
        ->and($updated->getFieldValue('yourName'))->toBe('After');
});

it('requires create or save scopes for the targeted form', function (): void {
    $allowedForm = formie()
        ->form([
            'title' => 'Allowed Generic GraphQL Save ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();
    $blockedForm = formie()
        ->form([
            'title' => 'Blocked Generic GraphQL Save ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();

    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);

    withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $allowedForm->uid . ':create',
    ], function () use ($resolver, $blockedForm, $resolveInfo): void {
        expect(fn() => $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$blockedForm->handle,
            'fields' => [
                'yourName' => 'Nope',
            ],
        ], null, $resolveInfo))->toThrow(Error::class, 'Unable to perform the action.');
    });
});

it('rejects saveSubmission updates without save scope', function (): void {
    $form = formie()
        ->form([
            'title' => 'Generic GraphQL Save Scope ' . uniqid(),
            'handle' => saveSubmissionGraphqlHandle(),
        ])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->create();

    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);

    $created = withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($resolver, $form, $resolveInfo): Submission {
        return $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'fields' => [
                'yourName' => 'Initial',
            ],
        ], null, $resolveInfo);
    });

    withSaveSubmissionGraphqlScope([
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($resolver, $form, $resolveInfo, $created): void {
        expect(fn() => $resolver->saveSubmissionByHandle(null, [
            'formHandle' => (string)$form->handle,
            'id' => $created->id,
            'fields' => [
                'yourName' => 'Updated',
            ],
        ], null, $resolveInfo))->toThrow(Error::class, 'Unable to perform the action.');
    });
});

function withSaveSubmissionGraphqlScope(array $scope, callable $callback): mixed
{
    $gqlService = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie Generic Save Submission Test Schema',
        'scope' => $scope,
    ]));

    try {
        return $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }
}

function saveSubmissionGraphqlHandle(): string
{
    static $counter = 700;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = 'saveSubmission' . $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (strlen($handle) > 26);

    return $handle;
}
