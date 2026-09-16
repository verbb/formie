<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('allows the same submission to finish multiple pages while throttling another submission', function (): void {
    $settings = Formie::$plugin->getSettings();
    $original = $settings->getAttributes();
    try {
        $settings->enableIpSubmissionThrottling = true;
        $settings->ipSubmissionThrottleMinutes = 10;
        $settings->enableGlobalSubmissionThrottling = false;
        $settings->enableReplayProtection = false;
        $form = formie()->form()->multiPage(2)->onPage(1)->singleLineTextField('first')->onPage(2)->singleLineTextField('last')
            ->settings(['disableCaptchas' => true])->create();
        $pages = $form->getPages();
        $submission = new Submission(['ipAddress' => '192.0.2.217']);
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('first', 'First answer');
        $request = new SubmissionRequest(['form' => $form, 'submission' => $submission,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT, 'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
            'requestToken' => 'ip-continuation-' . uniqid(), 'pageId' => (int)$pages[0]->id]);
        $workflow = Formie::$plugin->getSubmissionWorkflow();
        $first = $workflow->processSubmissionRequest($request);
        expect($first->success)->toBeTrue();
        expect($first->submission->id)->not->toBeNull();
        expect($first->submission->isIncomplete)->toBeTrue();
        expect($first->submission->isSpam)->toBeFalse();
        $id = $first->submission->id;
        // Reload persisted page-one state before continuing, as separate requests do.
        $request->submission = Submission::find()->id($id)->status(null)->one();
        expect(Formie::$plugin->getSubmissionGuards()->validateRequest($request))->toBeNull();
        $request->submission->setFieldValueFromRequest('last', 'Last answer');
        $request->pageId = (int)$pages[1]->id;
        $final = $workflow->processSubmissionRequest($request);
        expect($final->success)->toBeTrue();
        expect($final->submission->id)->toBe($id);
        $saved = Submission::find()->id($id)->status(null)->one();
        expect($saved->isIncomplete)->toBeFalse();
        expect($saved->isSpam)->toBeFalse();
        expect($saved->getFieldValue('first'))->toBe('First answer');
        expect($saved->getFieldValue('last'))->toBe('Last answer');
        $other = new Submission(['title' => 'Another submission', 'ipAddress' => '192.0.2.217']);
        $other->setForm($form);
        $request->submission = $other;
        expect(Formie::$plugin->getSubmissionGuards()->validateRequest($request))->toContain('Too many submissions');
        expect(Craft::$app->getElements()->saveElement($other))->toBeTrue();
        // Excluding one's own ID must still count a different stored submission.
        expect(Formie::$plugin->getSubmissionGuards()->validateRequest($request))->toContain('Too many submissions');
    } finally { $settings->setAttributes($original, false); }
});
