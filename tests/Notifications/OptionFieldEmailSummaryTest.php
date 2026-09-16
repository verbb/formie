<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;

it('renders checkbox labels in all-fields email summary when options come from submission snapshot', function (): void {
    $form = formie()
        ->form(['title' => 'Dynamic Options Email ' . uniqid()])
        ->checkboxesField('artists', [
            'options' => [
                ['label' => 'Placeholder', 'value' => 'placeholder'],
            ],
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['artists' => ['placeholder']])
        ->save();

    $submission->snapshot = [
        'fields' => [
            'artists' => [
                'options' => [
                    ['label' => 'Artist Name', 'value' => '42-Artist Name', 'default' => false],
                ],
            ],
        ],
    ];

    // Model historical content whose original option is no longer offered.
    $submission->setFieldValue('artists', ['42-Artist Name']);
    expect(\Craft::$app->elements->saveElement($submission, false))->toBeTrue();

    $reloaded = Submission::find()->id($submission->id)->one();

    $html = References::parseContent('{allFields}', $reloaded, ['includeSummary' => true]);

    expect($html)->toContain('Artist Name')
        ->and($html)->not->toContain('42-Artist Name')
        ->and($html)->toContain('Artists');
});

it('falls back to option values in all-fields email summary when labels cannot be resolved', function (): void {
    $form = formie()
        ->form(['title' => 'Dynamic Options Fallback ' . uniqid()])
        ->checkboxesField('artists', [
            'options' => [
                ['label' => 'Placeholder', 'value' => 'placeholder'],
            ],
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['artists' => ['placeholder']])
        ->save();

    // Historical values can outlive the form options that originally allowed them.
    $submission->setFieldValue('artists', ['42-Artist Name']);
    expect(\Craft::$app->elements->saveElement($submission, false))->toBeTrue();

    $reloaded = Submission::find()->id($submission->id)->one();

    $html = References::parseContent('{allFields}', $reloaded, ['includeSummary' => true]);

    expect($html)->toContain('42-Artist Name');
});

it('falls back to option values in field summary projection when labels cannot be resolved', function (): void {
    $form = formie()
        ->form(['title' => 'Dynamic Options Summary ' . uniqid()])
        ->dropdownField('department', [
            'options' => [
                ['label' => 'Placeholder', 'value' => 'placeholder'],
            ],
        ])
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['department' => 'placeholder'])
        ->save();

    $submission->setFieldValue('department', 'support');
    expect(\Craft::$app->elements->saveElement($submission, false))->toBeTrue();

    $reloaded = Submission::find()->id($submission->id)->one();

    expect($reloaded->getFieldValueForSummary('department'))->toBe('support');
});
