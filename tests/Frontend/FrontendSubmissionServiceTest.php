<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\client\models\LoadContext;
use verbb\formie\client\models\SubmitRequest;
use Tests\Support\WebRequestTestHelper;

it('submits a client payload through the current bootstrap session contract', function(): void {
    $form = formie()
        ->form([
            'title' => 'Frontend Submit ' . uniqid(),
        ])
        ->singleLineTextField('fullName', ['required' => true])
        ->emailField('emailAddress', ['required' => true])
        ->create();

    WebRequestTestHelper::withWebRequestContext(function() use ($form) {
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext([
            'handle' => $form->handle,
        ]));

        $result = runClientSubmission(new SubmitRequest([
            'handle' => $form->handle,
            'action' => 'submit',
            'session' => $bootstrap->session->toArrayRecursive(),
            'values' => [
                'fullName' => 'Peter Sherman',
                'emailAddress' => 'peter@example.test',
            ],
        ]))->toArrayRecursive();

        expect($result['success'])->toBeTrue()
            ->and($result['errors']['form'] ?? [])->toBe([])
            ->and($result['errors']['fields'] ?? [])->toBeArray();
    });
});

it('carries explicit operation IDs through interactive GraphQL and recovers after a token refresh', function (): void {
    $form = formie()->form()->singleLineTextField('name', ['required' => true])->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form) {
        $gql = Craft::$app->getGql();
        $previous = null;
        try {
            $previous = $gql->getActiveSchema();
        } catch (\craft\errors\GqlException) {
        }
        $schema = new \craft\models\GqlSchema(['name' => 'Workflow test', 'scope' => [
            'formieForms.' . $form->uid . ':read', 'formieSubmissions.' . $form->uid . ':create',
        ]]);
        $gql->setActiveSchema($schema);
        try {
            $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext(['handle' => $form->handle]));
            $input = ['handle' => $form->handle, 'operationId' => 'graphql-retry', 'session' => $bootstrap->session->toArrayRecursive(), 'values' => ['name' => '']];
            $invalid = \verbb\formie\gql\resolvers\ClientFormResolver::submitForm(null, ['input' => $input]);
            expect($invalid['outcome'])->toBe('validationFailed');
            $input['values']['name'] = 'GraphQL';
            $first = \verbb\formie\gql\resolvers\ClientFormResolver::submitForm(null, ['input' => $input]);
            $input['session']['tokens']['request'] = Formie::$plugin->getSubmissionGuards()->issueRequestToken($form);
            $retry = \verbb\formie\gql\resolvers\ClientFormResolver::submitForm(null, ['input' => $input]);
            expect($first['outcome'])->toBe('completed')->and($retry['submissionUid'])->toBe($first['submissionUid'])
                ->and((int)\verbb\formie\elements\Submission::find()->formId($form->id)->count())->toBe(1);
            $input['session']['continuation']['draftContext'] = 'changed-context';
            $changed = \verbb\formie\gql\resolvers\ClientFormResolver::submitForm(null, ['input' => $input]);
            expect($changed['outcome'])->toBe('stateConflict');
        } finally {
            $gql->setActiveSchema($previous);
        }
    }, ['method' => 'POST']);
});

it('exchanges revision grants through REST and GraphQL with refresh and stale-tab parity', function (string $transport): void {
    $form = formie()->form()->singleLineTextField('name')->settings(['disableCaptchas' => true])->create();
    $submission = formie()->submission($form)->with(['name' => 'Original'])->save();
    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission, $transport) {
        $gql = Craft::$app->getGql();
        try { $previous = $gql->getActiveSchema(); } catch (\craft\errors\GqlException) { $previous = null; }
        $gql->setActiveSchema(new \craft\models\GqlSchema(['name' => 'Grant parity', 'scope' => ['formieForms.' . $form->uid . ':read', 'formieSubmissions.' . $form->uid . ':create']]));
        try {
            $grant = Formie::$plugin->getSubmissionGrants()->issue($submission, \verbb\formie\services\SubmissionGrants::REVISE);
            $arguments = ['handle' => $form->handle, 'grantToken' => $grant->token, 'grantPurpose' => 'revise-complete'];
            if ($transport === 'graphql') {
                $bootstrap = \verbb\formie\gql\resolvers\ClientFormResolver::resolveForm(null, $arguments);
            } else {
                Craft::$app->getRequest()->setBodyParams($arguments);
                $controller = new \verbb\formie\controllers\client\FormsController('forms', Formie::$plugin);
                $bootstrap = $controller->actionLoad()->data;
            }
            expect($bootstrap['session']['continuation']['purpose'])->toBe('revise-complete');
            $refreshed = Formie::$plugin->getClientSessionService()->refreshSession(new \verbb\formie\client\models\SessionRefreshRequest(['handle' => $form->handle, 'session' => $bootstrap['session']]))->toArrayRecursive();
            expect($refreshed['continuation']['purpose'])->toBe('revise-complete');
            $input = ['handle' => $form->handle, 'operationId' => 'first-revision', 'session' => $refreshed, 'values' => ['name' => 'Revised']];
            $submit = fn(array $payload) => $transport === 'graphql'
                ? \verbb\formie\gql\resolvers\ClientFormResolver::submitForm(null, ['input' => $payload])
                : runClientSubmission(new SubmitRequest($payload))->toArrayRecursive();
            $first = $submit($input);
            expect($first['outcome'])->toBe('revised')->and($first['version'])->toBeGreaterThan($refreshed['version']);
            $input['operationId'] = 'stale-revision';
            $input['session']['tokens']['request'] = Formie::$plugin->getSubmissionGuards()->issueRequestToken($form);
            $stale = $submit($input);
            expect($stale['outcome'])->toBe('stateConflict');
            $fresh = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
            expect((string)$fresh->getFieldValue('name'))->toBe('Revised');
        } finally {
            $gql->setActiveSchema($previous);
        }
    }, ['method' => 'POST']);
})->with(['rest', 'graphql']);

it('returns a portable grant only for explicit client save and resumes the same progress', function (): void {
    $form = formie()->form()->multiPage(2)->onPage(1)->singleLineTextField('name')->settings(['disableCaptchas' => true])->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form) {
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext(['handle' => $form->handle]));
        $result = runClientSubmission(new SubmitRequest(['handle' => $form->handle, 'action' => 'save', 'session' => $bootstrap->session->toArrayRecursive(), 'values' => ['name' => 'Saved']]))->toArrayRecursive();
        expect($result['outcome'])->toBe('draftSaved')->and($result['resumeToken'])->not->toBeEmpty();
        $progress = Formie::$plugin->getSubmissionProgress()->getProgressState($form);
        Craft::$app->getSession()->set('formie:authority', 'second-client');
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()->build($form, new LoadContext(['handle' => $form->handle, 'grantToken' => $result['resumeToken']]));
        expect($bootstrap->session->continuation['progressId'])->toBe((string)$progress->id);
    }, ['method' => 'POST']);
});
