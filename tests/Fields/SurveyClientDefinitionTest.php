<?php

declare(strict_types=1);

use verbb\formie\fields\Survey;

// Client-rendered Survey fields pick their control from the display type, so the client definition carries it with
// the settings that control needs.
function surveyClientInput(array $config): array
{
    $form = formie()->form()->addField(Survey::class, 'question', array_merge([
        'label' => 'Question',
        'options' => [['label' => 'Alpha', 'value' => 'alpha'], ['label' => 'Beta', 'value' => 'beta'], ['label' => 'Gamma', 'value' => 'gamma']],
    ], $config))->create();

    try {
        return $form->getFieldByHandle('question')->getClientRenderedInput();
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
}

it('sends the display type to client-rendered forms', function(string $displayType): void {
    expect(surveyClientInput(['displayType' => $displayType])['displayType'])->toBe($displayType);
})->with(['likert', 'rank', 'rating', 'dropdown', 'radio', 'checkboxes', 'singleLineText', 'multiLineText']);

it('sends Likert rows only when the scale answers more than one row', function(): void {
    $rows = [['label' => 'Row one', 'value' => 'rowOne'], ['label' => 'Row two', 'value' => 'rowTwo']];

    expect(surveyClientInput(['displayType' => 'likert'])['likertRows'])->toBe([])
        ->and(surveyClientInput(['displayType' => 'likert', 'multipleRowsEnabled' => true, 'likertRows' => $rows])['likertRows'])->toBe([
            ['label' => 'Row one', 'value' => 'rowOne'],
            ['label' => 'Row two', 'value' => 'rowTwo'],
        ]);
});

it('starts a Rank answer on every option in order, as server-rendered forms submit it', function(): void {
    expect(surveyClientInput(['displayType' => 'rank'])['defaultValue'])->toBe(['alpha', 'beta', 'gamma']);
});

it('sends the star colour for Rating and the text field contract for text display types', function(): void {
    $text = surveyClientInput(['displayType' => 'singleLineText']);

    expect(surveyClientInput(['displayType' => 'rating', 'starColor' => '#ff0000'])['starColor'])->toBe('#ff0000')
        ->and($text['fieldKind'])->toBe('text')
        ->and($text)->not->toHaveKey('options')
        ->and(surveyClientInput(['displayType' => 'multiLineText'])['fieldKind'])->toBe('textarea');
});
