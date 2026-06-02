<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use Craft;
use verbb\formie\Formie;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;
use verbb\formie\controllers\SubmissionsController;

it('does not reflect unknown form handles from legacy anonymous page flows', function (): void {
    $missingHandle = 'security-missing-handle-' . uniqid();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($missingHandle): void {
        $request->setQueryParams([
            'handle' => $missingHandle,
            'pageId' => 1,
        ]);

        $controller = new SubmissionsController('formie-submissions-security', Craft::$app);

        expect(fn() => $controller->actionSetPage())
            ->toThrow(BadRequestHttpException::class, 'Form not found');
    }, [
        'method' => 'POST',
    ]);
})->group('security');

it('requires POST for legacy anonymous page flows', function (): void {
    $form = formie()
        ->form(['title' => 'Legacy Submission Security Method'])
        ->singleLineTextField('fullName')
        ->create();
    $pageId = (int)($form->getCurrentPage()?->id ?? 0);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $pageId): void {
        $request->setQueryParams([
            'handle' => (string)$form->handle,
            'pageId' => $pageId,
        ]);

        $controller = new SubmissionsController('formie-submissions-security', Craft::$app);

        expect(fn() => $controller->actionSetPage())
            ->toThrow(MethodNotAllowedHttpException::class);
    });
})->group('security');

it('does not bind legacy anonymous page flows to raw submission uids', function (): void {
    $formA = formie()
        ->form(['title' => 'Legacy Submission Security A'])
        ->singleLineTextField('fullName')
        ->create();
    $formB = formie()
        ->form(['title' => 'Legacy Submission Security B'])
        ->singleLineTextField('fullName')
        ->create();

    $pageId = (int)($formA->getCurrentPage()?->id ?? 0);
    $submissionB = formie()
        ->submission($formB)
        ->with(['fullName' => 'Wrong Form'])
        ->save();
    $submissionB->isIncomplete = true;

    expect(Craft::$app->getElements()->saveElement($submissionB))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($formA, $pageId, $submissionB): void {
        $request->setBodyParams([
            'handle' => (string)$formA->handle,
            'pageId' => $pageId,
            'submissionUid' => (string)$submissionB->uid,
        ]);

        $controller = new SubmissionsController('formie-submissions-security', Craft::$app);
        $response = $controller->actionSetPage();
        $progressState = Formie::$plugin->getSubmissionDrafts()->getProgressState($formA);

        expect($response->data['success'] ?? null)->toBeTrue()
            ->and($progressState?->submissionId)->toBeNull();
    }, [
        'method' => 'POST',
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);
})->group('security');
