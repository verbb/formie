<?php

use verbb\formie\Formie;
use verbb\formie\enums\NavigationIntent;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\models\SubmissionAuthority;
use verbb\formie\models\SubmissionCommand;
use verbb\formie\models\SubmissionResponse;

function submissionCommand(array $attributes): SubmissionCommand
{
    $form = $attributes['form'];
    $submission = $attributes['submission'];
    $submission->setForm($form);
    $operation = $attributes['operation'] ?? SubmissionOperation::SUBMIT;
    $navigation = $attributes['navigation'] ?? NavigationIntent::ADVANCE;
    return new SubmissionCommand(
        $operation, $navigation,
        $attributes['authority'] ?? new SubmissionAuthority(SubmissionAuthorityType::TRUSTED_INTERNAL, (int)$form->id, $submission->id, 'test'),
        $form, $submission,
        $attributes['expectedVersion'] ?? $submission->stateVersion,
        $attributes['operationId'] ?? null,
        isset($attributes['operationId']) ? Formie::$plugin->getSubmissionOperations()->fingerprint($attributes['payload'] ?? []) : null,
        $attributes['pageId'] ?? null, $attributes['targetPageId'] ?? null,
        $attributes['clearConditionallyHiddenFields'] ?? true,
        $attributes['policy'] ?? \verbb\formie\enums\SubmissionPolicy::STANDARD,
        $attributes['requestToken'] ?? null,
    );
}

function runSubmissionCommand(SubmissionCommand $command): SubmissionResponse
{
    $outcome = Formie::$plugin->getSubmissionProcessor()->executeCommand($command);
    return SubmissionResponse::fromOutcome($outcome, $command->form, $command->submission, $command);
}

function runManagedSubmission(\verbb\formie\models\ManagedSubmissionRequest $input, \verbb\formie\enums\SubmissionAuthorityType $authority = \verbb\formie\enums\SubmissionAuthorityType::VISITOR): \verbb\formie\models\SubmissionExecutionResult
{
    $form = Formie::$plugin->getSubmissionRequests()->requireFormByHandle($input->handle, $input->siteId);
    $input->requestToken ??= Formie::$plugin->getSubmissionGuards()->issueRequestToken($form);
    $request = Craft::$app->getRequest();
    $request->setBodyParams($request->getBodyParams() + ['formStartedAt' => (string)((int)(microtime(true) * 1000) - 60000), 'formieHoneypot' => '']);
    if ($input->submissionId && $input->expectedVersion === null) {
        $input->expectedVersion = (int)(new \craft\db\Query())->select('stateVersion')->from(\verbb\formie\helpers\Table::FORMIE_SUBMISSIONS)->where(['id' => $input->submissionId])->scalar();
    }
    return Formie::$plugin->getSubmissionRequests()->executeManaged($input, $authority);
}

function runClientSubmission(\verbb\formie\client\models\SubmitRequest $input): \verbb\formie\client\models\SubmitResult
{
    return Formie::$plugin->getSubmissionRequests()->execute($input, \verbb\formie\enums\SubmissionAuthorityType::VISITOR);
}

function createGuardTestForm(): \verbb\formie\elements\Form
{
    $form = new \verbb\formie\elements\Form(['title' => 'Guard Test', 'handle' => 'guard' . uniqid(), 'uid' => \verbb\formie\helpers\StringHelper::UUID()]);
    $form->setNotifications([]);
    return $form;
}

function withSubmissionGuardsPostContext(callable $callback, array $bodyParams = []): mixed
{
    return \Tests\Support\WebRequestTestHelper::withWebRequestContext($callback, ['method' => 'POST', 'bodyParams' => $bodyParams + [
        'formStartedAt' => (string)((int)(microtime(true) * 1000) - 10000), 'formieHoneypot' => '',
    ]]);
}

function continuitySubmission(): array
{
    $form = formie()->form()->singleLineTextField('message')->create();
    $submission = new \verbb\formie\elements\Submission();
    $submission->setForm($form);
    $submission->isIncomplete = true;
    $submission->setFieldValue('message', 'Canonical content');
    Craft::$app->getElements()->saveElement($submission, false);
    return [$form, $submission];
}
