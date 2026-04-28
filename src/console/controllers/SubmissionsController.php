<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\base\SingleNestedFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Table;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;

use Throwable;

use yii\console\ExitCode;
use yii\db\Query;

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
    public bool $dryRun = false;


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

        if ($actionID === 'repair-synced-subfields') {
            $options[] = 'formId';
            $options[] = 'formHandle';
            $options[] = 'dryRun';
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
     * Repair nested submission content for synced fields with mismatched subfield UIDs.
     */
    public function actionRepairSyncedSubfields(): int
    {
        $formIds = null;

        if ($this->formId !== null) {
            $formIds = explode(',', $this->formId);
        }

        if ($this->formHandle !== null) {
            $formHandles = explode(',', $this->formHandle);
            $formIds = Form::find()->handle($formHandles)->ids();
        }

        $forms = Form::find()->id($formIds)->all();

        if (!$forms) {
            $this->stderr('Unable to find any matching forms.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $totalChanged = 0;
        $totalMovedValues = 0;

        foreach ($forms as $form) {
            $repairMaps = $this->_getSyncedSubfieldRepairMaps($form);

            if (!$repairMaps) {
                $this->stdout("No synced subfield UID mappings found for form \"{$form->title}\"." . PHP_EOL, Console::FG_YELLOW);

                continue;
            }

            $submissions = (new Query())
                ->from(Table::FORMIE_SUBMISSIONS)
                ->where(['formId' => $form->id])
                ->all();

            $changed = 0;
            $movedValues = 0;

            foreach ($submissions as $submission) {
                $content = Json::decodeIfJson($submission['content'] ?? []);

                if (!is_array($content)) {
                    continue;
                }

                $contentChanged = false;

                foreach ($repairMaps as $parentUid => $repairMap) {
                    if (!isset($content[$parentUid]) || !is_array($content[$parentUid])) {
                        continue;
                    }

                    foreach ($repairMap as $staleUid => $currentUid) {
                        if (!array_key_exists($staleUid, $content[$parentUid])) {
                            continue;
                        }

                        $staleValue = $content[$parentUid][$staleUid];
                        $currentValue = $content[$parentUid][$currentUid] ?? null;

                        if (!$this->_isEmptyNestedValue($currentValue)) {
                            continue;
                        }

                        $content[$parentUid][$currentUid] = $staleValue;
                        unset($content[$parentUid][$staleUid]);

                        $contentChanged = true;
                        $movedValues++;
                    }
                }

                if ($contentChanged) {
                    $changed++;

                    if (!$this->dryRun) {
                        Db::update(Table::FORMIE_SUBMISSIONS, ['content' => $content], ['id' => $submission['id']]);
                    }
                }
            }

            $totalChanged += $changed;
            $totalMovedValues += $movedValues;

            $action = $this->dryRun ? 'Would repair' : 'Repaired';
            $this->stdout("{$action} {$changed} submissions for form \"{$form->title}\" ({$movedValues} values)." . PHP_EOL, Console::FG_GREEN);
        }

        $action = $this->dryRun ? 'Would repair' : 'Repaired';
        $this->stdout("{$action} {$totalChanged} submissions total ({$totalMovedValues} values)." . PHP_EOL, Console::FG_GREEN);

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

    private function _getSyncedSubfieldRepairMaps(Form $form): array
    {
        $maps = [];

        foreach ($form->getFields() as $field) {
            if (!$field instanceof SingleNestedFieldInterface) {
                continue;
            }

            $currentSubfields = [];

            foreach ($field->getFields() as $subfield) {
                if ($subfield->handle && $subfield->uid) {
                    $currentSubfields[$subfield->handle] = [
                        'type' => get_class($subfield),
                        'uid' => $subfield->uid,
                    ];
                }
            }

            if (!$currentSubfields) {
                continue;
            }

            // Map any old/orphaned subfield UIDs for this parent field back to the current
            // subfields by handle + type. This covers synced-field drift and old nested layouts.
            $oldSubfields = (new Query())
                ->select(['handle', 'type', 'uid'])
                ->from(Table::FORMIE_FIELDS)
                ->where(['handle' => array_keys($currentSubfields)])
                ->all();

            foreach ($oldSubfields as $oldSubfield) {
                $currentSubfield = $currentSubfields[$oldSubfield['handle']] ?? null;

                if (!$currentSubfield || $oldSubfield['type'] !== $currentSubfield['type']) {
                    continue;
                }

                if ($oldSubfield['uid'] && $oldSubfield['uid'] !== $currentSubfield['uid']) {
                    $maps[$field->uid][$oldSubfield['uid']] = $currentSubfield['uid'];
                }
            }
        }

        return $maps;
    }

    private function _isEmptyNestedValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
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
