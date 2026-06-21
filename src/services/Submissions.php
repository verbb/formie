<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FixedParentFieldInterface;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\base\PreviewableFieldInterface;
use verbb\formie\base\SearchableFieldInterface;
use verbb\formie\base\SortableFieldInterface;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\deprecations\SubmissionsDeprecations;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\PruneSubmissionEvent;
use verbb\formie\fields\values\AddressFieldValue;
use verbb\formie\fields\values\MultiOptionFieldValue;
use verbb\formie\fields\values\NameFieldValue;
use verbb\formie\fields as formiefields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\DataRetentionHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\helpers\Variables;
use verbb\formie\jobs\UpdateSubmissionContent;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Notification;
use verbb\formie\models\Settings;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\db\ElementQuery;
use craft\elements\Asset;
use craft\elements\User;
use craft\events\DefineSourceSortOptionsEvent;
use craft\events\DefineSourceTableAttributesEvent;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\IndexKeywordsEvent;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\Search as SearchHelper;
use craft\helpers\Session;
use craft\helpers\UrlHelper;

use yii\base\Event;
use yii\base\Component;

use DateInterval;
use DateTime;
use DateTimeZone;
use Throwable;
use Exception;

use Faker;
use libphonenumber\PhoneNumberUtil;

class Submissions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_AFTER_PRUNE_SUBMISSION = 'afterPruneSubmission';

    // Legacy - remove at next breakpoint.
    public const EVENT_BEFORE_SEND_NOTIFICATION = 'beforeSendNotification';
    public const EVENT_BEFORE_TRIGGER_INTEGRATION = 'beforeTriggerIntegration';


    // Traits
    // =========================================================================
    
    use SubmissionsDeprecations;


    // Properties
    // =========================================================================

    private array $_indexedSearchRows = [];


    // Public Methods
    // =========================================================================

    public function getSubmissionById(int $id, ?string $siteId = '*', array $criteria = []): ?Submission
    {
        // Always included incomplete or spam submissions
        $criteria['isIncomplete'] = null;
        $criteria['isSpam'] = null;

        return Craft::$app->getElements()->getElementById($id, Submission::class, $siteId, $criteria);
    }

    public function applyCpRequestAttributes(Submission $submission): void
    {
        $request = Craft::$app->getRequest();

        if (!$request->getIsCpRequest()) {
            return;
        }

        if (($title = $request->getBodyParam('title')) !== null) {
            $submission->title = (string)$title;
        }

        if (($statusId = $request->getBodyParam('statusId')) !== null) {
            $status = Formie::$plugin->getStatuses()->getStatusById((int)$statusId);
            $form = $submission->getForm();

            if ($status && $form && Formie::$plugin->getFormGroupPolicy()->isStatusAllowed($form, (int)$status->id)) {
                $submission->setStatus($status);
            }
        }

        if ($request->getBodyParam('isSpam') !== null) {
            $submission->isSpam = StringHelper::toBoolean((string)$request->getBodyParam('isSpam'));
        }

        if (($markAsComplete = $request->getBodyParam('markAsComplete')) !== null
            && StringHelper::toBoolean((string)$markAsComplete)
        ) {
            $submission->isIncomplete = false;
        }
    }

    public function defineSourceTableAttributes(DefineSourceTableAttributesEvent $event): void
    {
        if ($event->elementType !== Submission::class) {
            return;
        }

        if (preg_match('/^form:(\d+)$/', $event->source, $matches) && ($fields = Formie::$plugin->getFields()->getAllFieldsForForm($matches[1]))) {
            foreach ($fields as $field) {
                if ($field instanceof PreviewableFieldInterface) {
                    $event->attributes["field:{$field->uid}"] = ['label' => $field->label];
                }
            }
        }
    }

    public function defineSourceSortOptions(DefineSourceSortOptionsEvent $event): void
    {
        if ($event->elementType !== Submission::class) {
            return;
        }

        if (preg_match('/^form:(\d+)$/', $event->source, $matches) && ($fields = Formie::$plugin->getFields()->getAllFieldsForForm($matches[1]))) {
            foreach ($fields as $field) {
                if ($field instanceof SortableFieldInterface) {
                    $event->sortOptions["field:{$field->uid}"] = $field->getSortOption();
                }
            }
        }
    }

    public function beforeIndexKeywords(IndexKeywordsEvent $event)
    {
        if (!($event->element instanceof Submission)) {
            return;
        }

        $submission = $event->element;
        $cacheKey = "{$submission->id}:{$submission->siteId}";

        // Craft can index immediately or via queued jobs. Populate Formie field-index rows
        // the first time this submission is indexed in the current request.
        if (!isset($this->_indexedSearchRows[$cacheKey])) {
            $this->_indexedSearchRows[$cacheKey] = true;
            $this->indexSubmissionFieldRows($submission);
        }

        // No need to filter Craft's own element-attribute indexing here.
        // Submission field values are indexed separately via `indexSubmissionFieldRows()`.
    }

    public function clearSubmissionIndexRows(Submission $submission): void
    {
        $fieldIds = [];

        foreach ($submission->getFields() as $field) {
            if ($field->id) {
                $fieldIds[] = (int)$field->id;
            }
        }

        if (!$fieldIds) {
            return;
        }

        Db::delete(CraftTable::SEARCHINDEX, [
            'elementId' => $submission->id,
            'siteId' => $submission->siteId,
            'attribute' => 'field',
            'fieldId' => $fieldIds,
        ]);
    }

    public function indexSubmissionFieldRows(Submission $submission): void
    {
        $this->clearSubmissionIndexRows($submission);

        $site = $submission->getSite();
        $db = Craft::$app->getDb();

        foreach ($submission->getFields() as $field) {
            if (!($field instanceof SearchableFieldInterface) || !$field->isSearchableField()) {
                continue;
            }

            if (!$field->id || $field->getIsCosmetic()) {
                continue;
            }

            try {
                $fieldValue = $submission->getFieldValue($field->handle);
                $keywords = $field->getSearchKeywords($fieldValue, $submission);
            } catch (Throwable) {
                continue;
            }

            $keywords = SearchHelper::normalizeKeywords($keywords, [], true, $site->language);

            if ($keywords === '') {
                continue;
            }

            $keywords = " $keywords ";
            $columns = [
                'elementId' => $submission->id,
                'attribute' => 'field',
                'fieldId' => (int)$field->id,
                'siteId' => $site->id,
                'keywords' => $keywords,
            ];
            $updateColumns = ['keywords' => $keywords];

            if ($db->getIsPgsql()) {
                $columns['keywords_vector'] = $keywords;
                $updateColumns['keywords_vector'] = $keywords;
            }

            $db->createCommand()->upsert(CraftTable::SEARCHINDEX, $columns, $updateColumns)->execute();
        }
    }

    public function pruneIncompleteSubmissions($consoleInstance = null): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if ($settings->maxIncompleteSubmissionAge <= 0) {
            return;
        }

        $interval = new DateInterval("P{$settings->maxIncompleteSubmissionAge}D");
        $date = new DateTime();
        $date->sub($interval);

        $submissions = Submission::find()
            ->isIncomplete(true)
            // Don't use `Db::prepareDateForDb()` because the `dateCreated` param will already do that
            ->dateUpdated('< ' . $date->format('Y-m-d H:i:s'))
            ->all();

        foreach ($submissions as $submission) {
            try {
                Craft::$app->getElements()->deleteElement($submission, true);
            } catch (Throwable $e) {
                Formie::error("Failed to prune submission with ID: #{$submission->id}." . $e->getMessage());
            }
        }

        // Also check for spam pruning
        if ($settings->saveSpam) {
            if ($settings->spamLimit <= 0) {
                return;
            }

            $submissions = Submission::find()
                ->limit(null)
                ->offset($settings->spamLimit)
                ->isSpam(true)
                ->orderBy(['dateCreated' => SORT_DESC])
                ->all();

            if ($submissions && $consoleInstance) {
                $consoleInstance->stdout('Preparing to prune ' . count($submissions) . ' submissions.' . PHP_EOL, Console::FG_YELLOW);
            }

            foreach ($submissions as $submission) {
                try {
                    Craft::$app->getElements()->deleteElement($submission, true);

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Pruned spam submission with ID: #{$submission->id}." . PHP_EOL, Console::FG_GREEN);
                    }
                } catch (Throwable $e) {
                    Formie::error("Failed to prune spam submission with ID: #{$submission->id}." . $e->getMessage());

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Failed to prune spam submission with ID: #{$submission->id}. " . $e->getMessage() . PHP_EOL, Console::FG_RED);
                    }
                }
            }
        }
    }

    public function pruneDataRetentionSubmissions($consoleInstance = null): void
    {
        // Find all the forms with data retention settings
        $forms = (new Query())
            ->select(['id', 'handle', 'dataRetention', 'dataRetentionValue'])
            ->from([Table::FORMIE_FORMS])
            ->where(['not', ['dataRetention' => 'forever']])
            ->all();

        foreach ($forms as $form) {
            $dataRetention = $form['dataRetention'] ?? '';
            $dataRetentionValue = (int)$form['dataRetentionValue'];

            if ($consoleInstance) {
                $consoleInstance->stdout(Craft::t('formie', 'Starting data retention checks for form “{f}”: {d} {c}.', [
                    'f' => $form['handle'],
                    'c' => $dataRetention,
                    'd' => $dataRetentionValue,
                ]) . PHP_EOL, Console::FG_YELLOW);
            }

            $date = DataRetentionHelper::subtractInterval(new DateTime(), $dataRetention, $dataRetentionValue);

            if (!$date) {
                continue;
            }

            // Include complete/incomplete/spam submissions and all element statuses for retention checks.
            $submissionQuery = Submission::find()
                ->anyStatus()
                ->status(null)
                ->isIncomplete(null)
                ->isSpam(null)
                ->formId($form['id']);

            $totalSubmissionCount = (clone $submissionQuery)->count();

            $submissions = (clone $submissionQuery)
                // Don't use `Db::prepareDateForDb()` because the `dateCreated` param will already do that
                ->dateCreated('< ' . $date->format('Y-m-d H:i:s'))
                ->all();

            if ($consoleInstance) {
                $consoleInstance->stdout(Craft::t('formie', 'Found {c} total submissions for form “{f}”.', [
                    'c' => $totalSubmissionCount,
                    'f' => $form['handle'],
                ]) . PHP_EOL, Console::FG_YELLOW);

                if ($submissions) {
                    $consoleInstance->stdout(Craft::t('formie', 'Preparing to prune {c} submissions older than {d}.', [
                        'c' => count($submissions),
                        'd' => Db::prepareDateForDb($date),
                    ]) . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $consoleInstance->stdout(Craft::t('formie', 'No submissions found to prune older than {d}.', [
                        'd' => Db::prepareDateForDb($date),
                    ]) . PHP_EOL, Console::FG_GREEN);
                }
            }

            foreach ($submissions as $submission) {
                try {
                    Craft::$app->getElements()->deleteElement($submission, true);

                    $event = new PruneSubmissionEvent([
                        'submission' => $submission,
                    ]);
                    $this->trigger(self::EVENT_AFTER_PRUNE_SUBMISSION, $event);

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Pruned submission with ID: #{$submission->id}." . PHP_EOL, Console::FG_GREEN);
                    }
                } catch (Throwable $e) {
                    Formie::error("Failed to prune submission with ID: #{$submission->id}." . $e->getMessage());

                    if ($consoleInstance) {
                        $consoleInstance->stdout("Failed to prune submission with ID: #{$submission->id}. " . $e->getMessage() . PHP_EOL, Console::FG_RED);
                    }
                }
            }
        }
    }

    public function defineUserSubmissions(DefineUserContentSummaryEvent $event): void
    {
        $userIds = Craft::$app->getRequest()->getRequiredBodyParam('userId');

        $submissionCount = Submission::find()
            ->userId($userIds)
            ->siteId('*')
            ->unique()
            ->status(null)
            ->count();

        if ($submissionCount) {
            $event->contentSummary[] = $submissionCount == 1 ? Craft::t('formie', '1 form submission') : Craft::t('formie', '{num} form submissions', ['num' => $submissionCount]);
        }
    }

    public function deleteUserSubmissions(Event $event): void
    {
        /** @var User $user */
        $user = $event->sender;

        $submissions = Submission::find()
            ->userId($user->id)
            ->siteId('*')
            ->unique()
            ->status(null)
            ->all();

        if (!$submissions) {
            return;
        }

        // Are we transferring to another user, or just deleting?
        $inheritorOnDelete = $user->inheritorOnDelete ?? null;

        if ($inheritorOnDelete) {
            // Re-assign each submission to the new user
            Db::update(Table::FORMIE_SUBMISSIONS, ['userId' => $inheritorOnDelete->id], ['userId' => $user->id]);
        } else {
            // We just want to delete each submission - bye!
            foreach ($submissions as $submission) {
                try {
                    Craft::$app->getElements()->deleteElement($submission);
                } catch (Throwable $e) {
                    Formie::error("Failed to delete user submission with ID: #{$submission->id}." . $e->getMessage());
                }
            }
        }
    }

    public function restoreUserSubmissions(Event $event): void
    {
        /** @var User $user */
        $user = $event->sender;

        $submissions = Submission::find()
            ->userId($user->id)
            ->siteId('*')
            ->unique()
            ->status(null)
            ->trashed(true)
            ->all();

        foreach ($submissions as $submission) {
            try {
                Craft::$app->getElements()->restoreElement($submission);
            } catch (Throwable $e) {
                Formie::error("Failed to restore user submission with ID: #{$submission->id}." . $e->getMessage());
            }
        }
    }

    public function populateFakeSubmission(Submission $submission, ?Notification $notification = null): void
    {
        $fields = $submission->getFields();
        $emailRecipientHandles = $notification ? $this->_getEmailPreviewFieldHandles($notification) : [];
        $fieldContent = $this->getFakeFieldContent($fields, $emailRecipientHandles);

        $submission->setFieldValues($fieldContent);

        // Set some submission attributes as well
        $submission->id = '1234';
        $submission->uid = StringHelper::UUID();
        $submission->dateCreated = new DateTime();
        $submission->setUser(User::find()->one());
    }

    public function updateSubmissionContent(Form $form): void
    {
        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        // Check if we've moved fields in or our of Group fields. Their content needs to be re-arranged.
        // More performant if we don't spin up the queue job unless we need to
        $hasGroupField = array_filter($form->getFields(), function($field) {
            return $field instanceof formiefields\Group;
        });

        if ($hasGroupField) {
            Queue::push(new UpdateSubmissionContent([
                'formId' => $form->id,
            ]), $settings->queuePriority);
        }
    }

    public function logSpam(Submission $submission): void
    {
        $fieldValues = $submission->getSerializedFieldValues();
        $fieldValues = array_filter($fieldValues);
        $encodedValues = Json::encode($fieldValues);

        // Spam bots can post very large payloads; keep log lines bounded so PHP
        // workers do not stall while formatting diagnostic output.
        if (strlen($encodedValues) > 4096) {
            $encodedValues = substr($encodedValues, 0, 4096) . '…';
        }

        $error = Craft::t('formie', 'Submission marked as spam - “{r}” - {j}.', [
            'r' => $submission->spamReason,
            'j' => $encodedValues,
        ]);

        Formie::info($error);
    }

    public function getSubmissionPdfDownloadUrl(
        Submission $submission,
        ?int $pdfTemplateId = null,
        ?int $notificationId = null,
    ): ?string {
        if (!$submission->id) {
            return null;
        }

        if (!Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfTemplate($submission, $pdfTemplateId, $notificationId)) {
            return null;
        }

        $params = [
            'submissionId' => $submission->id,
        ];

        if ($pdfTemplateId) {
            $params['pdfTemplateId'] = $pdfTemplateId;
        }

        if ($notificationId) {
            $params['notificationId'] = $notificationId;
        }

        return UrlHelper::actionUrl('formie/submissions/download-pdf', $params);
    }

    public function generateSubmissionPdf(
        Submission $submission,
        ?int $pdfTemplateId = null,
        ?int $notificationId = null,
    ): string {
        return Formie::$plugin->getPdfTemplates()->renderSubmissionPdf(
            $submission,
            pdfTemplateId: $pdfTemplateId,
            notificationId: $notificationId,
        );
    }


    // Private Methods
    // =========================================================================

    public function getFakeFieldContent(array $fields, array $emailRecipientHandles = []): array
    {
        $fieldContent = [];

        $faker = Faker\Factory::create();

        foreach ($fields as $key => $field) {
            if (in_array($field->handle, $emailRecipientHandles, true)) {
                $fieldContent[$field->handle] = $faker->email();

                continue;
            }

            $fieldContent[$field->handle] = $field->getValueForEmailPreview($faker);
        }

        return $fieldContent;
    }

    private function _getEmailPreviewFieldHandles(Notification $notification): array
    {
        $handles = [];

        foreach (['to', 'cc', 'bcc', 'replyTo', 'from', 'sender'] as $setting) {
            $value = $notification->$setting;

            if (!is_string($value) || $value === '') {
                continue;
            }

            $handles = array_merge($handles, References::extractFieldReferenceHandles($value));
        }

        return array_values(array_unique($handles));
    }

}
