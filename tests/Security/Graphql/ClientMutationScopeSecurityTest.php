<?php

declare(strict_types=1);

use craft\db\Query;
use craft\errors\GqlException;
use craft\models\GqlSchema;
use GraphQL\Error\Error;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\gql\resolvers\ClientFormResolver;
use verbb\formie\helpers\Table;
use verbb\formie\services\SubmissionGrants;

function withClientMutationGraphqlScope(array $scope, callable $callback): void
{
    $gqlService = Craft::$app->getGql();
    $activeSchema = null;

    try {
        $activeSchema = $gqlService->getActiveSchema();
    } catch (GqlException) {
        // No active schema is normal in the test runtime.
    }

    $gqlService->setActiveSchema(new GqlSchema([
        'name' => 'Formie Client Mutation Scope Security Test Schema',
        'scope' => $scope,
    ]));

    try {
        $callback();
    } finally {
        $gqlService->setActiveSchema($activeSchema);
    }
}

function withClientMutationGraphqlRequest(array $scope, callable $callback): void
{
    WebRequestTestHelper::withWebRequestContext(function () use ($scope, $callback): void {
        withClientMutationGraphqlScope($scope, $callback);
    }, ['method' => 'POST']);
}

function clientMutationScopeForm(bool $multiPage = false): \verbb\formie\elements\Form
{
    $factory = formie()->form(['title' => 'Client Mutation Scope ' . bin2hex(random_bytes(5))]);

    if ($multiPage) {
        return $factory
            ->multiPage(2)
            ->onPage(1)
            ->singleLineTextField('firstName', ['required' => true])
            ->onPage(2)
            ->singleLineTextField('lastName', ['required' => true])
            ->create();
    }

    return $factory->singleLineTextField('fullName', ['required' => true])->create();
}

function clientMutationPortableContinueFixture(): array
{
    $form = clientMutationScopeForm();
    $submission = formie()->submission($form)->with(['fullName' => 'Original'])->save();
    $submission->isIncomplete = true;

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();

    $progress = Formie::$plugin->getSubmissionProgress()->upsertProgressState(
        $form,
        $submission,
        $form->getCurrentPage()?->id,
    );
    $grant = Formie::$plugin->getSubmissionGrants()->issue(
        $submission,
        SubmissionGrants::CONTINUE,
        $progress?->id,
    );

    return compact('form', 'submission', 'grant');
}

it('denies fresh client mutations to save-only GraphQL schemas', function (bool $globalScope): void {
    $form = clientMutationScopeForm(true);
    $scope = [
        'formieForms.' . $form->uid . ':read',
        $globalScope ? 'formieSubmissions.all:save' : 'formieSubmissions.' . $form->uid . ':save',
    ];

    withClientMutationGraphqlRequest($scope, function () use ($form): void {
        $bootstrap = ClientFormResolver::resolveForm(null, ['handle' => (string)$form->handle]);
        $session = $bootstrap['session'];

        expect(fn() => ClientFormResolver::refreshSession(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $session,
            ],
        ]))->toThrow(Error::class, 'Unable to perform the action.');

        $pages = $form->getPages();
        $pageResult = ClientFormResolver::setPage(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'currentPageId' => (string)$pages[0]->id,
                'targetPageId' => (string)$pages[1]->id,
                'session' => $session,
                'values' => ['firstName' => 'Save Only'],
            ],
        ]);
        $submitResult = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $session,
                'values' => ['firstName' => 'Save Only'],
            ],
        ]);

        expect($pageResult['success'])->toBeFalse()
            ->and($pageResult['httpStatus'])->toBe(403)
            ->and($submitResult['success'])->toBeFalse()
            ->and($submitResult['httpStatus'])->toBe(403);
    });

    expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(0);
})->with([false, true])->group('security');

it('allows create-only GraphQL schemas to complete their own multi-page journey', function (): void {
    $form = clientMutationScopeForm(true);

    withClientMutationGraphqlRequest([
        'formieForms.' . $form->uid . ':read',
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($form): void {
        $bootstrap = ClientFormResolver::resolveForm(null, ['handle' => (string)$form->handle]);
        $first = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $bootstrap['session'],
                'values' => ['firstName' => 'Create'],
            ],
        ]);

        expect($first['success'])->toBeTrue()
            ->and($first['outcome'])->toBe('pageChanged')
            ->and($first['submissionUid'])->not->toBeNull();

        $refreshed = ClientFormResolver::refreshSession(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $first['session'],
            ],
        ]);
        $completed = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $refreshed,
                'values' => ['lastName' => 'Journey'],
            ],
        ]);

        expect($completed['success'])->toBeTrue()
            ->and($completed['outcome'])->toBe('completed')
            ->and($completed['submissionUid'])->toBe($first['submissionUid']);
    });

    expect((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(1);
})->group('security');

it('keeps a direct create journey after the same browser opens a portable grant', function (): void {
    $form = clientMutationScopeForm(true);

    withClientMutationGraphqlRequest([
        'formieForms.' . $form->uid . ':read',
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($form): void {
        $bootstrap = ClientFormResolver::resolveForm(null, ['handle' => (string)$form->handle]);
        $first = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $bootstrap['session'],
                'values' => ['firstName' => 'Original'],
            ],
        ]);
        $submission = Submission::find()->uid($first['submissionUid'])->status(null)->isIncomplete(true)->one();
        $progress = Formie::$plugin->getSubmissionProgress()->loadProgress((int)$first['session']['continuation']['progressId']);
        $grant = Formie::$plugin->getSubmissionGrants()->issue($submission, SubmissionGrants::CONTINUE, $progress?->id);

        ClientFormResolver::resolveForm(null, [
            'handle' => (string)$form->handle,
            'grantToken' => $grant->token,
            'grantPurpose' => SubmissionGrants::CONTINUE,
        ]);

        $refreshed = ClientFormResolver::refreshSession(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $first['session'],
            ],
        ]);
        $completed = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $refreshed,
                'values' => ['lastName' => 'Journey'],
            ],
        ]);

        expect($completed['success'])->toBeTrue()
            ->and($completed['outcome'])->toBe('completed')
            ->and($completed['submissionUid'])->toBe($first['submissionUid']);
    });
})->group('security');

it('allows save-only GraphQL schemas to continue a submission through a portable grant', function (): void {
    ['form' => $form, 'submission' => $submission, 'grant' => $grant] = clientMutationPortableContinueFixture();

    withClientMutationGraphqlRequest([
        'formieForms.' . $form->uid . ':read',
        'formieSubmissions.' . $form->uid . ':save',
    ], function () use ($form, $submission, $grant): void {
        $bootstrap = ClientFormResolver::resolveForm(null, [
            'handle' => (string)$form->handle,
            'grantToken' => $grant->token,
            'grantPurpose' => SubmissionGrants::CONTINUE,
        ]);
        $refreshed = ClientFormResolver::refreshSession(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $bootstrap['session'],
            ],
        ]);
        $result = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $refreshed,
                'values' => ['fullName' => 'Continued'],
            ],
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['submissionUid'])->toBe($submission->uid);
    });

    $reloaded = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();

    expect((string)$reloaded?->getFieldValue('fullName'))->toBe('Continued')
        ->and((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(1);
})->group('security');

it('denies portable continuation to create-only GraphQL schemas', function (): void {
    ['form' => $form, 'submission' => $submission, 'grant' => $grant] = clientMutationPortableContinueFixture();

    withClientMutationGraphqlRequest([
        'formieForms.' . $form->uid . ':read',
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($form, $grant): void {
        $bootstrap = ClientFormResolver::resolveForm(null, [
            'handle' => (string)$form->handle,
            'grantToken' => $grant->token,
            'grantPurpose' => SubmissionGrants::CONTINUE,
        ]);
        $result = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $bootstrap['session'],
                'values' => ['fullName' => 'Blocked'],
            ],
        ]);

        expect($result['success'])->toBeFalse()
            ->and($result['httpStatus'])->toBe(403);
    });

    $reloaded = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();

    expect((string)$reloaded?->getFieldValue('fullName'))->toBe('Original')
        ->and($reloaded?->isIncomplete)->toBeTrue();
})->group('security');

it('does not exchange a portable grant during a denied scope preflight', function (): void {
    ['form' => $form, 'grant' => $grant] = clientMutationPortableContinueFixture();

    withClientMutationGraphqlRequest([
        'formieForms.' . $form->uid . ':read',
        'formieSubmissions.' . $form->uid . ':create',
    ], function () use ($form, $grant): void {
        $bootstrap = ClientFormResolver::resolveForm(null, ['handle' => (string)$form->handle]);
        $bootstrap['session']['continuation'] = [
            ...($bootstrap['session']['continuation'] ?? []),
            'grantToken' => $grant->token,
        ];
        $before = (int)(new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['parentId' => $grant->id])->count();
        $result = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'session' => $bootstrap['session'],
                'values' => ['fullName' => 'Blocked'],
            ],
        ]);
        $after = (int)(new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['parentId' => $grant->id])->count();

        expect($result['success'])->toBeFalse()
            ->and($result['httpStatus'])->toBe(403)
            ->and($before)->toBe(0)
            ->and($after)->toBe(0);
    });
})->group('security');

it('denies forged revision state to save-only GraphQL schemas', function (): void {
    $form = clientMutationScopeForm();
    $submission = formie()->submission($form)->with(['fullName' => 'Original'])->save();

    withClientMutationGraphqlRequest([
        'formieForms.' . $form->uid . ':read',
        'formieSubmissions.' . $form->uid . ':save',
    ], function () use ($form, $submission): void {
        $bootstrap = ClientFormResolver::resolveForm(null, ['handle' => (string)$form->handle]);
        $bootstrap['session']['continuation'] = [
            'purpose' => SubmissionGrants::REVISE,
            'submissionId' => (int)$submission->id,
            'progressId' => 999999999,
        ];
        $result = ClientFormResolver::submitForm(null, [
            'input' => [
                'handle' => (string)$form->handle,
                'action' => 'revise',
                'session' => $bootstrap['session'],
                'values' => ['fullName' => 'Forged'],
            ],
        ]);

        expect($result['success'])->toBeFalse()
            ->and($result['httpStatus'])->toBe(403);
    });

    $reloaded = Submission::find()->id($submission->id)->status(null)->one();

    expect((string)$reloaded?->getFieldValue('fullName'))->toBe('Original')
        ->and((int)Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->count())->toBe(1);
})->group('security');
