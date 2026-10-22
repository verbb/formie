<?php

declare(strict_types=1);

use verbb\formie\Formie;

it('renders checkbox option limits and messages on the field wrapper', function (array $renderOptions, bool $enabled, ?int $min, ?int $max): void {
    $form = formie()->form()->checkboxesField('topics', [
        'limitOptions' => $enabled,
        'min' => $min,
        'max' => $max,
        'validationMessages' => [
            'minOptions' => 'Please select at least {min}.',
            'maxOptions' => 'Please select no more than {max}.',
        ],
        'options' => [
            ['label' => 'One', 'value' => 'one'],
            ['label' => 'Two', 'value' => 'two'],
            ['label' => 'Three', 'value' => 'three'],
        ],
    ])->create();

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'topics', $renderOptions);
    $document = new DOMDocument();
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $wrappers = $xpath->query('//*[@data-formie-field-handle="topics"]');
    expect($wrappers->length)->toBe(1);
    $wrapper = $wrappers->item(0);

    foreach (['min' => $min, 'max' => $max] as $bound => $value) {
        $present = $enabled && (bool)$value;
        expect($wrapper->hasAttribute("data-formie-{$bound}-options"))->toBe($present)
            ->and($wrapper->hasAttribute("data-formie-validation-{$bound}-options-message"))->toBe($present);

        if ($present) {
            $message = $bound === 'min' ? "Please select at least {$value}." : "Please select no more than {$value}.";
            expect($wrapper->getAttribute("data-formie-{$bound}-options"))->toBe((string)$value)
                ->and($wrapper->getAttribute("data-formie-validation-{$bound}-options-message"))->toBe($message);
        }
    }

    expect($xpath->query('//input[@type="checkbox"]')->length)->toBe(3);

    if (isset($renderOptions['themeConfig'])) {
        expect($wrapper->getAttribute('class'))->toContain('custom-checkboxes');
    }
})->with([
    'default theme' => [[]],
    'no theme' => [['theme' => 'none']],
    'custom theme' => [['themeConfig' => ['field' => ['class' => 'custom-checkboxes']]]],
])->with([
    'both bounds' => [true, 1, 2],
    'maximum only' => [true, null, 2],
    'minimum only' => [true, 1, null],
    'disabled limits' => [false, 1, 2],
    'unset bounds' => [true, null, null],
    'zero bounds' => [true, 0, 0],
]);
