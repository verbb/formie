<?php

it('includes source number formatting when referencing another calculation', function (): void {
    $form = formie()->form()->calculationsField('subtotal', ['formatting' => 'number', 'prefix' => '$', 'suffix' => ' AUD'])
        ->calculationsField('total')->create();
    $form->getFieldByHandle('total')->formula = \verbb\formie\models\RichText::from('{field:' . $form->getFieldByHandle('subtotal')->reference . '} * 1.1');
    $variables = $form->getFieldByHandle('total')->getFormula()['variables'];

    expect(array_values($variables)[0])->toMatchArray([
        'sourceKey' => 'subtotal',
        'numberFormat' => ['prefix' => '$', 'suffix' => ' AUD'],
    ]);
});
