<?php

use verbb\formie\elements\Form;
use verbb\formie\Formie;

function singleLineInputTag(array $attributes): DOMElement
{
    $form = formie()->form()->singleLineTextField('answer', ['inputAttributes' => $attributes])->create();
    Formie::$plugin->getForms()->invalidateFormCaches();
    $form = Form::find()->id($form->id)->status(null)->one();
    $html = (string)Formie::$plugin->getRendering()->renderField($form, $form->getFieldByHandle('answer'));

    $dom = new DOMDocument();
    @$dom->loadHTML('<!doctype html><meta charset="utf-8">' . $html);
    $inputs = (new DOMXPath($dom))->query('//input[@data-formie-single-line-text-input]');
    expect($inputs->length)->toBe(1);

    return $inputs->item(0);
}

it('renders a type saved in the input attributes after reloading the form', function (string $type): void {
    expect(singleLineInputTag([['label' => 'type', 'value' => $type]])->getAttribute('type'))->toBe($type);
})->with(['password', 'tel', 'url', 'search']);

it('renders a text input without a saved type', function (array $attributes): void {
    expect(singleLineInputTag($attributes)->getAttribute('type'))->toBe('text');
})->with([
    'no attributes' => [[]],
    'other attributes' => [[['label' => 'readonly', 'value' => 'readonly']]],
    'blank type' => [[['label' => 'type', 'value' => '']]],
]);

it('keeps the rest of the core attributes ahead of the input attributes', function (): void {
    $input = singleLineInputTag([
        ['label' => 'type', 'value' => 'password'],
        ['label' => 'name', 'value' => 'overridden'],
        ['label' => 'id', 'value' => 'overridden'],
        ['label' => 'data-custom', 'value' => 'kept'],
    ]);

    expect($input->getAttribute('type'))->toBe('password')
        ->and($input->getAttribute('name'))->toBe('fields[answer]')
        ->and($input->getAttribute('id'))->not->toBe('overridden')
        ->and($input->getAttribute('data-custom'))->toBe('kept');
});
