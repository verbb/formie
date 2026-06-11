<?php

declare(strict_types=1);

use verbb\formie\fields\Table;
use verbb\formie\helpers\References;
use verbb\formie\helpers\RepeaterReferenceHelper;
use verbb\formie\helpers\TableReferenceHelper;
use verbb\formie\models\RichText;

it('parses table column scope metadata from reference tokens', function (): void {
    $form = formie()
        ->form(['title' => 'Table Reference Scopes'])
        ->tableField('tickets', [
            'columns' => [
                'col1' => ['heading' => 'Ticket', 'handle' => 'ticket', 'type' => 'singleline'],
                'col3' => ['heading' => 'Qty', 'handle' => 'qty', 'type' => 'number'],
            ],
            'staticRows' => true,
            'defaults' => [[], [], []],
        ])
        ->create();

    $tableField = $form->getFieldByHandle('tickets');
    expect($tableField)->toBeInstanceOf(Table::class);

    $ref = (string)$tableField->reference;
    $expr = References::parseReferenceExpression('{field:' . $ref . ':col3;scope=all}');

    expect($expr->isValid)->toBeTrue()
        ->and($expr->identifier)->toBe($ref)
        ->and($expr->selector)->toBe('col3')
        ->and($expr->transformerParams['scope'] ?? null)->toBe('all');
});

it('resolves table column references by row scope', function (): void {
    $form = formie()
        ->form(['title' => 'Table Column References'])
        ->tableField('tickets', [
            'columns' => [
                'col1' => ['heading' => 'Ticket', 'handle' => 'ticket', 'type' => 'singleline'],
                'col3' => ['heading' => 'Qty', 'handle' => 'qty', 'type' => 'number'],
            ],
            'staticRows' => true,
            'defaults' => [[], [], []],
        ])
        ->create();

    $tableField = $form->getFieldByHandle('tickets');
    $ref = (string)$tableField->reference;

    $submission = formie()->submission($form)->with([
        'tickets' => [
            ['col3' => '2'],
            ['col3' => '3'],
            ['col3' => '5'],
        ],
    ])->save();

    expect($submission->getFieldValue(References::field($ref, 'col3', ['scope' => 'first'])))->toBe('2')
        ->and($submission->getFieldValue(References::field($ref, 'col3', ['scope' => 'last'])))->toBe('5')
        ->and($submission->getFieldValue(References::field($ref, 'col3', ['scope' => 'all'])))->toBe(['2', '3', '5'])
        ->and($submission->getFieldValue(References::field($ref, 'col3', ['scope' => 'count'])))->toBe(3)
        ->and($submission->getFieldValue(References::field($ref, 'col3', ['scope' => 'rows', 'rows' => '1,3'])))->toBe(['2', '5'])
        ->and($submission->getFieldValue(References::field($ref, 'qty', ['scope' => 'all'])))->toBe(['2', '3', '5']);
});

it('requires row scope for table column tokens', function (): void {
    $form = formie()
        ->form(['title' => 'Table Scope Requirement'])
        ->tableField('tickets', [
            'columns' => [
                'col3' => ['heading' => 'Qty', 'handle' => 'qty', 'type' => 'number'],
            ],
        ])
        ->create();

    $tableField = $form->getFieldByHandle('tickets');
    $ref = (string)$tableField->reference;

    $submission = formie()->submission($form)->with([
        'tickets' => [
            ['col3' => '2'],
        ],
    ])->save();

    expect(TableReferenceHelper::requiresScope($submission, $ref, 'col3'))->toBeTrue()
        ->and(TableReferenceHelper::resolve($submission, $tableField, 'col3', ['scope' => 'first']))->toBe('2');
});

it('builds calculation formula variables with table row scope metadata', function (): void {
    $form = formie()
        ->form(['title' => 'Table Calculations Formula'])
        ->tableField('tickets', [
            'columns' => [
                'col3' => ['heading' => 'Qty', 'handle' => 'qty', 'type' => 'number'],
            ],
            'staticRows' => true,
            'defaults' => [[], [], []],
        ])
        ->calculationsField('total')
        ->create();

    $tableField = $form->getFieldByHandle('tickets');
    $calcField = $form->getFieldByHandle('total');
    $ref = (string)$tableField->reference;

    $calcField->formula = RichText::from('{field:' . $ref . ':col3;scope=all}');
    $formula = $calcField->getFormula();

    expect($formula['expression'])->not->toContain('{field:')
        ->and($formula['variables'])->toHaveCount(1);

    $variable = array_values($formula['variables'])[0];

    expect($variable['sourceKey'] ?? null)->toBe('tickets.col3')
        ->and($variable['scope'] ?? null)->toBe('all')
        ->and($variable['fieldKind'] ?? null)->toBe(Table::KIND_TABLE);
});

it('reuses repeater row expression parsing for table custom row scopes', function (): void {
    expect(RepeaterReferenceHelper::parseRowsExpression('1,3,5', 5))
        ->toBe([0, 2, 4])
        ->and(TableReferenceHelper::getColumnReferenceSelector(new Table([
            'columns' => [
                'col3' => ['heading' => 'Qty', 'handle' => 'qty', 'type' => 'number'],
            ],
        ]), 'qty'))->toBe('col3');
});
