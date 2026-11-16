<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;

// Editing a completed submission saves it once, so the browser changes its pages without sending them.
it('marks only forms editing a completed submission as revise forms', function(string $case, bool $expected): void {
    $form = formie()->form(['title' => 'Revise marker'])
        ->multiPage(2)
        ->page(1)->singleLineTextField('first')
        ->page(2)->singleLineTextField('second')
        ->create();

    $submission = formie()->submission($form)->with(['first' => 'One', 'second' => 'Two'])->save();

    if ($case === 'incomplete') {
        $submission->isIncomplete = true;
        expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    }

    $render = Formie::$plugin->getForms()->getFormById($form->id);

    if ($case !== 'new') {
        $render->setSubmission(Submission::find()->id($submission->id)->isIncomplete(null)->one());
    }

    $html = WebRequestTestHelper::withWebRequestContext(fn() => (string)Formie::$plugin->getRendering()->renderForm($render, ['renderCss' => false, 'renderJs' => false]));
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $element = $document->getElementsByTagName('form')->item(0);

    expect($element->hasAttribute('data-formie-revise'))->toBe($expected)
        ->and($document->getElementsByTagName('section')->length)->toBe(2);
})->with([
    'completed submission' => ['completed', true],
    'incomplete submission' => ['incomplete', false],
    'new submission' => ['new', false],
]);

it('leaves a custom action URL out of revise page changes', function(): void {
    $form = formie()->form(['title' => 'Revise custom action'])->singleLineTextField('first')->create();
    $submission = formie()->submission($form)->with(['first' => 'One'])->save();
    $render = Formie::$plugin->getForms()->getFormById($form->id);
    $render->setSubmission($submission);
    $render->setActionUrl('formie/submissions/submit');

    $html = WebRequestTestHelper::withWebRequestContext(fn() => (string)Formie::$plugin->getRendering()->renderForm($render, ['renderCss' => false, 'renderJs' => false]));

    expect($html)->not->toContain('data-formie-revise');
});
