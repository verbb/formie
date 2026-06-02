<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

it('handles sequential saves from separate model loads', function (): void {
    $form = formie()
        ->form(['title' => 'Concurrent Source'])
        ->singleLineTextField('fullName')
        ->create();

    $copyA = Form::find()->id($form->id)->one();
    $copyB = Form::find()->id($form->id)->one();

    $copyA->title = 'Concurrent A';
    $copyB->title = 'Concurrent B';

    $savedA = \Craft::$app->elements->saveElement($copyA);
    $savedB = \Craft::$app->elements->saveElement($copyB);

    $final = Form::find()->id($form->id)->one();

    expect($savedA)->toBeTrue()
        ->and($savedB)->toBeTrue()
        ->and($final?->title)->toBe('Concurrent B');
});

it('supports large multipage form save as a performance smoke guard', function (): void {
    $factory = formie()
        ->form(['title' => 'Large Form Smoke'])
        ->multiPage(8);

    for ($page = 1; $page <= 8; $page++) {
        $factory->onPage($page);

        for ($field = 1; $field <= 5; $field++) {
            $factory->singleLineTextField("p{$page}f{$field}");
        }
    }

    $form = $factory->create();

    expect($form->hasMultiplePages())->toBeTrue()
        ->and(count($form->getPages()))->toBe(8)
        ->and(count($form->getFields()))->toBe(40);
});

it('survives multiple delete and restore cycles', function (): void {
    $form = formie()
        ->form(['title' => 'Restore Cycles'])
        ->singleLineTextField('fullName')
        ->create();

    for ($i = 0; $i < 2; $i++) {
        $deleted = \Craft::$app->elements->deleteElement($form);
        $trashed = Form::find()->id($form->id)->trashed(true)->one();
        $restored = $trashed ? \Craft::$app->elements->restoreElement($trashed) : false;

        expect($deleted)->toBeTrue()
            ->and($restored)->toBeTrue();
    }

    expect(Form::find()->id($form->id)->one())->not->toBeNull();
});
