<?php

use craft\elements\Entry;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FieldsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\services\RuntimeConfiguration;

it('retains limited entry selections through rendered pages and final submission', function (string $submitMethod, bool $prefill): void {
    $entry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
    expect($entry)->not->toBeNull();
    $other = new Entry([
        'sectionId' => $entry->sectionId,
        'typeId' => $entry->typeId,
        'title' => 'Later course',
        'slug' => 'later-course-' . uniqid(),
    ]);
    $entryType = $entry->getType();
    $hasTitleField = $entryType->hasTitleField;
    $entryType->hasTitleField = true;
    try {
        expect(Craft::$app->getElements()->saveElement($other))->toBeTrue();
    } finally {
        $entryType->hasTitleField = $hasTitleField;
    }
    $settings = [
        'sourceType' => 'groups',
        'sources' => ['section:' . $entry->getSection()->uid],
        'optionLimit' => '1',
        'orderBy' => 'id ASC',
        'placeholder' => 'Select a course',
        'required' => true,
    ];
    $form = formie()->form()->settings(['submitMethod' => $submitMethod, 'disableCaptchas' => true])->multiPage(3)
        ->onPage(1)->entriesField('course', $settings + ['prefillQueryParam' => 'course'])
        ->entriesField('courseOption', $settings + ['prefillQueryParam' => 'course-option'])
        ->onPage(2)->singleLineTextField('notes')
        ->onPage(3)->summaryField('summary')->create();
    $query = ['course' => (string)$other->id, 'course-option' => (string)$other->id];
    (new RuntimeConfiguration())->establish($form, $query);
    $submission = new Submission(['title' => 'Continuing course application']);
    $submission->setForm($form);
    foreach (['course', 'courseOption'] as $handle) {
        $field = $form->getFieldByHandle($handle);
        $submission->setFieldValueFromRequest($handle, $field->serializeValueForClientInput($field->getInitialValue()));
    }
    $pages = $form->getPages();
    $response = runSubmissionCommand(submissionCommand([
        'form' => $form, 'submission' => $submission, 'pageId' => (int)$pages[0]->id,
    ]));
    expect($response->success)->toBeTrue()->and($response->nextPage?->id)->toBe($pages[1]->id);

    foreach ([1, 2] as $pageIndex) {
        // Separate loads reproduce the render form and persisted submission owning different instances.
        $form = Form::find()->id($form->id)->one();
        $submission = Submission::find()->id($submission->id)->status(null)->one();
        $form->setCurrentSubmission($submission);
        (new RuntimeConfiguration())->establish($form, $prefill ? $query : []);
        $html = WebRequestTestHelper::withWebRequestContext(
            fn() => (string)Formie::$plugin->getRendering()->renderForm($form),
            ['method' => 'POST', 'queryParams' => $query],
        );
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        foreach (['course', 'courseOption'] as $handle) {
            $selected = $xpath->query('//select[@name="fields[' . $handle . '][]"]/option[@selected]');
            expect($selected->length)->toBe(1)
                ->and($selected->item(0)->getAttribute('value'))->toBe((string)$other->id);
            $submission->setFieldValueFromRequest($handle, [$selected->item(0)->getAttribute('value')]);
        }
        expect($html)->toContain('Later course');
        if ($pageIndex === 2) {
            $token = $xpath->query('//*[@data-formie-summary-container]')->item(0)->getAttribute('data-formie-summary-token');
            $summary = WebRequestTestHelper::withWebRequestContext(
                fn() => (new FieldsController('formie-fields', Craft::$app))->actionGetSummaryHtml(),
                ['method' => 'POST', 'bodyParams' => ['accessToken' => $token]],
            );
            expect(substr_count($summary, 'Later course'))->toBe(2);
        }
        $response = runSubmissionCommand(submissionCommand([
            'form' => $form, 'submission' => $submission, 'pageId' => (int)$pages[$pageIndex]->id,
        ]));
        expect($response->success)->toBeTrue()->and($submission->getErrors())->toBe([]);
    }

    $saved = Submission::find()->id($submission->id)->status(null)->one();
    expect($saved->isIncomplete)->toBeFalse()
        ->and($saved->getFieldValue('course')->ids())->toBe([$other->id])
        ->and($saved->getFieldValue('courseOption')->ids())->toBe([$other->id]);
})->with(['ajax', 'page-reload'])->with([false, true]);
