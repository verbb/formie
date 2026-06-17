<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

it('persists appearance and behavior settings', function (): void {
    $form = formie()
        ->form(['title' => 'Settings Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'displayFormTitle' => true,
        'displayCurrentPageTitle' => true,
        'displayPageTabs' => true,
        'displayPageProgress' => true,
        'validationOnSubmit' => true,
        'validationOnFocus' => true,
        'submitMethod' => 'ajax',
        'submitActionMessagePosition' => 'bottom-form',
    ], false);

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->settings->displayFormTitle)->toBeTrue()
        ->and($reloaded?->settings->displayCurrentPageTitle)->toBeTrue()
        ->and($reloaded?->settings->displayPageTabs)->toBeTrue()
        ->and($reloaded?->settings->displayPageProgress)->toBeTrue()
        ->and($reloaded?->settings->validationOnFocus)->toBeTrue()
        ->and($reloaded?->settings->submitMethod)->toBe('ajax')
        ->and($reloaded?->settings->submitActionMessagePosition)->toBe('bottom-form');
});

it('persists lifecycle and retention-oriented settings', function (): void {
    $form = formie()
        ->form(['title' => 'Lifecycle Settings'])
        ->emailField('email')
        ->create();

    $form->settings->setAttributes([
        'scheduleForm' => true,
        'limitSubmissions' => true,
        'limitSubmissionsNumber' => 10,
        'limitSubmissionsType' => 'total',
        'dataRetention' => 'submission-age',
        'dataRetentionValue' => '30',
        'fileUploadsAction' => 'assets',
    ], false);

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->settings->scheduleForm)->toBeTrue()
        ->and($reloaded?->settings->limitSubmissions)->toBeTrue()
        ->and($reloaded?->settings->limitSubmissionsNumber)->toBe(10)
        ->and($reloaded?->settings->dataRetention)->toBe('submission-age')
        ->and($reloaded?->settings->dataRetentionValue)->toBe('30')
        ->and($reloaded?->settings->fileUploadsAction)->toBe('assets');
});

it('treats schedule datetimes as Craft app timezone wall-clock values', function (): void {
    $form = formie()
        ->form(['title' => 'Schedule Wall Clock'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'scheduleFormStart' => '2026-05-01 00:00:00',
        'scheduleFormEnd' => '2026-05-18 00:00:00',
    ], false);

    expect($form->settings->getFormBuilderConfig()['scheduleFormStart'])->toBe('2026-05-01 00:00:00')
        ->and($form->settings->getFormBuilderConfig()['scheduleFormEnd'])->toBe('2026-05-18 00:00:00');

    $form->settings->setAttributes([
        'scheduleFormStart' => '2026-06-02 00:00:00',
    ], false);

    expect($form->settings->getFormBuilderConfig()['scheduleFormStart'])->toBe('2026-06-02 00:00:00');
});

it('persists schedule datetimes without timezone drift', function (): void {
    $form = formie()
        ->form(['title' => 'Schedule Settings'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'scheduleForm' => true,
        'scheduleFormStart' => '2026-05-01 00:00:00',
        'scheduleFormEnd' => '2026-05-18 00:00:00',
    ], false);

    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded?->settings->getFormBuilderConfig()['scheduleFormStart'])->toBe('2026-05-01 00:00:00')
        ->and($reloaded?->settings->getFormBuilderConfig()['scheduleFormEnd'])->toBe('2026-05-18 00:00:00');
});
