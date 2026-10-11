<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubmissionAuthorityType;
use verbb\formie\enums\SubmissionOperation;
use verbb\formie\helpers\IntegrationRerunPolicies;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationResult;
use verbb\formie\models\IntegrationTriggerRequest;
use verbb\formie\models\SubmissionAuthority;

use Craft;

use yii\base\Component;

class IntegrationTriggers extends Component
{
    // Constants
    // =========================================================================

    public const SOURCE_WORKFLOW = 'workflow';
    public const SOURCE_CP_ELEMENT_SAVE = 'cpElementSave';
    public const SOURCE_SPAM_UNMARK = 'spamUnmark';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_CLI = 'cli';


    // Public Methods
    // =========================================================================

    public function dispatch(IntegrationTriggerRequest $request): void
    {
        if ($request->integration !== null) {
            $this->_dispatchManualIntegration($request->integration, $request->submission, $request->authority ?? $this->_operatorAuthority($request->submission, 'manual'));

            return;
        }

        Formie::$plugin->getIntegrations()->triggerIntegrations(
            $request->submission,
            $request->operation,
            $request->triggerEvent,
            $request->operatorInitiated,
            $request->authority,
        );
    }

    public function dispatchFromWorkflow(
        Submission $submission,
        SubmissionOperation $operation,
        ?string $triggerEvent = null,
    ): void {
        $this->dispatchFromWorkflowWithAuthority($submission, $operation, $triggerEvent, null);
    }

    public function dispatchFromWorkflowWithAuthority(
        Submission $submission,
        SubmissionOperation $operation,
        ?string $triggerEvent,
        ?SubmissionAuthority $authority,
    ): void {
        $this->dispatch(new IntegrationTriggerRequest([
            'source' => self::SOURCE_WORKFLOW,
            'submission' => $submission,
            'operation' => $operation,
            'triggerEvent' => $triggerEvent ?? IntegrationTriggerEvents::resolveFromOperation($operation),
            'authority' => $authority,
        ]));
    }

    public function dispatchCpElementSave(Submission $submission): void
    {
        if ($submission->isIncomplete || $submission->isSpam) {
            return;
        }

        $form = $submission->getForm();

        if (!$form || !IntegrationRerunPolicies::formHasIntegrationAllowingEvent($form, IntegrationTriggerEvents::CP_SAVE)) {
            return;
        }

        $this->dispatch(new IntegrationTriggerRequest([
            'source' => self::SOURCE_CP_ELEMENT_SAVE,
            'submission' => $submission,
            'operation' => SubmissionOperation::REVISE,
            'triggerEvent' => IntegrationTriggerEvents::CP_SAVE,
            'authority' => $this->_currentAuthority($submission, SubmissionAuthorityType::CONTROL_PANEL, 'cp-element-save'),
        ]));
    }

    public function dispatchSpamUnmark(
        Submission $submission,
        bool $sendNotifications,
        bool $triggerIntegrations,
    ): void {
        $this->dispatchSpamUnmarkWithAuthority($submission, $sendNotifications, $triggerIntegrations, $this->_operatorAuthority($submission, 'spam-unmark'));
    }

    public function dispatchSpamUnmarkWithAuthority(
        Submission $submission,
        bool $sendNotifications,
        bool $triggerIntegrations,
        SubmissionAuthority $authority,
    ): void {
        if ($sendNotifications) {
            Formie::$plugin->getNotifications()->sendNotifications($submission);
        }

        if (!$triggerIntegrations) {
            return;
        }

        $this->dispatch(new IntegrationTriggerRequest([
            'source' => self::SOURCE_SPAM_UNMARK,
            'submission' => $submission,
            'operation' => SubmissionOperation::SUBMIT,
            'triggerEvent' => IntegrationTriggerEvents::UNMARK_SPAM,
            'operatorInitiated' => true,
            'authority' => $authority,
        ]));
    }

    public function forceIntegration(Integration $integration, Submission $submission, string $reason): IntegrationResult
    {
        $authority = $this->_operatorAuthority($submission, 'force');

        return Formie::$plugin->getIntegrationRunner()->runIntegration($integration, $submission, StringHelper::UUID(), 'synchronous', [
            'triggerEvent' => IntegrationTriggerEvents::MANUAL,
            'operatorInitiated' => true,
            'force' => true,
            'reason' => $reason,
            'authorityType' => $authority->type->value,
            'authorityScope' => $authority->scope,
            'actorId' => $authority->actorId,
        ]);
    }

    public function dispatchManualIntegration(Integration $integration, Submission $submission): IntegrationResult
    {
        return $this->_dispatchManualIntegration($integration, $submission, $this->_operatorAuthority($submission, 'manual'));
    }


    // Private Methods
    // =========================================================================

    private function _dispatchManualIntegration(Integration $integration, Submission $submission, SubmissionAuthority $authority): IntegrationResult
    {
        return Formie::$plugin->getIntegrationRunner()->runIntegration($integration, $submission, StringHelper::UUID(), 'synchronous', [
            'triggerEvent' => IntegrationTriggerEvents::MANUAL,
            'operatorInitiated' => true,
            'authorityType' => $authority->type->value,
            'authorityScope' => $authority->scope,
            'actorId' => $authority->actorId,
        ]);
    }

    private function _operatorAuthority(Submission $submission, string $scope): SubmissionAuthority
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest()) {
            return $this->_currentAuthority($submission, SubmissionAuthorityType::TRUSTED_INTERNAL, 'cli:' . $scope);
        }

        $type = $request->getIsCpRequest() ? SubmissionAuthorityType::CONTROL_PANEL : SubmissionAuthorityType::VISITOR;

        return $this->_currentAuthority($submission, $type, $scope);
    }

    private function _currentAuthority(Submission $submission, SubmissionAuthorityType $type, string $scope): SubmissionAuthority
    {
        $actorId = in_array($type, [SubmissionAuthorityType::VISITOR, SubmissionAuthorityType::CONTROL_PANEL], true)
            ? Craft::$app->getUser()->getId() : null;

        return new SubmissionAuthority(
            $type,
            (int)$submission->formId,
            $submission->id ? (int)$submission->id : null,
            $scope,
            $actorId ? (int)$actorId : null,
        );
    }
}
