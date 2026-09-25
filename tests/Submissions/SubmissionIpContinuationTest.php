<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\SubmissionCommand;
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
        $request = submissionCommand(['form' => $form, 'submission' => $submission,
            'operation' => \verbb\formie\enums\SubmissionOperation::SUBMIT, 'navigation' => \verbb\formie\enums\NavigationIntent::ADVANCE,
            'operationId' => 'ip-continuation-' . uniqid(), 'pageId' => (int)$pages[0]->id]);
        $workflow = Formie::$plugin->getSubmissionWorkflow();
        $first = runSubmissionCommand($request);
        expect($first->success)->toBeTrue();
        expect($first->submission->id)->not->toBeNull();
        expect($first->submission->isIncomplete)->toBeTrue();
        expect($first->submission->isSpam)->toBeFalse();
        $id = $first->submission->id;
        // Reload persisted page-one state before continuing, as separate requests do.
        $continued = Submission::find()->id($id)->status(null)->isIncomplete(null)->one();
        $continued->setFieldValueFromRequest('last', 'Last answer');
        $request = submissionCommand(['form' => $form, 'submission' => $continued, 'pageId' => (int)$pages[1]->id]);
        $final = runSubmissionCommand($request);
        expect($final->success)->toBeTrue();
        expect($final->submission->id)->toBe($id);
        $saved = Submission::find()->id($id)->status(null)->one();
        expect($saved->isIncomplete)->toBeFalse();
        expect($saved->isSpam)->toBeFalse();
        expect($saved->getFieldValue('first'))->toBe('First answer');
        expect($saved->getFieldValue('last'))->toBe('Last answer');
        $other = new Submission(['title' => 'Another submission', 'ipAddress' => '192.0.2.217']);
        $other->setForm($form);
        \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () use ($form, $other) {
            $request = guardCommand($form, ['submission' => $other]);
            expect(fn() => Formie::$plugin->getSubmissionGuards()->validateRequest($request, false))->toThrow(\yii\web\TooManyRequestsHttpException::class);
        }, ['method' => 'POST']);
    } finally { $settings->setAttributes($original, false); }
});
