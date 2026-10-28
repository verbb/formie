<?php

use craft\elements\Entry;
use verbb\formie\elements\Form;
use verbb\formie\fields\Entries;
use verbb\formie\Formie;

it('selects an entry populated through consecutive Twig calls', function (string $shape, bool $force): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();
    $form = formie()->form()
        ->singleLineTextField('recipient')
        ->entriesField('account', ['displayType' => 'dropdown', 'sourceType' => 'elements', 'sourceElements' => [['id' => $entry->id]]])
        ->create();
    $form = Form::find()->id($form->id)->one();
    $value = match ($shape) {
        'integer' => $entry->id,
        'string' => (string)$entry->id,
        'array' => [$entry->id],
        'query' => Entry::find()->id($entry->id),
    };

    Craft::$app->getView()->renderString(<<<'TWIG'
        {% do craft.formie.populateFormValues(form, { recipient: 'deposit' }) %}
        {% do craft.formie.populateFormValues(form, { account: value }, force) %}
        TWIG, compact('form', 'value', 'force'));

    $document = new DOMDocument();
    @$document->loadHTML((string)Formie::$plugin->getRendering()->renderField($form, 'account'));
    $selected = (new DOMXPath($document))->query('//option[@selected]');

    expect($selected->length)->toBe(1)
        ->and($selected->item(0)->getAttribute('value'))->toBe((string)$entry->id)
        ->and($form->getFieldByHandle('recipient')->getInitialValue($form))->toBe('deposit')
        ->and($form->getFieldByHandle('account')->getInitialValue($form)->ids())->toBe([$entry->id]);
})->with(['integer', 'string', 'array', 'query'])->with([false, true]);

it('preserves existing entry answers unless population is forced', function (bool $force): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    $form = formie()->form()->entriesField('account')->create();
    $submission = formie()->submission($form)->with(['account' => []])->save();
    $form->setCurrentSubmission($submission);

    Formie::$plugin->getRendering()->populateFormValues($form, ['account' => (string)$entry->id], $force);

    expect($submission->getFieldValue('account')->ids())->toBe($force ? [$entry->id] : []);
})->with([false, true]);

it('keeps empty and invalid scalar element values empty', function (mixed $value): void {
    expect((new Entries())->normalizeValue($value, null)->ids())->toBe([]);
})->with([null, '', false, true, 'not-an-id', '1 OR 1=1', 1.5, 0, '0']);
