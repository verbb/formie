<?php

declare(strict_types=1);

use craft\base\Element;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Survey;
use verbb\formie\Formie;

it('submits original rank option values and preserves their chosen order', function (): void {
    $form = formie()->form()->addField(Survey::class, 'ranking', [
        'displayType' => Survey::DISPLAY_RANK,
        'options' => [
            ['label' => 'Performance', 'value' => 'Performance'],
            ['label' => 'C plus plus', 'value' => 'C++'],
            ['label' => 'C sharp', 'value' => 'C#'],
        ],
    ])->create();
    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'ranking');
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $values = [];

    foreach ($xpath->query('//input[@data-formie-rank-input]') as $input) {
        $values[] = $input->getAttribute('value');
    }

    expect($values)->toBe(['Performance', 'C++', 'C#']);
    $ranked = array_reverse($values);
    $submission = new Submission();
    $submission->setForm($form);
    $submission->setScenario(Element::SCENARIO_LIVE);
    $submission->title = 'Ranked choices';
    $submission->setFieldValueFromRequest('ranking', $ranked);

    expect(Craft::$app->getElements()->saveElement($submission))->toBeTrue();
    $loaded = Submission::find()->id($submission->id)->status(null)->one();
    expect($loaded->getFieldValue('ranking')->values())->toBe($ranked);
});
