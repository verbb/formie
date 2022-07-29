<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\helpers\Db;

use Throwable;

use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class SubmissionsController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The form ID(s) to delete submissions from. Can be set to multiple comma-separated IDs.
     */
    public ?int $formId = null;

    /**
     * @var string|null The form handle(s) to delete submissions from. Can be set to multiple comma-separated handles.
     */
    public ?string $formHandle = null;

    /**
     * @var bool Whether to delete only spam submissions.
     */
    public bool $spamOnly = false;

    /**
     * @var bool Whether to delete only incomplete submissions.
     */
    public bool $incompleteOnly = false;

    /**
     * @var int|null The submission ID(s) to use data for. Can be set to multiple comma-separated IDs.
     */
    public ?int $submissionId = null;

    /**
     * @var string|null The handle of the integration to trigger.
     */
    public ?string $integration = null;

    /**
     * @var int|null The ID of the notification to trigger.
     */
    public ?int $notificationId = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'delete') {
            $options[] = 'formId';
            $options[] = 'formHandle';
            $options[] = 'spamOnly';
            $options[] = 'incompleteOnly';
        }

        if ($actionID === 'run-integration') {
            $options[] = 'submissionId';
            $options[] = 'integration';
        }

        if ($actionID === 'send-notification') {
            $options[] = 'submissionId';
            $options[] = 'notificationId';
        }

        return $options;
    }

    /**
     * Deletes all submissions.
     *
     * @return int
     * @throws Throwable
     */
    public function actionDelete(): int
    {
        $formIds = null;

        if ($this->formId !== null) {
            $formIds = explode(',', $this->formId);
        }

        if ($this->formHandle !== null) {
            $formHandle = explode(',', $this->formHandle);

            $formIds = Form::find()->handle($formHandle)->ids();
        }

        if (!$this->formId && !$this->formHandle) {
            $this->stderr('You must provide either a --form-id or --form-handle option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$formIds) {
            $this->stderr('Unable to find any matching forms.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($formIds as $formId) {
            $query = Submission::find()->formId($formId);

            // Target spam submissions by default
            if ($this->spamOnly) {
                $query->isSpam(true);
            } else {
                $query->isSpam(null);
            }

            // Target incomplete submissions by default
            if ($this->incompleteOnly) {
                $query->isIncomplete(true);
            } else {
                $query->isIncomplete(null);
            }

            $count = (int)$query->count();

            if ($count === 0) {
                $this->stdout('No submissions exist for that criteria.' . PHP_EOL, Console::FG_YELLOW);

                continue;
            }

            $elementsText = $count === 1 ? 'submission' : 'submissions';
            $this->stdout("Deleting {$count} {$elementsText} for form #{$formId} ..." . PHP_EOL, Console::FG_YELLOW);

            $elementsService = Craft::$app->getElements();

            foreach (Db::each($query) as $element) {
                $elementsService->deleteElement($element);

                $this->stdout("Deleted submission #{$element->id} ..." . PHP_EOL, Console::FG_GREEN);
            }
        }

        return ExitCode::OK;
    }

    /**
     * Triggers an integration for a submission
     *
     * @return int
     * @throws Throwable
     */
    public function actionRunIntegration(): int
    {
        if (!$this->submissionId) {
            $this->stderr('You must provide an --submission-id option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$this->integration) {
            $this->stderr('You must provide an --integration option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($this->integration);

        if (!$integration) {
            $this->stderr('Unable to find matching integration.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$integration->supportsPayloadSending()) {
            $this->stderr('Integration does not support payload sending.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $submissionIds = explode(',', $this->submissionId);
        $submissions = Submission::find()->id($submissionIds)->all();

        if (!$submissions) {
            $this->stderr('Unable to find any matching submissions.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($submissions as $submission) {
            // Ensure that the integration settings are prepped from the form settings
            $form = $submission->getForm();
            $formSettings = $form->settings->integrations[$this->integration] ?? [];
            $integration->setAttributes($formSettings, false);

            Formie::$plugin->getSubmissions()->sendIntegrationPayload($integration, $submission);

            $this->stdout("Triggered integration for submission #{$submission->id} ..." . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }

    /**
     * Sends a noification for a submission
     *
     * @return int
     * @throws Throwable
     */
    public function actionSendNotification(): int
    {
        if (!$this->submissionId) {
            $this->stderr('You must provide an --submission-id option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$this->notificationId) {
            $this->stderr('You must provide an --notification option.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $notification = Formie::$plugin->getNotifications()->getNotificationById($this->notificationId);

        if (!$notification) {
            $this->stderr('Unable to find matching notification.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $submissionIds = explode(',', $this->submissionId);
        $submissions = Submission::find()->id($submissionIds)->all();

        if (!$submissions) {
            $this->stderr('Unable to find any matching submissions.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($submissions as $submission) {
            Formie::$plugin->getSubmissions()->sendNotificationEmail($notification, $submission);

            $this->stdout("Sent notification for submission #{$submission->id} ..." . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
