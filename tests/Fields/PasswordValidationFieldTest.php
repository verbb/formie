<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\models\ClientModule;
use verbb\formie\theme\context\RenderContext;

it('enforces password validation rules', function (): void {
    $form = formie()
        ->form(['title' => 'Password Validation'])
        ->passwordField('password', [
            'required' => true,
            'passwordMinLength' => 8,
            'passwordRequireUppercase' => true,
            'passwordRequireLowercase' => true,
            'passwordRequireSpecialCharacter' => true,
        ])
        ->create();

    $invalid = formie()
        ->submission($form)
        ->with(['password' => 'short'])
        ->allowValidationFailure()
        ->save();

    expect($invalid)->toHaveFieldError('password');

    $valid = formie()
        ->submission($form)
        ->with(['password' => 'ValidPass1!'])
        ->save();

    expect($valid->id)->not->toBeNull();
});

it('skips password validation when the field is left empty on a saved submission', function (): void {
    $form = formie()
        ->form(['title' => 'Password Validation Optional'])
        ->passwordField('password', [
            'passwordMinLength' => 8,
        ])
        ->create();

    $saved = formie()
        ->submission($form)
        ->with(['password' => 'SavedPass1'])
        ->save();

    expect($saved->id)->not->toBeNull();

    $resubmitted = \verbb\formie\elements\Submission::find()->id($saved->id)->one();
    expect($resubmitted)->not->toBeNull();

    $resubmitted->setFieldValue('password', '');
    expect(\Craft::$app->elements->saveElement($resubmitted))->toBeTrue();
});

it('renders password validation client attributes and registers the client module', function (): void {
    $form = formie()
        ->form(['title' => 'Password Validation Client'])
        ->passwordField('password', [
            'passwordMinLength' => 6,
            'passwordRequireUppercase' => true,
        ])
        ->create();

    $field = $form->getFieldByHandle('password');
    $page = $form->getPages()[0];
    expect($field)->not->toBeNull();

    $tag = $field->renderSlotTag('fieldInput', RenderContext::from([
        'form' => $form,
        'field' => $field,
        'page' => $page,
        'currentPage' => $page,
        'value' => '',
    ]));

    expect($tag?->attributes['data-formie-password-min-length'] ?? null)->toBe(6)
        ->and($tag?->attributes['data-formie-password-require-uppercase'] ?? null)->toBeTrue();

    $moduleIds = array_values(array_map(
        static fn(array $module): string => (string)$module['id'],
        Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND),
    ));

    expect($moduleIds)->toContain('password-validation');
});

it('does not register password validation modules when disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Password Validation Disabled'])
        ->passwordField('password')
        ->create();

    $moduleIds = array_values(array_map(
        static fn(array $module): string => (string)$module['id'],
        Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND),
    ));

    expect($moduleIds)->not->toContain('password-validation');
});

it('still enforces match field validation for password fields', function (): void {
    $form = formie()
        ->form(['title' => 'Password Match'])
        ->passwordField('password', ['required' => true])
        ->passwordField('confirmPassword', ['required' => true])
        ->create();

    $passwordField = $form->getFieldByHandle('password');
    $confirmField = $form->getFieldByHandle('confirmPassword');
    expect($passwordField?->reference)->not->toBeNull();

    $confirmField->matchField = References::field((string)$passwordField->reference);
    expect(\Craft::$app->elements->saveElement($form))->toBeTrue();

    $form = \verbb\formie\elements\Form::find()->id($form->id)->one();
    expect($form)->not->toBeNull();

    $invalid = formie()
        ->submission($form)
        ->with([
            'password' => 'secret-one',
            'confirmPassword' => 'secret-two',
        ])
        ->allowValidationFailure()
        ->save();

    expect($invalid)->toHaveFieldError('confirmPassword');
});
