<?php

declare(strict_types=1);

use verbb\formie\Formie;

function tableEmptyDateForm(): verbb\formie\elements\Form
{
    return formie()->form(['title' => 'Table Empty Date'])->tableField('items', [
        'columns' => [
            'col1' => ['heading' => 'When', 'handle' => 'when', 'type' => 'date'],
            'col2' => ['heading' => 'At', 'handle' => 'at', 'type' => 'time'],
            'col3' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline'],
        ],
    ])->create();
}

it('treats an unanswered date or time cell as empty', function(): void {
    $field = tableEmptyDateForm()->getFieldByHandle('items');
    $value = $field->normalizeValueFromRequest([['col1' => ['date' => ''], 'col2' => ['time' => ''], 'col3' => 'Apples']]);

    expect($value[0]['col1'])->toBeNull()
        ->and($value[0]['when'])->toBeNull()
        ->and($value[0]['col2'])->toBeNull()
        ->and($value[0]['col3'])->toBe('Apples');
});

it('still reads an answered date or time cell', function(): void {
    $field = tableEmptyDateForm()->getFieldByHandle('items');
    $value = $field->normalizeValueFromRequest([['col1' => ['date' => '2022-03-03'], 'col2' => ['time' => '03:02'], 'col3' => '']]);

    expect($value[0]['col1']->format('Y-m-d'))->toBe('2022-03-03')
        ->and($value[0]['col2']->format('H:i'))->toBe('03:02');
});

it('renders the form again with an unanswered date cell after a failed submission', function(): void {
    $form = tableEmptyDateForm();
    $field = $form->getFieldByHandle('items');
    $submission = new verbb\formie\elements\Submission();
    $submission->setForm($form);
    $submission->setFieldValue('items', $field->normalizeValueFromRequest([['col1' => ['date' => ''], 'col2' => ['time' => ''], 'col3' => 'Apples']]));
    $form->setCurrentSubmission($submission);

    $html = Tests\Support\WebRequestTestHelper::withWebRequestContext(fn(): string => (string)Formie::$plugin->getRendering()->renderForm($form));

    expect($html)->toContain('name="fields[items][0][col1][date]"');
});
