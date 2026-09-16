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
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

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
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

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
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

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
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

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
    return 'test' . bin2hex(random_bytes(8));
}

it('executes generic submission mutations with typed nested field values', function (): void {
    $form = formie()->form(['handle' => saveSubmissionGraphqlHandle()])
        ->settings(['disableCaptchas' => true])
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new \verbb\formie\fields\Name())->getSubFields()])
        ->repeaterField('items', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class,
            'handle' => 'description',
            'label' => 'Description',
        ]]]]])
        ->create();

    withSaveSubmissionGraphqlScope(['formieSubmissions.all:create', 'formieSubmissions.all:read'], function () use ($form): void {
        $schema = new \GraphQL\Type\Schema([
            'query' => new \GraphQL\Type\Definition\ObjectType([
                'name' => 'AuditQuery',
                'fields' => ['ping' => \GraphQL\Type\Definition\Type::string()],
            ]),
            'mutation' => new \GraphQL\Type\Definition\ObjectType([
                'name' => 'AuditMutation',
                'fields' => ['saveSubmission' => SubmissionMutation::createGenericSaveMutation()],
            ]),
            'types' => [\verbb\formie\gql\types\generators\SubmissionGenerator::generateType($form)],
        ]);
        $result = \GraphQL\GraphQL::executeQuery($schema,
            'mutation ($handle: String!, $fields: Array) { saveSubmission(formHandle: $handle, fields: $fields) { id } }',
            null, null, [
                'handle' => $form->handle,
                'fields' => [
                    'person' => ['firstName' => 'Jane', 'lastName' => 'Doe'],
                    'items' => ['rows' => [['description' => 'First'], ['description' => 'Second']]],
                ],
            ]);

        expect(array_map(fn($error) => $error->getMessage(), $result->errors))->toBe([]);
        $submission = Submission::find()->id($result->data['saveSubmission']['id'])->status(null)->isIncomplete(null)->isSpam(null)->one();
        expect($submission->getFieldValue('person')->firstName)->toBe('Jane');
        $rows = $submission->getFieldValue('items');
        expect($rows[0]['description'] ?? null)->toBe('First')
            ->and($rows[1]['description'] ?? null)->toBe('Second');
    });
});

it('rejects unknown or malformed generic field and captcha maps before saving', function (array $payload, string $message): void {
    $form = formie()->form(['handle' => saveSubmissionGraphqlHandle()])
        ->settings(['disableCaptchas' => true])
        ->singleLineTextField('yourName')
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new \verbb\formie\fields\Name())->getSubFields()])
        ->create();
    $resolver = Craft::createObject(SubmissionResolver::class);
    $resolveInfo = test()->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create(SubmissionMutation::createGenericSaveMutation());

    withSaveSubmissionGraphqlScope(['formieSubmissions.all:create'], function () use ($form, $resolver, $resolveInfo, $payload, $message): void {
        expect(fn() => $resolver->saveSubmissionByHandle(null, ['formHandle' => $form->handle] + $payload, null, $resolveInfo))
            ->toThrow(Error::class, $message);
        expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->isSpam(null)->count())->toBe(0);
    });
})->with([
    'element attribute in fields' => [['fields' => ['formId' => 123]], 'Unknown fields argument'],
    'identifier in fields' => [['fields' => ['id' => 123]], 'Unknown fields argument'],
    'field in captchas' => [['captchas' => ['yourName' => 'Changed']], 'Unknown captchas argument'],
    'wrong scalar type' => [['fields' => ['yourName' => ['nested']]], 'Invalid fields argument'],
    'unknown nested field' => [['fields' => ['person' => ['unknown' => 'value']]], 'Invalid fields argument'],
]);

it('clears optional nested fields through GraphQL submission updates', function (string $fieldHandle, bool $generic): void {
    $form = formie()->form()->settings(['disableCaptchas' => true])
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new \verbb\formie\fields\Name())->getSubFields()])
        ->groupField('details', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'note', 'label' => 'Note',
        ]]]]])
        ->repeaterField('items', ['rows' => [['fields' => [[
            'type' => \verbb\formie\fields\SingleLineText::class, 'handle' => 'description', 'label' => 'Description',
        ]]]]])
        ->create();
    $submission = formie()->submission($form)->with([
        'person' => ['firstName' => 'Before'],
        'details' => ['note' => 'Before'],
        'items' => [['description' => 'Before']],
    ])->save();

    $mutation = $generic ? SubmissionMutation::createGenericSaveMutation() : SubmissionMutation::createSaveMutation($form);
    $resolveInfo = $this->createMock(ResolveInfo::class);
    $resolveInfo->fieldDefinition = \GraphQL\Type\Definition\FieldDefinition::create($mutation);
    $arguments = $generic
        ? ['id' => $submission->id, 'formHandle' => $form->handle, 'fields' => [$fieldHandle => null]]
        : ['id' => $submission->id, $fieldHandle => null];

    $saved = withSaveSubmissionGraphqlScope(['formieSubmissions.all:save'], fn() => ($mutation['resolve'])(null, $arguments, null, $resolveInfo));
    expect($form->getFieldByHandle($fieldHandle)->isValueEmpty($saved->getFieldValue($fieldHandle), $saved))->toBeTrue();
})->with(['person', 'details', 'items'])->with([true, false]);
