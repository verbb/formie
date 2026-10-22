<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\fields\{Email, Group, MultiLineText, Number, SingleLineText};
use verbb\formie\Formie;
use verbb\formie\models\FormInstanceConfig;
use yii\base\Event;

function groupDefaultValueForm(string $type, mixed $default): Form
{
    $form = formie()->form()->groupField('details', ['rows' => [['fields' => [[
        'type' => $type,
        'handle' => 'answer',
        'label' => 'Answer',
        'defaultValue' => $default,
    ]]]]])->create();

    return Form::find()->id($form->id)->one();
}

function renderedGroupDefaultValue(Form $form): string
{
    return WebRequestTestHelper::withWebRequestContext(function () use ($form): string {
        $html = (string)Formie::$plugin->getRendering()->renderForm($form);
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $input = (new DOMXPath($document))->query('//*[@name="fields[details][answer]"]')->item(0);
        expect($input)->not->toBeNull();

        return $input->nodeName === 'textarea' ? $input->textContent : $input->getAttribute('value');
    });
}

it('renders saved child defaults when a group has no default value', function (string $type, mixed $default, string $expected): void {
    $form = groupDefaultValueForm($type, $default);

    expect(renderedGroupDefaultValue($form))->toBe($expected);
})->with([
    'text' => [SingleLineText::class, 'Default answer', 'Default answer'],
    'textarea' => [MultiLineText::class, "First line\nSecond line", "First line\nSecond line"],
    'email' => [Email::class, 'default@example.test', 'default@example.test'],
    'number' => [Number::class, 7, '7'],
    'zero' => [SingleLineText::class, '0', '0'],
]);

it('preserves group and child prefills over child defaults', function (string $scope, string $value): void {
    $form = groupDefaultValueForm(SingleLineText::class, 'Default answer');
    $group = $form->getFieldByHandle('details');
    $field = $group->getFieldByHandle('answer');
    $initial = $scope === 'group' ? [$group->uid => ['answer' => $value]] : [$field->uid => $value];
    $form->replaceInstanceConfig(new FormInstanceConfig(initial: $initial));

    expect(renderedGroupDefaultValue($form))->toBe($value);
})->with(['group', 'child'])->with(['Prefilled answer', '', '0']);

it('preserves explicitly configured and event-provided group defaults', function (string $source, string $value): void {
    $form = groupDefaultValueForm(SingleLineText::class, 'Default answer');
    $group = $form->getFieldByHandle('details');
    $handler = function (ModifyFieldValueEvent $event) use ($value): void {
        $event->value = ['answer' => $value];
    };

    if ($source === 'setting') {
        $group->defaultValue = ['answer' => $value];
    } else {
        // Rendering creates field instances, so register the extension at class level.
        Event::on(Group::class, Group::EVENT_MODIFY_DEFAULT_VALUE, $handler);
    }

    try {
        expect(renderedGroupDefaultValue($form))->toBe($value);
    } finally {
        Event::off(Group::class, Group::EVENT_MODIFY_DEFAULT_VALUE, $handler);
    }
})->with(['setting', 'event'])->with(['Group default', '', '0']);

it('preserves submitted group answers after saving and reloading', function (string $value): void {
    $form = groupDefaultValueForm(SingleLineText::class, 'Default answer');
    $saved = formie()->submission($form)->with(['details' => ['answer' => $value]])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $form->setCurrentSubmission($submission);

    expect($submission->getFieldValue('details')['answer'])->toBe($value)
        ->and(renderedGroupDefaultValue($form))->toBe($value);
})->with(['Submitted answer', '', '0']);
