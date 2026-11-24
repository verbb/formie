<?php

declare(strict_types=1);

use craft\helpers\Db;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Quiz;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\AddressAutoComplete;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;

function fieldLabelXssProbe(): string
{
    return 'Name<img src=x onerror=alert(1)><script>alert(2)</script> <a href="https://example.test/terms">terms</a> <em>now</em>';
}

function expectPurifiedFieldLabel(string $html): void
{
    expect($html)
        ->toContain('Name')
        ->toContain('<a href="https://example.test/terms">terms</a>')
        ->toContain('<em>now</em>')
        ->not->toContain('onerror')
        ->not->toContain('<script');
}

it('purifies field labels on the rendered form and keeps safe markup', function (): void {
    $form = formie()
        ->form(['title' => 'Field Label Purification'])
        ->singleLineTextField('labelProbe', ['label' => fieldLabelXssProbe()])
        ->singleLineTextField('plainLabel', ['label' => 'Terms & conditions'])
        ->create();

    expectPurifiedFieldLabel((string)Formie::$plugin->getRendering()->renderField($form, 'labelProbe'));

    expect((string)Formie::$plugin->getRendering()->renderField($form, 'plainLabel'))
        ->toContain('>Terms & conditions');
})->group('security');

it('purifies the label fallback for question fields without a question', function (): void {
    $field = new Quiz([
        'handle' => 'question',
        'label' => fieldLabelXssProbe(),
        'options' => [['label' => 'Yes', 'value' => 'yes'], ['label' => 'No', 'value' => 'no']],
    ]);
    $form = new Form(['title' => 'Question Label Purification', 'handle' => 'questionLabelPurification']);
    $form->setFormLayout(new FieldLayout([
        'pages' => [['rows' => [['fields' => [$field]]]]],
    ]));

    expectPurifiedFieldLabel((string)$field->getLabelHtml());
    expectPurifiedFieldLabel((string)Formie::$plugin->getRendering()->renderField($form, 'question'));

    $plain = new Quiz(['handle' => 'plainQuestion', 'label' => 'Terms & conditions']);

    expect((string)$plain->getLabelHtml())->toBe('Terms & conditions');
})->group('security');

it('purifies nested field labels in Group and Repeater summaries', function (): void {
    $child = ['type' => SingleLineText::class, 'handle' => 'city', 'label' => fieldLabelXssProbe()];
    $form = formie()->form()
        ->groupField('details', ['rows' => [['fields' => [$child]]]])
        ->repeaterField('offices', ['rows' => [['fields' => [$child]]]])
        ->create();
    $submission = formie()->submission($form)->with([
        'details' => ['city' => 'Geelong'],
        'offices' => [['city' => 'Ballarat']],
    ])->save();

    foreach (['details' => 'Geelong', 'offices' => 'Ballarat'] as $handle => $value) {
        $field = $form->getFieldByHandle($handle);
        $summary = (string)$field->getValueForSummary($submission->getFieldValue($handle), $submission);

        expectPurifiedFieldLabel($summary);
        expect($summary)->toContain($value);
    }
})->group('security');

it('purifies a disabled Address auto-complete label in summaries', function (): void {
    $rows = [
        ['fields' => [['type' => AddressAutoComplete::class, 'handle' => 'autoComplete', 'label' => fieldLabelXssProbe(), 'enabled' => false]]],
        ...(new Address())->getSubFields(),
    ];
    $created = formie()->form()->addressField('address', ['rows' => $rows])->create();
    $form = Form::find()->id($created->id)->one();
    $field = $form->getFieldByHandle('address');
    $submission = formie()->submission($form)->save();

    // Stored auto-complete text with no other parts, as Formie 2 saved it
    Db::update(Table::FORMIE_SUBMISSIONS, ['content' => [$field->uid => ['autocomplete' => '1 Legacy Way']]], ['id' => $submission->id]);
    $submission = Submission::find()->id($submission->id)->status(null)->isIncomplete(null)->one();
    $summary = (string)$field->getValueForSummary($submission->getFieldValue('address'), $submission);

    expectPurifiedFieldLabel($summary);
    expect($summary)->toContain('1 Legacy Way');
})->group('security');
