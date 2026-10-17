<?php

use verbb\formie\Formie;

it('honours option label display settings in report cells and exports', function (string $factory, mixed $selected, string $labels, string $values): void {
    $form = formie()->form()->{$factory}('choice', ['options' => [
        ['label' => 'Zero choice', 'value' => '0'],
        ['label' => 'Design & build', 'value' => 'design'],
    ]])->create();
    $submission = formie()->submission($form)->with(['choice' => $selected])->save();
    $columns = Formie::$plugin->getReportColumns();
    $column = ['type' => 'field', 'handle' => 'choice', 'header' => 'Choice'];

    foreach ([true, false] as $useLabels) {
        $display = ['useOptionLabels' => $useLabels];
        $expected = $useLabels ? $labels : $values;
        expect($columns->formatCell($submission, $column, $display))->toBe($expected)
            ->and($columns->formatViewerCell($submission, $column, $display)['value'])->toBe($expected)
            ->and($columns->formatRowAssoc($submission, [$column], $display)['Choice'])->toBe($expected);
    }
})->with([
    'dropdown zero' => ['dropdownField', '0', 'Zero choice', '0'],
    'radio zero' => ['radioField', '0', 'Zero choice', '0'],
    'checkboxes' => ['checkboxesField', ['0', 'design'], 'Zero choice, Design & build', '0, design'],
    'empty dropdown' => ['dropdownField', '', '', ''],
    'empty checkboxes' => ['checkboxesField', [], '', ''],
]);
