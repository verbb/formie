<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\IntegrationRerunPolicies;
use verbb\formie\helpers\IntegrationTriggerEvents;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\IntegrationResponse;
use verbb\formie\models\IntegrationTriggerRequest;
use verbb\formie\models\SubmissionRequest;

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
            $this->dispatchManualIntegration($request->integration, $request->submission);

            return;
        }

        Formie::$plugin->getIntegrations()->triggerIntegrations(
            $request->submission,
            $request->processMode,
            $request->triggerEvent,
            $request->operatorInitiated,
        );
    }

    public function dispatchFromWorkflow(
        Submission $submission,
        string $processMode,
        ?string $triggerEvent = null,
    ): void {
        $this->dispatch(new IntegrationTriggerRequest([
            'source' => self::SOURCE_WORKFLOW,
            'submission' => $submission,
            'processMode' => $processMode,
            'triggerEvent' => $triggerEvent ?? IntegrationTriggerEvents::resolveFromProcessMode($processMode),
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
            'processMode' => SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING,
            'triggerEvent' => IntegrationTriggerEvents::CP_SAVE,
        ]));
    }

    public function dispatchSpamUnmark(
        Submission $submission,
        bool $sendNotifications,
        bool $triggerIntegrations,
    ): void {
        if ($sendNotifications) {
            Formie::$plugin->getSubmissions()->sendNotifications($submission);
        }

        if (!$triggerIntegrations) {
            return;
        }

        $this->dispatch(new IntegrationTriggerRequest([
            'source' => self::SOURCE_SPAM_UNMARK,
            'submission' => $submission,
            'processMode' => SubmissionWorkflow::PROCESS_MODE_SUBMIT,
            'triggerEvent' => IntegrationTriggerEvents::UNMARK_SPAM,
            'operatorInitiated' => true,
        ]));
    }

    public function dispatchCpSubmissionFollowUps(Submission $submission, SubmissionRequest $submissionRequest): void
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsCpRequest() || $request->getIsConsoleRequest()) {
            return;
        }

        if ($submissionRequest->processMode !== SubmissionWorkflow::PROCESS_MODE_EDIT_EXISTING) {
            return;
        }

        // Operator toggles on the CP submission edit form when unmarking spam.
        if (!$submission->hasSpamChanged(true, false)) {
            return;
        }

        $this->dispatchSpamUnmark(
            $submission,
            StringHelper::toBoolean($request->getBodyParam('sendNotifications')),
            StringHelper::toBoolean($request->getBodyParam('triggerIntegrations')),
        );
    }

    public function dispatchManualIntegration(Integration $integration, Submission $submission): bool|IntegrationResponse
    {
        $integration->populateContext();
        $integration->context['triggerEvent'] = IntegrationTriggerEvents::MANUAL;
        $integration->context['operatorInitiated'] = true;

        return Formie::$plugin->getIntegrations()->sendIntegrationPayload($integration, $submission);
    }
}
