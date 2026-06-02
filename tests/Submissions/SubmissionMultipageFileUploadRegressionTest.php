<?php

declare(strict_types=1);

use Tests\Support\UploadTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\FileUpload;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

it('keeps file upload values stable across multipage submit steps with full payloads', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();

    $rows = [[
        'fields' => [[
            'type' => FileUpload::class,
            'handle' => 'nestedUpload',
            'label' => 'Nested Upload',
            'restrictFiles' => false,
            'allowedKinds' => ['text'],
        ]],
    ]];

    $form = formie()
        ->form(['title' => 'Multipage File Upload Regression'])
        ->multiPage(4)
        ->onPage(1)
            ->fileUploadField('topUpload', ['restrictFiles' => false, 'allowedKinds' => ['text']])
        ->onPage(2)
            ->groupField('groupUpload', ['rows' => $rows])
        ->onPage(3)
            ->repeaterField('repeatUpload', ['rows' => $rows])
        ->onPage(4)
            ->singleLineTextField('finalNote')
        ->create();

    $pages = $form->getPages();
    expect($pages)->toHaveCount(4);

    $topAsset = UploadTestHelper::seedAsset('mp-top.txt', 'top', $volume);
    $groupAsset = UploadTestHelper::seedAsset('mp-group.txt', 'group', $volume);
    $repeaterAsset = UploadTestHelper::seedAsset('mp-repeater.txt', 'repeater', $volume);

    $submission = new Submission();
    $submission->setForm($form);

    $process = Formie::$plugin->getSubmissionWorkflow();

    // Step 1: page 1 submit with full-form payload semantics.
    $submission->setFieldValueFromRequest('topUpload', [$topAsset->id]);
    $submission->setFieldValueFromRequest('groupUpload', []);
    $submission->setFieldValueFromRequest('repeatUpload', []);
    $submission->setFieldValueFromRequest('finalNote', '');

    $step1 = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'pageId' => (int)$pages[0]->id,
    ]));

    expect($step1->success)->toBeTrue()
        ->and($step1->nextPage?->id)->toBe($pages[1]->id)
        ->and($step1->submission->isIncomplete)->toBeTrue()
        ->and($step1->submission->getFieldValue('topUpload')->ids())->toBe([$topAsset->id]);

    // Step 2: page 2 submit sends whole payload again.
    $submission = $step1->submission;
    $submission->setFieldValueFromRequest('topUpload', [$topAsset->id]);
    $submission->setFieldValueFromRequest('groupUpload', [
        'nestedUpload' => [$groupAsset->id],
    ]);
    $submission->setFieldValueFromRequest('repeatUpload', []);
    $submission->setFieldValueFromRequest('finalNote', '');

    $step2 = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'pageId' => (int)$pages[1]->id,
    ]));

    expect($step2->success)->toBeTrue()
        ->and($step2->nextPage?->id)->toBe($pages[2]->id)
        ->and($step2->submission->isIncomplete)->toBeTrue()
        ->and($step2->submission->getFieldValue('topUpload')->ids())->toBe([$topAsset->id])
        ->and($step2->submission->getFieldValue('groupUpload.nestedUpload')->ids())->toBe([$groupAsset->id]);

    // Step 3: page 3 submit adds repeater file and moves to page 4.
    $submission = $step2->submission;
    $submission->setFieldValueFromRequest('topUpload', [$topAsset->id]);
    $submission->setFieldValueFromRequest('groupUpload', [
        'nestedUpload' => [$groupAsset->id],
    ]);
    $submission->setFieldValueFromRequest('repeatUpload', [[
        'nestedUpload' => [$repeaterAsset->id],
    ]]);
    $submission->setFieldValueFromRequest('finalNote', '');

    $step3 = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'pageId' => (int)$pages[2]->id,
    ]));

    expect($step3->success)->toBeTrue()
        ->and($step3->nextPage?->id)->toBe($pages[3]->id)
        ->and($step3->submission->isIncomplete)->toBeTrue()
        ->and($step3->submission->getFieldValue('topUpload')->ids())->toBe([$topAsset->id])
        ->and($step3->submission->getFieldValue('groupUpload.nestedUpload')->ids())->toBe([$groupAsset->id])
        ->and($step3->submission->getFieldValue('repeatUpload.0.nestedUpload')->ids())->toBe([$repeaterAsset->id]);

    // Step 4a: navigate backward from page 4 -> page 3 and ensure uploaded IDs remain stable.
    $submission = $step3->submission;
    $submission->setFieldValueFromRequest('topUpload', [$topAsset->id]);
    $submission->setFieldValueFromRequest('groupUpload', [
        'nestedUpload' => [$groupAsset->id],
    ]);
    $submission->setFieldValueFromRequest('repeatUpload', [[
        'nestedUpload' => [$repeaterAsset->id],
    ]]);
    $submission->setFieldValueFromRequest('finalNote', '');

    $backToPage3 = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_BACK,
        'pageId' => (int)$pages[3]->id,
    ]));

    expect($backToPage3->success)->toBeTrue()
        ->and($backToPage3->nextPage?->id)->toBe($pages[2]->id)
        ->and($backToPage3->submission->isIncomplete)->toBeTrue()
        ->and($backToPage3->submission->getFieldValue('topUpload')->ids())->toBe([$topAsset->id])
        ->and($backToPage3->submission->getFieldValue('groupUpload.nestedUpload')->ids())->toBe([$groupAsset->id])
        ->and($backToPage3->submission->getFieldValue('repeatUpload.0.nestedUpload')->ids())->toBe([$repeaterAsset->id]);

    // Step 4b: move forward again page 3 -> page 4 to simulate back/forward navigation churn.
    $submission = $backToPage3->submission;
    $submission->setFieldValueFromRequest('topUpload', [$topAsset->id]);
    $submission->setFieldValueFromRequest('groupUpload', [
        'nestedUpload' => [$groupAsset->id],
    ]);
    $submission->setFieldValueFromRequest('repeatUpload', [[
        'nestedUpload' => [$repeaterAsset->id],
    ]]);
    $submission->setFieldValueFromRequest('finalNote', '');

    $forwardAgain = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'pageId' => (int)$pages[2]->id,
    ]));

    expect($forwardAgain->success)->toBeTrue()
        ->and($forwardAgain->nextPage?->id)->toBe($pages[3]->id)
        ->and($forwardAgain->submission->isIncomplete)->toBeTrue()
        ->and($forwardAgain->submission->getFieldValue('topUpload')->ids())->toBe([$topAsset->id])
        ->and($forwardAgain->submission->getFieldValue('groupUpload.nestedUpload')->ids())->toBe([$groupAsset->id])
        ->and($forwardAgain->submission->getFieldValue('repeatUpload.0.nestedUpload')->ids())->toBe([$repeaterAsset->id]);

    // Step 5: final page submit with a text field to ensure mixed payload is stable.
    $submission = $forwardAgain->submission;
    $submission->setFieldValueFromRequest('topUpload', [$topAsset->id]);
    $submission->setFieldValueFromRequest('groupUpload', [
        'nestedUpload' => [$groupAsset->id],
    ]);
    $submission->setFieldValueFromRequest('repeatUpload', [[
        'nestedUpload' => [$repeaterAsset->id],
    ]]);
    $submission->setFieldValueFromRequest('finalNote', 'Final page payload');

    $finalStep = $process->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => SubmissionWorkflow::SUBMIT_ACTION_SUBMIT,
        'pageId' => (int)$pages[3]->id,
    ]));

    expect($finalStep->success)->toBeTrue()
        ->and($finalStep->nextPage)->toBeNull()
        ->and($finalStep->submission->isIncomplete)->toBeFalse()
        ->and($finalStep->submission->getFieldValue('topUpload')->ids())->toBe([$topAsset->id])
        ->and($finalStep->submission->getFieldValue('groupUpload.nestedUpload')->ids())->toBe([$groupAsset->id])
        ->and($finalStep->submission->getFieldValue('repeatUpload.0.nestedUpload')->ids())->toBe([$repeaterAsset->id])
        ->and($finalStep->submission->getFieldValue('finalNote'))->toBe('Final page payload');
});
