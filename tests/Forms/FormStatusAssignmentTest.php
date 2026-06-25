<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;

it('persists and hydrates formStatusId on forms', function (): void {
    $formStatus = Formie::$plugin->getFormStatuses()->getStatusByHandle('draft');

    expect($formStatus)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Form Status Assignment'])
        ->singleLineTextField('fullName')
        ->create();

    $form->formStatusId = (int)$formStatus->id;

    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->formStatusId)->toBe((int)$formStatus->id)
        ->and($reloaded->getFormStatusModel()?->handle)->toBe('draft');
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('assigns the default form status to new forms', function (): void {
    $default = Formie::$plugin->getFormStatuses()->getDefaultStatus();

    expect($default)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Default Form Status'])
        ->singleLineTextField('fullName')
        ->create();

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded?->formStatusId)->toBe((int)$default->id);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('returns a default form status handle when formStatusId is missing', function (): void {
    $default = Formie::$plugin->getFormStatuses()->getDefaultStatus();

    expect($default)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Missing Form Status'])
        ->singleLineTextField('fullName')
        ->create();

    $form->formStatusId = null;
    Craft::$app->db->createCommand()
        ->update('{{%formie_forms}}', ['formStatusId' => null], ['id' => $form->id])
        ->execute();

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded)->not->toBeNull()
        ->and($reloaded->getStatus())->toBe($default->handle)
        ->and($reloaded->getFormStatusModel()?->handle)->toBe($default->handle);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');

it('falls back to a configured form status when formStatusId is invalid', function (): void {
    $default = Formie::$plugin->getFormStatuses()->getDefaultStatus();

    expect($default)->not->toBeNull();

    $form = formie()
        ->form(['title' => 'Invalid Form Status'])
        ->singleLineTextField('fullName')
        ->create();

    $form->formStatusId = 999999;

    expect($form->getStatus())->toBe($default->handle)
        ->and($form->getFormStatusModel()?->id)->toBe((int)$default->id);
})->skip(fn (): bool => !class_exists(\Craft::class) || !\Craft::$app, 'Requires Craft bootstrap');
