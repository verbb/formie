<?php

declare(strict_types=1);

use craft\elements\Entry;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\services\SubmissionWorkflow;

dataset('finalize_submit_methods', ['ajax', 'page-reload']);
dataset('finalize_actions', ['message', 'reload', 'reset', 'url', 'entry']);

beforeEach(function () { \verbb\formie\Formie::$plugin->getSettings()->completionRedirectAllowedOrigins = ['https://example.test', 'http://formie-react-tests.ddev.site']; });

it('keeps finalize submit actions stable across ajax and page-reload submit methods', function (string $submitMethod, string $submitAction): void {
    $form = formie()
        ->form(['title' => "Finalize {$submitAction} {$submitMethod}"])
        ->singleLineTextField('fullName', ['required' => true])
        ->settings(['disableCaptchas' => true])
        ->create();

    $form->settings->setAttributes([
        'submitMethod' => $submitMethod,
        'submitAction' => $submitAction,
        'submitActionMessage' => 'Submission completed successfully.',
        'submitActionMessagePosition' => 'top-form',
        'submitActionMessageTimeout' => 5,
        'submitActionFormHide' => true,
        'submitActionTab' => 'same-tab',
    ], false);

    if ($submitAction === 'url') {
        $form->settings->setAttributes([
            'submitActionUrl' => 'https://example.test/finalized',
        ], false);
    }

    if ($submitAction === 'entry') {
        $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
        expect($entry)->not->toBeNull();
        $form->submitActionEntryId = $entry->id;
        $form->submitActionEntrySiteId = $entry->siteId;
    }

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', 'Finalize Matrix');

    $response = runSubmissionCommand(submissionCommand([
        'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
    ]));

    $clientConfig = $form->getCpEditConfig();
    $settings = $clientConfig['settings'] ?? [];

    expect($response->success)->toBeTrue(json_encode($response->submission->getErrors()))
        ->and($response->submission->id)->not->toBeNull()
        ->and($settings['submitMethod'] ?? null)->toBe($submitMethod)
        ->and($form->settings->submitAction)->toBe($submitAction)
        ->and($form->settings->submitActionMessagePosition)->toBe('top-form')
        ->and((int)$form->settings->submitActionMessageTimeout)->toBe(5)
        ->and($form->settings->submitActionFormHide)->toBeTrue();

    if ($submitAction === 'url') {
        expect((string)$form->getRedirectUrl())->toContain('example.test/finalized');
    }

    if ($submitAction === 'entry') {
        expect((string)$form->getRedirectUrl())->toContain('formie-seed-entry');
    }
})->with('finalize_submit_methods')->with('finalize_actions');
