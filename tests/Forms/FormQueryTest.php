<?php

declare(strict_types=1);

use verbb\formie\Formie;

it('queries forms by id handle uid and layout id through the forms service', function (): void {
    $form = formie()
        ->form(['title' => 'Query Service'])
        ->singleLineTextField('fullName')
        ->create();

    $forms = Formie::$plugin->getForms();

    expect($forms->getFormById((int)$form->id)?->id)->toBe($form->id)
        ->and($forms->getFormByHandle((string)$form->handle)?->id)->toBe($form->id)
        ->and($forms->getFormByUid((string)$form->uid)?->id)->toBe($form->id)
        ->and($forms->getFormByLayoutId((int)$form->layoutId))->not->toBeNull();
});

it('includes newly created forms in all forms query', function (): void {
    $form = formie()
        ->form(['title' => 'All Forms Query'])
        ->singleLineTextField('value')
        ->create();

    $allForms = Formie::$plugin->getForms()->getAllForms();
    $ids = array_map(static fn($item) => (int)$item->id, $allForms);

    expect(in_array((int)$form->id, $ids, true))->toBeTrue();
});

it('keeps form query data updated after an edit', function (): void {
    $form = formie()
        ->form(['title' => 'Before Query Edit'])
        ->singleLineTextField('value')
        ->create();

    $form->title = 'After Query Edit';
    Craft::$app->elements->saveElement($form);

    $queried = Formie::$plugin->getForms()->getFormById((int)$form->id);

    expect($queried?->title)->toBe('After Query Edit');
});
