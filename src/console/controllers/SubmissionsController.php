<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;

use Throwable;

use yii\console\ExitCode;

/**
 * Manages Formie Submissions.
 */
class SubmissionsController extends Controller
{
    // Properties
    // =========================================================================

    public ?string $formId = null;
    public ?string $formHandle = null;
    public bool $spamOnly = false;
    public bool $incompleteOnly = false;
    public ?string $before = null;
    public ?string $after = null;
    public ?string $submissionId = null;
    public ?string $integration = null;
    public ?int $notificationId = null;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'delete') {
            $options[] = 'formId';
            $options[] = 'formHandle';
            $options[] = 'spamOnly';
            $options[] = 'incompleteOnly';
            $options[] = 'before';
            $options[] = 'after';
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
     * Delete Formie submissions.
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

            if ($this->before) {
                $query->before(DateTimeHelper::toDateTime($this->before));
            }

            if ($this->after) {
                $query->after(DateTimeHelper::toDateTime($this->after));
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
     * Run an integration on a Formie submission.
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

        if (!$integration::supportsPayloadSending()) {
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
     * Send an email notification on a Formie submission.
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
