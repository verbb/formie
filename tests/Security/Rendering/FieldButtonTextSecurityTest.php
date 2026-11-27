<?php

declare(strict_types=1);

use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

// Repeater and Table "Add Label" and Summary "Description" are text set in the form builder. They are purified
// before rendering, so markup that runs script never reaches the form, while plain text and safe markup stay.

const FIELD_BUTTON_TEXT_PAYLOAD = 'Add <img src=x onerror=alert(1)><b>row</b>';

function fieldButtonTextHtml(string $handle, string $type, array $settings): string
{
    $form = formie()->form(['title' => 'Field button text ' . uniqid()])->{$type . 'Field'}($handle, $settings)->create();

    // The Summary heading only renders while a submission is in progress.
    if ($type === 'summary') {
        $submission = new Submission();
        $submission->setForm($form);
        $form->setCurrentSubmission($submission);
    }

    $view = Craft::$app->getView();
    $mode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

    try {
        return WebRequestTestHelper::withWebRequestContext(fn() => (string)Formie::$plugin->getRendering()->renderForm($form));
    } finally {
        $view->setTemplateMode($mode);
    }
}

it('purifies builder text rendered inside field buttons and headings', function (string $type, array $settings): void {
    $html = fieldButtonTextHtml('target', $type, $settings);

    expect($html)->not->toContain('onerror')
        ->and($html)->toContain('<b>row</b>');
})->with([
    'repeater add label' => ['repeater', ['addRowLabel' => FIELD_BUTTON_TEXT_PAYLOAD, 'rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'item', 'label' => 'Item']]]]]],
    'table add label' => ['table', ['addRowLabel' => FIELD_BUTTON_TEXT_PAYLOAD, 'columns' => [['id' => 'col1', 'heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']]]],
    'summary description' => ['summary', ['description' => FIELD_BUTTON_TEXT_PAYLOAD]],
]);
