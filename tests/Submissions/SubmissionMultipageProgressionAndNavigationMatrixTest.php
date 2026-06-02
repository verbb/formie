<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\models\SubmissionRequest;
use verbb\formie\services\SubmissionWorkflow;

dataset('multipage_submit_methods', ['ajax', 'page-reload']);

it('retains values across 4-page progression and back navigation using partial page payloads', function (string $submitMethod): void {
    $form = createFourPageSingleFieldForm($submitMethod);
    $pages = $form->getPages();
    $workflow = new SubmissionWorkflow();

    $step1Submission = new Submission();
    $step1Submission->setForm($form);
    $step1Submission->setFieldValueFromRequest('pageOneValue', 'one');

    $step1 = runSubmitStep($workflow, $form, $step1Submission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[0]->id);
    expect($step1->success)->toBeTrue()
        ->and($step1->nextPage?->id)->toBe($pages[1]->id)
        ->and($step1->submission->isIncomplete)->toBeTrue();

    $step2Submission = reloadSubmission($step1->submission->id);
    $step2Submission->setFieldValueFromRequest('pageTwoValue', 'two');

    $step2 = runSubmitStep($workflow, $form, $step2Submission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[1]->id);
    expect($step2->success)->toBeTrue()
        ->and($step2->nextPage?->id)->toBe($pages[2]->id);

    $step2Reloaded = reloadSubmission($step2->submission->id);
    expect($step2Reloaded->getFieldValue('pageOneValue'))->toBe('one')
        ->and($step2Reloaded->getFieldValue('pageTwoValue'))->toBe('two');

    $step3Submission = reloadSubmission($step2->submission->id);
    $step3Submission->setFieldValueFromRequest('pageThreeValue', 'three');

    $step3 = runSubmitStep($workflow, $form, $step3Submission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[2]->id);
    expect($step3->success)->toBeTrue()
        ->and($step3->nextPage?->id)->toBe($pages[3]->id);

    $backSubmission = reloadSubmission($step3->submission->id);
    $back = runSubmitStep($workflow, $form, $backSubmission, SubmissionWorkflow::SUBMIT_ACTION_BACK, (int)$pages[3]->id);
    expect($back->success)->toBeTrue()
        ->and($back->nextPage?->id)->toBe($pages[2]->id)
        ->and($back->submission->getFieldValue('pageOneValue'))->toBe('one')
        ->and($back->submission->getFieldValue('pageTwoValue'))->toBe('two')
        ->and($back->submission->getFieldValue('pageThreeValue'))->toBe('three');

    $forwardSubmission = reloadSubmission($back->submission->id);
    $forward = runSubmitStep($workflow, $form, $forwardSubmission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[2]->id);
    expect($forward->success)->toBeTrue()
        ->and($forward->nextPage?->id)->toBe($pages[3]->id);

    $finalSubmission = reloadSubmission($forward->submission->id);
    $finalSubmission->setFieldValueFromRequest('pageFourValue', 'four');
    $final = runSubmitStep($workflow, $form, $finalSubmission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[3]->id);

    expect($final->success)->toBeTrue()
        ->and($final->nextPage)->toBeNull()
        ->and($final->submission->isIncomplete)->toBeFalse()
        ->and($final->submission->getFieldValue('pageOneValue'))->toBe('one')
        ->and($final->submission->getFieldValue('pageTwoValue'))->toBe('two')
        ->and($final->submission->getFieldValue('pageThreeValue'))->toBe('three')
        ->and($final->submission->getFieldValue('pageFourValue'))->toBe('four');
})->with('multipage_submit_methods');

it('retains advanced nested values in multipage flows with partial page payloads', function (string $submitMethod): void {
    $form = createAdvancedFourPageForm($submitMethod);
    $pages = $form->getPages();
    $workflow = new SubmissionWorkflow();

    $step1Submission = new Submission();
    $step1Submission->setForm($form);
    $step1Submission->setFieldValueFromRequest('contactEmail', 'advanced@example.test');
    $step1 = runSubmitStep($workflow, $form, $step1Submission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[0]->id);

    $step2Submission = reloadSubmission($step1->submission->id);
    $step2Submission->setFieldValueFromRequest('identity', [
        'firstName' => 'Ada',
        'lastName' => 'Lovelace',
    ]);
    $step2 = runSubmitStep($workflow, $form, $step2Submission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[1]->id);

    $step3Submission = reloadSubmission($step2->submission->id);
    $step3Submission->setFieldValueFromRequest('profileAddress', [
        'address1' => '123 Main St',
        'city' => 'Melbourne',
        'state' => 'VIC',
        'zip' => '3000',
        'country' => 'AU',
    ]);
    $step3 = runSubmitStep($workflow, $form, $step3Submission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[2]->id);

    $step4Submission = reloadSubmission($step3->submission->id);
    $step4Submission->setFieldValueFromRequest('groupMeta', ['groupRequiredText' => 'group-value']);
    $step4Submission->setFieldValueFromRequest('repeatMeta', [['repeatRequiredText' => 'repeat-value']]);
    $final = runSubmitStep($workflow, $form, $step4Submission, SubmissionWorkflow::SUBMIT_ACTION_SUBMIT, (int)$pages[3]->id);

    expect($final->success)->toBeTrue()
        ->and($final->nextPage)->toBeNull()
        ->and($final->submission->isIncomplete)->toBeFalse()
        ->and($final->submission->getFieldValue('contactEmail'))->toBe('advanced@example.test')
        ->and($final->submission->getFieldValue('identity.firstName'))->toBe('Ada')
        ->and($final->submission->getFieldValue('identity.lastName'))->toBe('Lovelace')
        ->and($final->submission->getFieldValue('profileAddress.address1'))->toBe('123 Main St')
        ->and($final->submission->getFieldValue('groupMeta.groupRequiredText'))->toBe('group-value')
        ->and($final->submission->getFieldValue('repeatMeta.0.repeatRequiredText'))->toBe('repeat-value');
})->with('multipage_submit_methods');

it('supports direct tab-style target page navigation without final submit', function (string $submitMethod): void {
    $form = createFourPageSingleFieldForm($submitMethod);
    $pages = $form->getPages();
    $workflow = new SubmissionWorkflow();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('pageOneValue', 'tab-nav');

    $jumpToPage3 = runSubmitStep(
        $workflow,
        $form,
        $submission,
        SubmissionWorkflow::SUBMIT_ACTION_SAVE,
        (int)$pages[0]->id,
        (int)$pages[2]->id
    );

    expect($jumpToPage3->success)->toBeTrue()
        ->and($jumpToPage3->nextPage?->id)->toBe($pages[2]->id)
        ->and($jumpToPage3->submission->isIncomplete)->toBeTrue();

    $jumpNavigationSubmission = $jumpToPage3->submission;
    $jumpNavigationSubmission->setFieldValueFromRequest('pageThreeValue', 'tab-three');

    $jumpBackToPage2 = runSubmitStep(
        $workflow,
        $form,
        $jumpNavigationSubmission,
        SubmissionWorkflow::SUBMIT_ACTION_SAVE,
        (int)$pages[2]->id,
        (int)$pages[1]->id
    );

    expect($jumpBackToPage2->success)->toBeTrue()
        ->and($jumpBackToPage2->nextPage?->id)->toBe($pages[1]->id)
        ->and($jumpBackToPage2->submission->isIncomplete)->toBeTrue();
})->with('multipage_submit_methods');

function createFourPageSingleFieldForm(string $submitMethod): mixed
{
    return formie()
        ->form([
            'title' => 'Four Page Basic Matrix ' . $submitMethod . ' ' . uniqid(),
            'handle' => multipageMatrixHandle(),
            'settings' => [
                'submitMethod' => $submitMethod,
            ],
        ])
        ->multiPage(4)
        ->onPage(1)->singleLineTextField('pageOneValue', ['required' => true])
        ->onPage(2)->singleLineTextField('pageTwoValue', ['required' => true])
        ->onPage(3)->singleLineTextField('pageThreeValue', ['required' => true])
        ->onPage(4)->singleLineTextField('pageFourValue', ['required' => true])
        ->create();
}

function createAdvancedFourPageForm(string $submitMethod): mixed
{
    $nameRows = markMatrixSubFieldRequired((new Name(['useMultipleFields' => true]))->getSubFields(), 'firstName');
    $addressRows = markMatrixSubFieldRequired((new Address())->getSubFields(), 'address1');

    return formie()
        ->form([
            'title' => 'Four Page Advanced Matrix ' . $submitMethod . ' ' . uniqid(),
            'handle' => multipageMatrixHandle(),
            'settings' => [
                'submitMethod' => $submitMethod,
            ],
        ])
        ->multiPage(4)
        ->onPage(1)->emailField('contactEmail', ['required' => true])
        ->onPage(2)->nameField('identity', ['useMultipleFields' => true, 'rows' => $nameRows])
        ->onPage(3)->addressField('profileAddress', ['rows' => $addressRows])
        ->onPage(4)
            ->groupField('groupMeta', [
                'rows' => [[
                    'fields' => [[
                        'type' => SingleLineText::class,
                        'handle' => 'groupRequiredText',
                        'label' => 'Group Required',
                        'required' => true,
                    ]],
                ]],
            ])
            ->repeaterField('repeatMeta', [
                'rows' => [[
                    'fields' => [[
                        'type' => SingleLineText::class,
                        'handle' => 'repeatRequiredText',
                        'label' => 'Repeater Required',
                        'required' => true,
                    ]],
                ]],
            ])
        ->create();
}

function markMatrixSubFieldRequired(array $rows, string $handle): array
{
    foreach ($rows as $rowIndex => $rowConfig) {
        foreach (($rowConfig['fields'] ?? []) as $fieldIndex => $fieldConfig) {
            if (($fieldConfig['handle'] ?? null) === $handle) {
                $rows[$rowIndex]['fields'][$fieldIndex]['required'] = true;
            }
        }
    }

    return $rows;
}

function runSubmitStep(
    SubmissionWorkflow $workflow,
    mixed $form,
    Submission $submission,
    string $submitAction,
    int $pageId,
    ?int $targetPageId = null
): mixed {
    return $workflow->processSubmissionRequest(new SubmissionRequest([
        'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
        'form' => $form,
        'submission' => $submission,
        'submitAction' => $submitAction,
        'pageId' => $pageId,
        'targetPageId' => $targetPageId,
    ]));
}

function reloadSubmission(?int $id): Submission
{
    $submission = Submission::find()->id($id)->status(null)->isSpam(null)->isIncomplete(null)->one();

    if (!$submission) {
        throw new RuntimeException('Unable to reload submission for multipage matrix assertions.');
    }

    return $submission;
}

function multipageMatrixHandle(): string
{
    static $counter = 1000;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = 'matrix' . $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}
