<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\models\GqlSchema;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\Formie;
use verbb\formie\gql\resolvers\ClientFormResolver;
use verbb\formie\models\FormSubmitRequest;
use verbb\formie\models\HtmlSubmissionRequest;

// Staged bytes come only from real multipart uploads or decoded GraphQL file data. Posted
// `mutationData` entries that name a server path must never move that file into an upload.

function mutationDataPathForm(): Form
{
    $location = 'volume:' . UploadTestHelper::ensureUploadVolume()->uid;

    return formie()->form(['title' => 'Mutation data path ' . uniqid()])->settings(['disableCaptchas' => true])
        ->fileUploadField('document', ['allowedKinds' => ['text'], 'uploadLocationSource' => $location])
        ->create();
}

function mutationDataPathTarget(): string
{
    $directory = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'mutation-path-' . uniqid();
    mkdir($directory, 0775, true);

    $path = $directory . DIRECTORY_SEPARATOR . 'server-secret.txt';
    file_put_contents($path, 'server secret');

    return $path;
}

function mutationDataPathSubmit(Form $form, string $transport, string $action, array $document): void
{
    WebRequestTestHelper::withWebRequestContext(function($request) use ($form, $transport, $action, $document): void {
        $session = Formie::$plugin->getFormSessions()->issueInitialSession($form)->toArrayRecursive();

        if ($transport === 'html') {
            $request->setBodyParams([
                'formieHoneypot' => '',
                'formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000),
                'handle' => $form->handle,
                'requestToken' => $session['tokens']['request'],
                'expectedVersion' => 0,
                'submitAction' => $action,
                'fields' => ['document' => $document],
            ]);

            Formie::$plugin->getSubmissionRequests()->executeHtml(new HtmlSubmissionRequest([
                'handle' => $form->handle,
                'requestToken' => $session['tokens']['request'],
                'expectedVersion' => 0,
                'submitAction' => $action,
            ]), SubmissionAuthorityType::VISITOR);

            return;
        }

        $input = [
            'handle' => $form->handle,
            'action' => $action,
            'session' => $session,
            'values' => ['document' => $document],
        ];

        if ($transport === 'rest') {
            runClientSubmission(new FormSubmitRequest($input));

            return;
        }

        $gql = Craft::$app->getGql();

        try {
            $previous = $gql->getActiveSchema();
        } catch (GqlException) {
            $previous = null;
        }

        $gql->setActiveSchema(new GqlSchema([
            'name' => 'Mutation data path',
            'scope' => [
                'formieForms.' . $form->uid . ':read',
                'formieSubmissions.' . $form->uid . ':create',
            ],
        ]));

        try {
            ClientFormResolver::submitForm(null, ['input' => $input]);
        } catch (Throwable) {
            // A rejected request is fine. Only the file on disk matters here.
        } finally {
            $gql->setActiveSchema($previous);
        }
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => $transport === 'html' ? 'text/html' : 'application/json'],
    ]);
}

it('never moves a server path posted as mutationData', function (string $transport, string $action, string $type): void {
    $form = mutationDataPathForm();
    $path = mutationDataPathTarget();

    mutationDataPathSubmit($form, $transport, $action, ['mutationData' => [[
        'type' => $type,
        'path' => $path,
        'filename' => 'moved-' . uniqid() . '.txt',
    ]]]);

    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))->toBe('server secret');
})->with(['html', 'rest', 'graphql'])->with(['submit', 'save'])->with(['file', 'upload']);
