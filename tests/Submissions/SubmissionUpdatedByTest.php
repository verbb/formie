<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use Craft;
use craft\elements\User;
use verbb\formie\elements\Submission;

it('does not set updatedById when submissions are saved from the front-end', function (): void {
    $form = formie()
        ->form(['title' => 'Submission Updated By Front-end'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Front-end Save'])
        ->save();

    $reloaded = Submission::find()->id($submission->id)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded?->updatedById)->toBeNull();
});

it('sets updatedById when submissions are saved from the control panel', function (): void {
    $admin = User::find()->status(null)->admin(true)->one();
    expect($admin)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Submission Updated By CP'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Before CP Edit'])
        ->save();

    WebRequestTestHelper::withWebRequestContext(function () use ($admin, $submission): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($admin);

        $submission->setFieldValue('fullName', 'After CP Edit');
        expect(Craft::$app->elements->saveElement($submission))->toBeTrue();
    });

    $reloaded = Submission::find()->id($submission->id)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded?->updatedById)->toBe($admin->id)
        ->and($reloaded?->getUpdatedBy()?->id)->toBe($admin->id);
});

it('exposes last edited by as a submission index table attribute', function (): void {
    $attributes = Submission::tableAttributes();

    expect($attributes)->toHaveKey('updatedBy')
        ->and($attributes['updatedBy']['label'])->toBe(Craft::t('formie', 'Last Edited By'));
});

it('renders a user chip for the updatedBy submission table attribute', function (): void {
    $admin = User::find()->status(null)->admin(true)->one();
    expect($admin)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Submission Updated By Attribute HTML'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Attribute HTML'])
        ->save();

    WebRequestTestHelper::withWebRequestContext(function () use ($admin, $submission): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($admin);

        $submission->setFieldValue('fullName', 'Attribute HTML Updated');
        expect(Craft::$app->elements->saveElement($submission))->toBeTrue();
    });

    $reloaded = Submission::find()->id($submission->id)->one();
    expect($reloaded)->not->toBeNull();

    $html = $reloaded->getAttributeHtml('updatedBy');

    expect($html)->not->toBe('')
        ->and($html)->toContain((string)$admin->id);
});
