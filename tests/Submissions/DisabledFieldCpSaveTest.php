<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\CpSubmissionFieldConditions;
use verbb\formie\models\HtmlSubmissionRequest;

use craft\elements\User;

function disabledFieldCpSave(Form $form, ?Submission $submission, array $values): Submission
{
    $response = WebRequestTestHelper::withWebRequestContext(function () {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

        return (new SubmissionsController('formie-submissions-test', Craft::$app))->actionSaveAdminSubmission();
    }, [
        'method' => 'POST',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'handle' => $form->handle,
            'submissionId' => $submission?->id,
            'expectedVersion' => $submission?->stateVersion ?? 0,
            'siteId' => $form->siteId,
            'fields' => $values,
        ],
    ]);

    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));
    $saved = Submission::find()->formId($form->id)->status(null)->isIncomplete(null)->one();
    expect($saved)->not->toBeNull();

    return $saved;
}

it('persists disabled visibility fields through CP creation edits and clears', function (string $mode, bool $existing): void {
    $child = ['type' => SingleLineText::class, 'handle' => 'note', 'label' => 'Note', 'visibility' => 'disabled'];
    $form = formie()->form()->settings(['cpSubmissionFieldConditions' => $mode])
        ->singleLineTextField('note', ['visibility' => 'disabled'])
        ->singleLineTextField('untouched')
        ->groupField('group', ['rows' => [['fields' => [$child]]]])
        ->repeaterField('people', ['rows' => [['fields' => [$child]]]])
        ->groupField('disabledGroup', ['visibility' => 'disabled', 'rows' => [['fields' => [array_replace($child, ['visibility' => 'visible'])]]]])
        ->create();

    $submission = $existing ? formie()->submission($form)->save() : null;

    foreach (['Internal note', 'Updated note', '0', ''] as $value) {
        $submission = disabledFieldCpSave($form, $submission, [
            'note' => $value,
            'untouched' => 'Keep',
            'group' => ['note' => $value],
            'people' => [['note' => $value], ['note' => 'Second row']],
            'disabledGroup' => ['note' => $value],
        ]);

        expect($submission->getFieldValue('note'))->toBe($value)
            ->and($submission->getFieldValue('group.note'))->toBe($value)
            ->and($submission->getFieldValue('people.0.note'))->toBe($value)
            ->and($submission->getFieldValue('people.1.note'))->toBe('Second row')
            ->and($submission->getFieldValue('disabledGroup.note'))->toBe($value)
            ->and($submission->getFieldValue('untouched'))->toBe('Keep');
    }

    // An unrelated later save must retain the stored internal values too.
    $submission = disabledFieldCpSave($form, $submission, ['untouched' => 'Changed']);
    expect($submission->getFieldValue('people.1.note'))->toBe('Second row')
        ->and($submission->getFieldValue('untouched'))->toBe('Changed');
})->with(['default' => '', 'follow' => CpSubmissionFieldConditions::FOLLOW, 'muted' => CpSubmissionFieldConditions::MUTED, 'show all' => CpSubmissionFieldConditions::SHOW_ALL])->with(['new' => false, 'existing' => true]);

it('still follows CP condition rules on fields with disabled visibility', function (string $mode, string $effect): void {
    $form = formie()->form()->settings(['cpSubmissionFieldConditions' => $mode])
        ->singleLineTextField('allow')
        ->singleLineTextField('note', [
            'visibility' => 'disabled',
            'enableConditions' => true,
            'conditions' => ['version' => 1, 'showRule' => $effect, 'conditionRule' => 'all', 'conditions' => [
                ['field' => 'allow', 'condition' => '=', 'value' => 'yes'],
            ]],
        ])->create();

    $saved = disabledFieldCpSave($form, null, ['allow' => 'no', 'note' => 'Internal note']);
    expect($saved->getFieldValue('note'))->toBe($mode === CpSubmissionFieldConditions::SHOW_ALL ? 'Internal note' : '');
    $saved = disabledFieldCpSave($form, $saved, ['allow' => 'yes', 'note' => 'Updated note']);
    expect($saved->getFieldValue('note'))->toBe('Updated note');
})->with([CpSubmissionFieldConditions::FOLLOW, CpSubmissionFieldConditions::MUTED, CpSubmissionFieldConditions::SHOW_ALL])->with(['show', 'enable']);

it('rejects disabled visitor values regardless of ambient CP request state', function (bool $cpRequest): void {
    $form = formie()->form()->singleLineTextField('note', ['visibility' => 'disabled'])->singleLineTextField('message')->create();

    $result = WebRequestTestHelper::withWebRequestContext(function () use ($form, $cpRequest) {
        Craft::$app->getRequest()->setIsCpRequest($cpRequest);
        Craft::$app->getUser()->setIdentity(User::find()->admin(true)->one());

        return runHtmlSubmission(new HtmlSubmissionRequest(['handle' => $form->handle, 'siteId' => $form->siteId]), SubmissionAuthorityType::VISITOR);
    }, ['method' => 'POST', 'bodyParams' => ['fields' => ['note' => 'Visitor value', 'message' => 'Public value']]]);

    expect($result->response->success)->toBeTrue();
    $saved = Submission::find()->id($result->command->submission->id)->status(null)->one();
    expect($saved?->getFieldValue('note'))->toBe('')
        ->and($saved?->getFieldValue('message'))->toBe('Public value');
})->with([false, true]);

it('still clears fields switched off independently of their visibility during CP saves', function (string $mode): void {
    $form = formie()->form()->settings(['cpSubmissionFieldConditions' => $mode])
        ->singleLineTextField('inactive', ['enabled' => false, 'visibility' => 'disabled'])
        ->singleLineTextField('note', ['visibility' => 'disabled'])
        ->create();

    $saved = disabledFieldCpSave($form, null, ['inactive' => 'Ignored', 'note' => 'Internal note']);
    expect($saved->getFieldValue('inactive'))->toBe('')
        ->and($saved->getFieldValue('note'))->toBe('Internal note');
})->with([CpSubmissionFieldConditions::FOLLOW, CpSubmissionFieldConditions::MUTED]);
