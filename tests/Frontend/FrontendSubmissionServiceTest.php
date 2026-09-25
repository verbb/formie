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
