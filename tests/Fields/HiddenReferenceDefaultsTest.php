<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\Notification;

it('resolves custom hidden references before a submission exists', function (bool $passForm): void {
    $form = formie()->form()
        ->hiddenField('context', ['defaultValue' => '{form:handle}|{site:language}|{timestamp}'])
        ->create();
    $field = $form->getFieldByHandle('context');
    $language = Craft::$app->getSites()->getSiteById($form->siteId)->language;

    expect($form->getCurrentSubmission())->toBeNull()
        ->and($field->getInitialValue($passForm ? $form : null))
        ->toMatch('/^' . preg_quote($form->handle . '|' . $language, '/') . '\|\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
})->with([true, false]);

it('stores rendered hidden reference defaults for submission editing and email', function (): void {
    $form = formie()->form()->hiddenField('language', ['defaultValue' => '{site:language}'])
        ->hiddenField('capturedAt', ['defaultValue' => '{timestamp}'])->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $html = (string)Formie::$plugin->getRendering()->renderForm($form);
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $values = [];

        foreach (['language', 'capturedAt'] as $handle) {
            $input = $xpath->query('//input[@name="fields[' . $handle . ']"]')->item(0);
            expect($input)->not->toBeNull();
            $values[$handle] = $input->getAttribute('value');
        }

        expect($values['language'])->toBe(Craft::$app->getSites()->getCurrentSite()->language)
            ->and($values['capturedAt'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');

        $saved = formie()->submission($form)->with($values)->save();
        $submission = Submission::find()->id($saved->id)->status(null)->one();

        foreach ($values as $handle => $value) {
            $field = $form->getFieldByHandle($handle);
            expect($submission->getFieldValue($handle))->toBe($value)
                ->and((string)$field->getReferenceBlockHtml($submission, new Notification(), $value))->toContain($value);

            Craft::$app->getView()->setTemplateMode('cp');
            expect((string)$field->getSubmissionHtml($value, $submission))->toContain('value="' . $value . '"');
        }
    });
});
