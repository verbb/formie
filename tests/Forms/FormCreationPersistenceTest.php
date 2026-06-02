<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;

function creationTestHandle(): string
{
    static $counter = 4000;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}

it('creates and persists a minimal form and can fetch it by identifiers', function (): void {
    $form = formie()
        ->form(['title' => 'Creation Contract'])
        ->singleLineTextField('fullName')
        ->create();

    $forms = Formie::$plugin->getForms();

    expect($forms->getFormById((int)$form->id))->not->toBeNull()
        ->and($forms->getFormByHandle((string)$form->handle))->not->toBeNull()
        ->and($forms->getFormByUid((string)$form->uid))->not->toBeNull()
        ->and($forms->getFormByLayoutId((int)$form->layoutId))->not->toBeNull();
});

it('updates and persists form core attributes and settings', function (): void {
    $form = formie()
        ->form(['title' => 'Before Update'])
        ->singleLineTextField('fullName')
        ->create();

    $form->title = 'After Update';
    $form->settings->setAttributes([
        'displayFormTitle' => true,
        'submitMethod' => 'ajax',
    ], false);

    $saved = Craft::$app->elements->saveElement($form);

    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded)->not->toBeNull()
        ->and($reloaded->title)->toBe('After Update')
        ->and($reloaded->settings->displayFormTitle)->toBeTrue()
        ->and($reloaded->settings->submitMethod)->toBe('ajax');
});

it('fails validation when trying to save a duplicate handle', function (): void {
    $handle = creationTestHandle();

    $first = formie()
        ->form(['title' => 'First', 'handle' => $handle])
        ->singleLineTextField('firstName')
        ->create();

    $second = new Form([
        'title' => 'Second',
        'handle' => $handle,
    ]);

    $saved = Craft::$app->elements->saveElement($second);

    expect($first->id)->not->toBeNull()
        ->and($saved)->toBeFalse()
        ->and($second->getErrors())->not->toBeEmpty();
});
