<?php

declare(strict_types=1);

use verbb\formie\fields\Table;
use verbb\formie\Formie;

function tableHeadingXssProbe(): string
{
    return 'Col<img src=x onerror=alert(1)><script>alert(2)</script> **Bold** [docs](https://example.test/docs)';
}

function tableHeadingPurificationForm(): verbb\formie\elements\Form
{
    return formie()->form(['title' => 'Table Heading Purification'])->tableField('items', [
        'columns' => [
            'col1' => ['heading' => tableHeadingXssProbe(), 'handle' => 'probe', 'type' => 'singleline'],
            'col2' => ['heading' => 'Terms & conditions', 'handle' => 'plain', 'type' => 'singleline'],
        ],
    ])->create();
}

/**
 * @return string[]
 */
function tableHeadingCells(string $html): array
{
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $html, $matches);

    return $matches[1];
}

it('purifies Table column headings on the rendered form and keeps Markdown', function(): void {
    $form = tableHeadingPurificationForm();
    $headings = tableHeadingCells((string)Formie::$plugin->getRendering()->renderField($form, 'items'));

    expect($headings)->toHaveCount(2)
        ->and($headings[0])
        ->toContain('Col')
        ->toContain('<strong>Bold</strong>')
        ->toContain('<a href="https://example.test/docs">docs</a>')
        ->not->toContain('onerror')
        ->not->toContain('<script')
        ->and($headings[1])->toBe('Terms &amp; conditions');
})->group('security');

it('purifies Table column headings in summaries', function(): void {
    $form = tableHeadingPurificationForm();
    $field = $form->getFieldByHandle('items');
    $headings = tableHeadingCells((string)$field->getValueForSummary([['col1' => 'Apples', 'col2' => 'Pears']]));

    expect($headings)->toHaveCount(2)
        ->and($headings[0])
        ->toContain('Col<img src="x" alt="" />')
        ->not->toContain('onerror')
        ->not->toContain('<script')
        ->and($headings[1])->toBe('Terms & conditions');
})->group('security');

function tableCellXssProbe(): string
{
    return 'Widget<img src=x onerror=alert(1)><script>alert(2)</script>';
}

/**
 * @return string[]
 */
function tableBodyCells(string $html): array
{
    preg_match_all('/<td\b[^>]*>(.*?)<\/td>/s', $html, $matches);

    return $matches[1];
}

function expectEncodedTableCells(string $html): void
{
    expect(tableBodyCells($html))->toBe([
        'Widget&lt;img src=x onerror=alert(1)&gt;&lt;script&gt;alert(2)&lt;/script&gt;',
        'Fish &amp; chips',
    ]);
}

it('encodes Table cell values in summaries', function(): void {
    $field = tableHeadingPurificationForm()->getFieldByHandle('items');

    expectEncodedTableCells((string)$field->getValueForSummary([['col1' => tableCellXssProbe(), 'col2' => 'Fish & chips']]));
})->group('security');

it('encodes Table cell values in Group and Repeater summaries', function(): void {
    $table = [
        'type' => Table::class,
        'handle' => 'grid',
        'label' => 'Grid',
        'columns' => [
            'col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline'],
            'col2' => ['heading' => 'Note', 'handle' => 'note', 'type' => 'singleline'],
        ],
    ];
    $form = formie()->form()
        ->groupField('details', ['rows' => [['fields' => [$table]]]])
        ->repeaterField('offices', ['rows' => [['fields' => [$table]]]])
        ->create();
    $rows = [['col1' => tableCellXssProbe(), 'col2' => 'Fish & chips']];
    $submission = formie()->submission($form)->with([
        'details' => ['grid' => $rows],
        'offices' => [['grid' => $rows]],
    ])->save();

    foreach (['details', 'offices'] as $handle) {
        $field = $form->getFieldByHandle($handle);

        expectEncodedTableCells((string)$field->getValueForSummary($submission->getFieldValue($handle), $submission));
    }
})->group('security');
