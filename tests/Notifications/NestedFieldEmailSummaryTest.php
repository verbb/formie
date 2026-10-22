<?php

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Group;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\References;
use verbb\formie\references\ReferenceOutputContext;

it('respects nested email summary opt-outs after saving and reloading', function (string $container, string $token): void {
    $form = formie()->form()->{$container}('details', [
        'rows' => [['fields' => [
            ['type' => SingleLineText::class, 'handle' => 'publicAnswer', 'label' => 'Public answer'],
            ['type' => SingleLineText::class, 'handle' => 'privateAnswer', 'label' => 'Private answer', 'includeInEmailFieldSummaries' => false],
            [
                'type' => Group::class,
                'handle' => 'nested',
                'label' => 'Nested group',
                'rows' => [['fields' => [
                    ['type' => SingleLineText::class, 'handle' => 'included', 'label' => 'Nested included'],
                    ['type' => SingleLineText::class, 'handle' => 'excluded', 'label' => 'Nested excluded', 'includeInEmailFieldSummaries' => false],
                ]]],
            ],
        ]]],
    ])->create();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $form = Form::find()->id($form->id)->one();
    expect($form->getFieldByHandle('details')->getFieldByHandle('privateAnswer')->includeInEmailFieldSummaries)->toBeFalse();

    $row = [
        'publicAnswer' => 'Visible response',
        'privateAnswer' => 'Excluded response',
        'nested' => ['included' => 'Nested visible response', 'excluded' => 'Nested excluded response'],
    ];
    $value = $container === 'repeaterField' ? [$row, array_replace($row, ['publicAnswer' => 'Second visible response', 'privateAnswer' => 'Second excluded response'])] : $row;
    $saved = formie()->submission($form)->with(['details' => $value])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $html = References::parseContent($token, $submission, ['outputContext' => ReferenceOutputContext::Html]);

    expect($html)->toContain('Public answer', 'Visible response', 'Nested included', 'Nested visible response');

    foreach (['Private answer', 'Excluded response', 'Nested excluded', 'Second excluded response'] as $excluded) {
        expect($html)->not->toContain($excluded);
    }

    if ($container === 'repeaterField') {
        expect($html)->toContain('Second visible response');
    }

    // Opting out of aggregate summaries must not remove stored submission data.
    $stored = $submission->getFieldValue('details');
    $storedRow = $container === 'repeaterField' ? $stored[0] : $stored;
    expect($storedRow['privateAnswer'])->toBe('Excluded response');

    if ($container === 'groupField') {
        // An explicitly selected child reference remains available in notification content.
        $child = $form->getFieldByHandle('details')->getFieldByHandle('privateAnswer');
        expect(References::parseContent(References::field($child->reference), $submission, ['outputContext' => ReferenceOutputContext::Html]))->toContain('Excluded response');
    }
})->with(['groupField', 'repeaterField'])->with(['{allFields}', '{allContentFields}', '{allVisibleFields}'])->group('notifications');
