<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\Quiz;
use verbb\formie\fields\Survey;
use verbb\formie\Formie;
use verbb\formie\models\FieldLayout;

it('renders questionnaire choice layouts required by their browser modules', function (string $class, string $setting, string $displayType): void {
    $field = new $class([
        'handle' => 'question',
        'label' => 'Workshop question',
        $setting => $displayType,
        'options' => [['label' => 'Yes', 'value' => 'yes'], ['label' => 'No', 'value' => 'no']],
    ]);
    $form = new Form(['title' => 'Questionnaire presentation', 'handle' => 'questionnairePresentation']);
    $form->setFormLayout(new FieldLayout([
        'pages' => [['rows' => [['fields' => [$field]]]]],
    ]));

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'question');
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $layout = $xpath->query('//fieldset[@data-formie-' . $displayType . '-field-layout]')->item(0);

    expect($layout)->not->toBeNull()
        ->and($layout->getElementsByTagName('legend')->length)->toBe(1)
        ->and($layout->getElementsByTagName('input')->length)->toBeGreaterThanOrEqual(2);
})->with([
    'radio quiz' => [Quiz::class, 'fieldType', 'radio'],
    'checkbox quiz' => [Quiz::class, 'fieldType', 'checkboxes'],
    'radio survey' => [Survey::class, 'displayType', 'radio'],
    'checkbox survey' => [Survey::class, 'displayType', 'checkboxes'],
]);
