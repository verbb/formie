<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use Craft;
use verbb\formie\Formie;
use verbb\formie\helpers\CpSubmissionFieldConditions;
it('resolves cp submission field condition settings from form and plugin defaults', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Submission Condition Settings',
    ]);

    Formie::$plugin->getSettings()->defaultCpSubmissionFieldConditions = CpSubmissionFieldConditions::MUTED;

    expect($form->getCpSubmissionFieldConditions())->toBe(CpSubmissionFieldConditions::MUTED);

    $form->settings->cpSubmissionFieldConditions = CpSubmissionFieldConditions::SHOW_ALL;
    $form->setSettings($form->settings);

    expect($form->getCpSubmissionFieldConditions())->toBe(CpSubmissionFieldConditions::SHOW_ALL)
        ->and($form->cpSubmissionFollowsFieldConditions())->toBeFalse();
});

it('marks conditionally hidden cp submission fields for follow and muted modes', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Submission Field Markup',
    ]);

    $submission = formie()->submission($form)->with([
        'enquiryType' => 'support',
        'otherReason' => 'Hidden Value',
    ])->save();

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): void {
        Craft::$app->getRequest()->setIsCpRequest(true);

        $hiddenField = $form->getFieldByHandle('otherReason');
        $html = (string)$hiddenField?->getSubmissionHtml($submission->getFieldValue('otherReason'), $submission);

        expect($html)
            ->toContain('data-formie-field')
            ->toContain('data-formie-conditions')
            ->toContain('data-formie-conditionally-hidden')
            ->toContain('formie-conditionally-hidden')
            ->toContain('class="field formie-conditionally-hidden"');

        $form->settings->cpSubmissionFieldConditions = CpSubmissionFieldConditions::MUTED;
        $form->setSettings($form->settings);

        $mutedHtml = (string)$hiddenField?->getSubmissionHtml($submission->getFieldValue('otherReason'), $submission);

        expect($mutedHtml)
            ->toContain('data-formie-cp-muted')
            ->toContain('fui-cp-muted-conditional-field')
            ->toContain('class="field fui-cp-muted-conditional-field"')
            ->not->toContain('data-formie-conditionally-hidden');
    });
});

it('clears conditionally hidden field values when saving submissions from the control panel', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Submission Save Conditions',
    ]);

    $submission = formie()->submission($form)->with([
        'enquiryType' => 'support',
        'otherReason' => 'Should Be Cleared',
    ])->save();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $submission): void {
        $request->setIsCpRequest(true);
        $request->setBodyParams([
            'handle' => $form->handle,
            'submissionId' => (int)$submission->id,
            'siteId' => (int)$submission->siteId,
            'fields' => [
                'enquiryType' => 'support',
                'otherReason' => 'Should Be Cleared',
            ],
        ]);

        (new verbb\formie\controllers\SubmissionsController('formie-submissions-test', Craft::$app))->actionSaveSubmission();
    }, [
        'method' => 'POST',
        'hostInfo' => 'https://craft.example.test',
        'httpHost' => 'craft.example.test',
        'requestUri' => "/admin/formie/submissions/{$form->handle}/{$submission->id}",
    ]);

    $saved = formie()->submission($form)->find($submission->id);

    expect($saved?->getFieldValue('otherReason'))->toBeNull();
});

it('includes cp conditions modules in submission edit config by default', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'CP Submission Client Config',
    ]);

    $moduleIds = array_values(array_map(
        static fn(array $module): string => (string)$module['id'],
        $form->getClientConfig()['modules'] ?? [],
    ));

    expect($moduleIds)->toContain('conditions');
});
