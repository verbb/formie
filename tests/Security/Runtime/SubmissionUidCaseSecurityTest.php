<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\errors\SubmissionUnavailableException;
use verbb\formie\models\HtmlSubmissionRequest;
use yii\web\ForbiddenHttpException;

function submissionUidCaseRevise(Form $form, string $uid, ?string $token, int $version): mixed
{
    return WebRequestTestHelper::withWebRequestContext(fn() => runHtmlSubmission(new HtmlSubmissionRequest([
        'handle' => $form->handle,
        'operation' => SubmissionOperation::REVISE,
        'submissionUid' => $uid,
        'expectedVersion' => $version,
        'submissionEditToken' => $token,
        'submitAction' => 'submit',
        'fieldParamNamespace' => 'fields',
    ])), ['method' => 'POST', 'bodyParams' => ['fields' => ['name' => 'Changed']]]);
}

function submissionUidCaseValue(Submission $submission): string
{
    return (string)Submission::find()->id($submission->id)->isIncomplete(null)->isSpam(null)->one()->getFieldValue('name');
}

function submissionUidCaseFixture(): array
{
    $form = formie()->form()->settings(['disableCaptchas' => true])->singleLineTextField('name')->create();

    return [$form, formie()->submission($form)->with(['name' => 'Original'])->save()];
}

function submissionUidCaseToken(Form $form, Submission $submission): string
{
    return WebRequestTestHelper::withWebRequestContext(function () use ($form, $submission): string {
        $form->setSubmission($submission);

        return $form->getSubmissionEditToken();
    });
}

it('revises the same submission from an uppercase UID with a genuine edit grant', function (): void {
    [$form, $submission] = submissionUidCaseFixture();
    $uid = strtoupper($submission->uid);
    expect($uid)->not->toBe($submission->uid);

    $result = submissionUidCaseRevise($form, $uid, submissionUidCaseToken($form, $submission), (int)$submission->stateVersion);

    expect($result->response->success)->toBeTrue()
        ->and((int)$result->response->submission->id)->toBe((int)$submission->id)
        ->and(submissionUidCaseValue($submission))->toBe('Changed');
})->group('security');

it('rejects an uppercase UID without an edit grant', function (): void {
    [$form, $submission] = submissionUidCaseFixture();

    expect(fn() => submissionUidCaseRevise($form, strtoupper($submission->uid), null, (int)$submission->stateVersion))
        ->toThrow(ForbiddenHttpException::class);
    expect(submissionUidCaseValue($submission))->toBe('Original');
})->group('security');

it('rejects an uppercase UID with an edit grant for another submission', function (): void {
    [$form, $submission] = submissionUidCaseFixture();
    $other = formie()->submission($form)->with(['name' => 'Other'])->save();

    expect(fn() => submissionUidCaseRevise($form, strtoupper($submission->uid), submissionUidCaseToken($form, $other), (int)$submission->stateVersion))
        ->toThrow(ForbiddenHttpException::class);
    expect(submissionUidCaseValue($submission))->toBe('Original')
        ->and(submissionUidCaseValue($other))->toBe('Other');
})->group('security');

it('does not reach a submission on another form from an uppercase UID', function (): void {
    [$form, $submission] = submissionUidCaseFixture();
    [$otherForm] = submissionUidCaseFixture();

    expect(fn() => submissionUidCaseRevise($otherForm, strtoupper($submission->uid), submissionUidCaseToken($form, $submission), (int)$submission->stateVersion))
        ->toThrow(SubmissionUnavailableException::class);
    expect(submissionUidCaseValue($submission))->toBe('Original');
})->group('security');

it('keeps rejecting mixed case search expressions in place of a UID', function (string $uid): void {
    [$form, $submission] = submissionUidCaseFixture();

    expect(fn() => submissionUidCaseRevise($form, $uid, submissionUidCaseToken($form, $submission), (int)$submission->stateVersion))
        ->toThrow(SubmissionUnavailableException::class);
    expect(submissionUidCaseValue($submission))->toBe('Original');
})->with(['wildcard' => 'A*', 'not operator' => 'NOT 00000000-0000-0000-0000-000000000000'])->group('security');
