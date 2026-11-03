<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\models\HtmlSubmissionRequest;

it('advances the posted page after recovering a cached form version', function(string $submitMethod, int $pageCount): void {
    $form = formie()->form()
        ->settings(['automaticSubmissionState' => true, 'disableCaptchas' => true, 'submitMethod' => $submitMethod])
        ->multiPage($pageCount)
        ->onPage(1)->singleLineTextField('company', ['required' => true])
        ->radioField('audience', ['options' => [['label' => 'Company', 'value' => 'company'], ['label' => 'Other', 'value' => 'other']]])
        ->onPage($pageCount)->emailField('email', ['required' => true])
        ->create();
    $pages = $form->getPages();

    foreach (array_slice($pages, 1, -1) as $page) {
        $page->setPageSettings([
            'enablePageConditions' => true,
            'pageConditions' => [
                'showRule' => 'hide', 'conditionRule' => 'all',
                'conditions' => [['field' => 'audience', 'condition' => '=', 'value' => 'company']],
            ],
        ]);
    }

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $lastPage = $pages[$pageCount - 1];
    $prior = formie()->submission($form)->with(['company' => 'Original', 'audience' => 'company'])->save();
    $prior->isIncomplete = true;
    expect(Craft::$app->getElements()->saveElement($prior))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function() use ($form, $pages, $lastPage, $prior): void {
        Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $prior, $lastPage->id);

        $input = new HtmlSubmissionRequest([
            'handle' => $form->handle,
            'submitAction' => 'submit',
            'pageId' => (int)$pages[0]->id,
            'draftContextToken' => $form->getDraftContextToken(),
            'expectedVersion' => 0,
            'fieldParamNamespace' => 'fields',
        ]);
        $conflict = runHtmlSubmission($input);
        expect($conflict->response->outcome->type->value)->toBe('stateConflict')
            ->and((string)$conflict->response->submission->getFieldValue('company'))->toBe('Original');

        // Match the browser's recovery: accept the returned identity and version,
        // but submit the page actually shown by the cached markup.
        $input->submissionUid = $conflict->response->submission->uid;
        $input->expectedVersion = $conflict->response->outcome->version;
        $input->requestToken = null;
        $retry = runHtmlSubmission($input);

        expect($retry->response->outcome->type->value)->toBe('pageChanged')
            ->and($retry->response->nextPage?->id)->toBe($lastPage->id)
            ->and($retry->response->submission->getErrors())->toBe([])
            ->and($retry->response->submission->id)->toBe($prior->id)
            ->and((string)$retry->response->submission->getFieldValue('company'))->toBe('Updated');
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['company' => 'Updated', 'audience' => 'company', 'email' => '']]]);
})->with(['ajax', 'page-reload'])->with([2, 7]);
