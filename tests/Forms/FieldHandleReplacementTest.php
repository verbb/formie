<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Number;
use verbb\formie\fields\SingleLineText;

function fieldHandleReplacementFormHandle(): string
{
    static $counter = 6000;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}

it('allows replacing a deleted field with a new field using the same handle in one save', function (): void {
    $form = formie()
        ->form(['title' => 'Replace Field', 'handle' => fieldHandleReplacementFormHandle()])
        ->numberField('quantity', ['label' => 'Quantity'])
        ->create();

    $layout = $form->getFormLayout();
    $pages = $layout->getPages();
    $rows = $pages[0]->getRows();

    $rows[0]->setFields([
        [
            'type' => SingleLineText::class,
            'handle' => 'quantity',
            'label' => 'Quantity',
        ],
    ]);

    $layout->setPages($pages);
    $form->setFormLayout($layout);

    $saved = Craft::$app->elements->saveElement($form);

    expect($saved)->toBeTrue();

    $reloaded = Form::find()->id($form->id)->one();
    $replacementField = $reloaded?->getFieldByHandle('quantity');

    expect($replacementField)->not->toBeNull()
        ->and($replacementField)->toBeInstanceOf(SingleLineText::class)
        ->and($replacementField)->not->toBeInstanceOf(Number::class)
        ->and($reloaded?->getFields())->toHaveCount(1);
});
