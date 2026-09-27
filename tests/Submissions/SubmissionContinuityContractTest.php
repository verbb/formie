<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;
use verbb\formie\services\SubmissionGrants;
use craft\db\Query;

it('stores one content-free progress row and no portable grant for ordinary progress', function () {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () {
    [$form, $submission] = continuitySubmission();
    $service = Formie::$plugin->getSubmissionProgress();
    $progress = $service->upsertProgressState($form, $submission);
    expect($service->getProgressState($form)->id)->toBe($progress->id);
    $row = (new Query())->from(Table::FORMIE_SUBMISSION_PROGRESS)->where(['id' => $progress->id])->one();
    expect($row['content'])->toBeNull()
        ->and((new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['submissionId' => $submission->id])->andWhere(['not', ['tokenHash' => null]])->count())->toEqual(0);
    });
});

it('hashes purpose-bound grants and associates another browser without moving progress', function () {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () {
    [$form, $submission] = continuitySubmission();
    $progress = Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission);
    $grants = Formie::$plugin->getSubmissionGrants();
    $issued = $grants->issue($submission, SubmissionGrants::CONTINUE, $progress->id);
    $row = (new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['id' => $issued->id])->one();
    expect(json_encode($row))->not->toContain($issued->token)
        ->and($row['tokenHash'])->toBe('v1:' . hash('sha256', $issued->token))
        ->and($grants->verify($issued->token, SubmissionGrants::REVISE, $form))->toBeNull();
    $firstBrowser = Craft::$app->getSession()->get('formie:authority');
    Craft::$app->getSession()->set('formie:authority', 'second-browser');
    expect(Formie::$plugin->getSubmissionProgress()->getProgressState($form))->toBeNull();
    expect($grants->exchange($issued->token, SubmissionGrants::CONTINUE, $form)?->id)->toBe($issued->id)
        ->and(Formie::$plugin->getSubmissionProgress()->getProgressState($form)->id)->toBe($progress->id);
    Craft::$app->getSession()->set('formie:authority', $firstBrowser);
    expect(Formie::$plugin->getSubmissionProgress()->getProgressState($form)->id)->toBe($progress->id);
    $grants->revoke($issued->id);
    Craft::$app->getSession()->set('formie:authority', 'second-browser');
    expect(Formie::$plugin->getSubmissionProgress()->getProgressState($form))->toBeNull();
    });
});

it('rejects cross-form, expired and completed continue grants and cascades deletion', function () {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () {
    [$form, $submission] = continuitySubmission();
    $progress = Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission);
    $grants = Formie::$plugin->getSubmissionGrants();
    $issued = $grants->issue($submission, SubmissionGrants::CONTINUE, $progress->id);
    $other = formie()->form()->create();
    expect($grants->verify($issued->token, SubmissionGrants::CONTINUE, $other))->toBeNull();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_SUBMISSION_GRANTS, ['expiresAt' => time() - 1], ['id' => $issued->id])->execute();
    expect($grants->verify($issued->token, SubmissionGrants::CONTINUE, $form))->toBeNull();
    $issued = $grants->issue($submission, SubmissionGrants::CONTINUE, $progress->id);
    $submission->isIncomplete = false;
    Craft::$app->getElements()->saveElement($submission, false);
    expect($grants->verify($issued->token, SubmissionGrants::CONTINUE, $form))->toBeNull();
    Craft::$app->getElements()->deleteElement($submission, true);
    expect((new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['submissionId' => $submission->id])->exists())->toBeFalse()
        ->and((new Query())->from(Table::FORMIE_SUBMISSION_PROGRESS)->where(['submissionId' => $submission->id])->exists())->toBeFalse();
    });
});

it('rotates a portable grant and invalidates its exchanged children without removing canonical progress', function () {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () {
        [$form, $submission] = continuitySubmission();
        $progress = Formie::$plugin->getSubmissionProgress()->upsertProgressState($form, $submission);
        $grants = Formie::$plugin->getSubmissionGrants();
        $old = $grants->issue($submission, SubmissionGrants::CONTINUE, $progress->id);
        Craft::$app->getSession()->set('formie:authority', 'rotation-browser');
        $grants->exchange($old->token, SubmissionGrants::CONTINUE, $form);
        $next = $grants->rotate($old->id, $submission, SubmissionGrants::CONTINUE, $progress->id);
        expect($grants->verify($old->token, SubmissionGrants::CONTINUE, $form))->toBeNull()
            ->and($grants->bound($form, SubmissionGrants::CONTINUE))->toBeNull()
            ->and($grants->verify($next->token, SubmissionGrants::CONTINUE, $form)?->progressId)->toBe($progress->id);
        $otherSite = clone $form;
        $otherSite->siteId = (int)$form->siteId + 1000;
        expect($grants->exchange($next->token, SubmissionGrants::CONTINUE, $otherSite))->toBeNull();
        $grants->exchange($next->token, SubmissionGrants::CONTINUE, $form);
        expect(Formie::$plugin->getSubmissionProgress()->getProgressState($form)->id)->toBe($progress->id);
    });
});

it('issues continue authority without manufacturing progress', function () {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () {
        [$form, $submission] = continuitySubmission();
        $grants = Formie::$plugin->getSubmissionGrants();
        $issued = $grants->issue($submission, SubmissionGrants::CONTINUE);

        expect($issued->progressId)->toBeNull()
            ->and($grants->verify($issued->token, SubmissionGrants::CONTINUE, $form)?->submissionId)->toBe($submission->id)
            ->and((new Query())->from(Table::FORMIE_SUBMISSION_PROGRESS)->where(['submissionId' => $submission->id])->exists())->toBeFalse();
    });
});

it('removes browser-only authority with its progress', function () {
    \Tests\Support\WebRequestTestHelper::withWebRequestContext(function () {
        [$form] = continuitySubmission();
        $progress = Formie::$plugin->getSubmissionProgress()->upsertPageState($form);
        $grants = Formie::$plugin->getSubmissionGrants();

        expect($grants->bound($form, SubmissionGrants::CONTINUE)?->progressId)->toBe($progress->id);

        Formie::$plugin->getSubmissionProgress()->deleteProgress($progress->id);

        expect($grants->bound($form, SubmissionGrants::CONTINUE))->toBeNull()
            ->and((new Query())->from(Table::FORMIE_SUBMISSION_GRANTS)->where(['progressId' => $progress->id])->exists())->toBeFalse();
    });
});
