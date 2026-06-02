<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\Variables;
use verbb\formie\helpers\References;

it('keeps registry-driven field variable sources aligned with registered fields', function (): void {
    $registered = Formie::$plugin->getFields()->getRegisteredFields();

    expect($registered)->not->toBeEmpty();

    foreach ($registered as $class => $field) {
        $sources = array_map(static function($source) {
            return $source->toArray();
        }, $field->variableSources());

        expect($sources)->toBeArray();

        foreach ($sources as $source) {
            expect($source)->toHaveKey('selector');
            expect($source)->toHaveKey('content');
            expect($source)->toHaveKey('types');
        }
    }
});

it('executes transform parsing for every registered transformer definition', function (): void {
    $registry = Variables::getCategoryConfig()['transformerRegistry'] ?? [];

    $form = formie()
        ->form(['title' => 'Variables Matrix'])
        ->singleLineTextField('fullName')
        ->numberField('score')
        ->agreeField('terms')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Value Matrix',
        'score' => '42.5',
        'terms' => true,
    ])->save();

    $tokenByType = [
        Variables::TYPE_TEXT => '{field:fullName}',
        Variables::TYPE_NUMBER => '{field:score}',
        Variables::TYPE_DATE => '{submission:date}',
        'boolean' => '{field:terms}',
    ];

    foreach ($registry as $valueType => $definitions) {
        $token = $tokenByType[$valueType] ?? '{formName}';

        foreach ($definitions as $definition) {
            $id = $definition['id'] ?? '';
            if (!$id) {
                continue;
            }

            $params = [];
            foreach (($definition['params'] ?? []) as $param) {
                $name = $param['name'] ?? '';
                if (!$name) {
                    continue;
                }

                if (array_key_exists('default', $param)) {
                    $params[$name] = (string)$param['default'];
                    continue;
                }

                if (($param['type'] ?? '') === 'number') {
                    $params[$name] = '2';
                } else {
                    $params[$name] = 'x';
                }
            }

            $parts = [];
            foreach ($params as $name => $value) {
                $parts[] = "{$name}={$value}";
            }

            $suffix = $parts ? ';' . implode(';', $parts) : '';
            $template = rtrim($token, '}') . ";transform={$id}{$suffix}}";

            $parsed = References::parseContent($template, $submission, ['includeSummary' => true]);

            expect($parsed)->not->toBeNull()
                ->and($parsed)->not->toContain('{');
        }
    }
});

it('parses transform metadata on body-less variables', function (): void {
    $form = formie()
        ->form(['title' => 'Body-less Variable Transforms'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with([
        'fullName' => 'Shown',
    ])->save();

    $timestamp = References::parseContent('{timestamp;transform=format;preset=isoDate}', $submission, ['includeSummary' => true]);

    expect($timestamp)->toMatch('/^\d{4}-\d{1,2}-\d{1,2}$/');
});

it('marks summary variables as aggregate content that cannot use regular transforms', function (): void {
    $groups = Variables::getCategoryConfig()['staticGroups'][Variables::GROUP_FORM] ?? [];
    $summaryTokens = ['{allFields}', '{allContentFields}', '{allVisibleFields}'];
    $summarySources = array_filter($groups, static function(array $source) use ($summaryTokens): bool {
        return in_array($source['value'] ?? '', $summaryTokens, true);
    });

    expect($summarySources)->toHaveCount(3);

    foreach ($summarySources as $source) {
        expect($source['content'] ?? null)->toBe(Variables::CONTENT_ANY)
            ->and($source['types'] ?? null)->toBe([])
            ->and($source['allowTransforms'] ?? null)->toBeFalse();
    }
});

it('uses email field summary settings for all-fields style variables and normalizes legacy config keys', function (): void {
    $form = formie()
        ->form(['title' => 'Email Field Summary Settings'])
        ->singleLineTextField('publicName', [
            'label' => 'Public Name',
        ])
        ->singleLineTextField('emptyResponse', [
            'label' => 'Empty Response',
        ])
        ->singleLineTextField('hiddenFromSummary', [
            'label' => 'Hidden From Summary',
            'includeInEmailFieldSummaries' => false,
        ])
        ->singleLineTextField('legacyHiddenFromSummary', [
            'label' => 'Legacy Hidden From Summary',
            'includeInEmail' => false,
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'publicName' => 'Shown',
        'emptyResponse' => '',
        'hiddenFromSummary' => 'Filtered',
        'legacyHiddenFromSummary' => 'Legacy Filtered',
    ])->save();

    $hiddenField = $form->getFieldByHandle('hiddenFromSummary');
    $legacyField = $form->getFieldByHandle('legacyHiddenFromSummary');
    $allFields = References::parseContent('{allFields}', $submission, ['includeSummary' => true]);
    $transformedAllFields = References::parseContent('{allFields;transform=lower}', $submission, ['includeSummary' => true]);
    $allContentFields = References::parseContent('{allContentFields}', $submission, ['includeSummary' => true]);
    $allVisibleFields = References::parseContent('{allVisibleFields}', $submission, ['includeSummary' => true]);

    expect($hiddenField?->includeInEmailFieldSummaries)->toBeFalse()
        ->and($legacyField?->includeInEmailFieldSummaries)->toBeFalse()
        ->and($transformedAllFields)->toBe($allFields)
        ->and($allFields)->toContain('<strong>Public Name</strong>')
        ->and($allFields)->toContain('Shown')
        ->and($allFields)->toContain('<strong>Empty Response</strong>')
        ->and($allFields)->toContain('No response.')
        ->and($allFields)->not->toContain('Hidden From Summary')
        ->and($allFields)->not->toContain('Legacy Hidden From Summary')
        ->and($allContentFields)->toContain('<strong>Public Name</strong>')
        ->and($allContentFields)->toContain('Shown')
        ->and($allContentFields)->not->toContain('Empty Response')
        ->and($allContentFields)->not->toContain('Hidden From Summary')
        ->and($allContentFields)->not->toContain('Legacy Hidden From Summary')
        ->and($allVisibleFields)->toContain('<strong>Public Name</strong>')
        ->and($allVisibleFields)->toContain('Shown')
        ->and($allVisibleFields)->not->toContain('Hidden From Summary')
        ->and($allVisibleFields)->not->toContain('Legacy Hidden From Summary');
});

it('omits conditionally hidden fields from all-fields style variables', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'Email Field Summary Conditions',
    ]);

    $submission = formie()->submission($form)->with([
        'enquiryType' => 'support',
        'otherReason' => 'Should Be Hidden',
    ])->save();

    $allFields = References::parseContent('{allFields}', $submission, ['includeSummary' => true]);
    $allContentFields = References::parseContent('{allContentFields}', $submission, ['includeSummary' => true]);
    $allVisibleFields = References::parseContent('{allVisibleFields}', $submission, ['includeSummary' => true]);

    expect($form->getFieldByHandle('otherReason')?->isConditionallyHidden($submission))->toBeTrue()
        ->and($allFields)->toContain('Enquiry Type')
        ->and($allFields)->not->toContain('Other Reason')
        ->and($allFields)->not->toContain('Should Be Hidden')
        ->and($allContentFields)->not->toContain('Other Reason')
        ->and($allContentFields)->not->toContain('Should Be Hidden')
        ->and($allVisibleFields)->not->toContain('Other Reason')
        ->and($allVisibleFields)->not->toContain('Should Be Hidden');
});

it('includes conditionally visible summary fields when server conditions use client field references', function (): void {
    $form = formie()->conditionForms()->optionsValueVisibility([
        'title' => 'Email Field Summary Client Conditions',
    ]);

    $sourceField = $form->getFieldByHandle('enquiryType');
    $conditionalField = $form->getFieldByHandle('otherReason');

    expect($sourceField?->uid)->not->toBeEmpty()
        ->and($conditionalField)->not->toBeNull();

    $conditionalField->enableConditions = true;
    $conditionalField->conditions = [
        'showRule' => 'show',
        'conditionRule' => 'all',
        'conditions' => [[
            'field' => (string)$sourceField->uid,
            'condition' => '=',
            'value' => 'other',
        ]],
    ];

    $submission = formie()->submission($form)->with([
        'enquiryType' => 'other',
        'otherReason' => 'Visible In Email',
    ])->save();

    $allFields = References::parseContent('{allFields}', $submission, ['includeSummary' => true]);

    expect($conditionalField->isConditionallyHidden($submission))->toBeFalse()
        ->and($allFields)->toContain('Other Reason')
        ->and($allFields)->toContain('Visible In Email');
});
