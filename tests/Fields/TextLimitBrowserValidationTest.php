<?php

declare(strict_types=1);

use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\Survey;

// Client-rendered forms check text limits in the browser and show the counter, as server-rendered forms do, so they
// get the limits, their units and the messages the server uses.
function textLimitRule(array $rules): array|false
{
    return current(array_filter($rules, static fn(array $rule): bool => $rule['type'] === 'textLimit'));
}

it('sends text limits as a browser validation rule with their messages', function(): void {
    $field = new SingleLineText(['handle' => 'notes', 'label' => 'Notes', 'limit' => true, 'min' => 3, 'minType' => 'characters', 'max' => 5, 'maxType' => 'words']);
    $rule = textLimitRule($field->browserValidationRules());

    expect($rule)->toMatchArray(['type' => 'textLimit', 'min' => 3, 'minType' => 'characters', 'max' => 5, 'maxType' => 'words'])
        ->and($rule['messages']['minCharacters'])->toBe('Notes must be no less than 3 characters.')
        ->and($rule['messages']['maxWords'])->toBe('Notes must be no greater than 5 words.')
        ->and(textLimitRule((new MultiLineText(['handle' => 'story', 'limit' => true, 'max' => 10]))->browserValidationRules()))->toMatchArray(['max' => 10, 'min' => null])
        ->and(textLimitRule((new SingleLineText(['handle' => 'open', 'min' => 3, 'max' => 5]))->browserValidationRules()))->toBeFalse();
});

it('fills a custom message limit with the bound it describes', function(): void {
    $field = new SingleLineText(['handle' => 'notes', 'label' => 'Notes', 'limit' => true, 'min' => 2, 'max' => 9, 'validationMessages' => [
        'minCharacters' => 'At least {limit}.',
        'maxCharacters' => 'At most {limit}.',
    ]]);
    $rule = textLimitRule($field->browserValidationRules());

    expect($rule['messages']['minCharacters'])->toBe('At least 2.')
        ->and($rule['messages']['maxCharacters'])->toBe('At most 9.');
});

it('sends the limit units for the counter in the client definition', function(): void {
    $input = (new MultiLineText(['handle' => 'story', 'limit' => true, 'max' => 10, 'maxType' => 'words']))->getClientRenderedInput();

    expect($input['textLimit'])->toBe(['min' => null, 'minType' => 'characters', 'max' => 10, 'maxType' => 'words'])
        ->and((new MultiLineText(['handle' => 'open', 'max' => 10]))->getClientRenderedInput()['textLimit'])->toBeNull();
});

it('checks a Survey text answer as the text field it shows', function(): void {
    $form = formie()->form()->addField(Survey::class, 'question', [
        'label' => 'Question',
        'displayType' => 'singleLineText',
        'limit' => true,
        'max' => 4,
        'maxType' => 'characters',
    ])->create();

    try {
        $field = $form->getFieldByHandle('question');

        expect(textLimitRule($field->browserValidationRules()))->toMatchArray(['max' => 4, 'maxType' => 'characters'])
            ->and($field->getClientRenderedInput()['textLimit'])->toMatchArray(['max' => 4]);
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});
