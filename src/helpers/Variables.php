<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\ElementFieldInterface;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ParseVariablesEvent;
use verbb\formie\events\RegisterVariablesEvent;
use verbb\formie\fields\data\MultiOptionsFieldData;
use verbb\formie\fields\data\SingleOptionFieldData;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\elements\User;
use craft\fields\BaseRelationField;
use craft\helpers\App;
use craft\models\Site;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\web\IdentityInterface;

use DateTime;
use DateTimeZone;
use Throwable;
use Exception;

class Variables
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_VARIABLES = 'registerVariables';
    public const EVENT_PARSE_VARIABLES = 'parseVariables';


    // Static Methods
    // =========================================================================

    public static function getFormVariables(): array
    {
        return [
            ['label' => Craft::t('formie', 'Form'), 'heading' => true],
            ['label' => Craft::t('formie', 'All Form Fields'), 'value' => '{allFields}'],
            ['label' => Craft::t('formie', 'All Non Empty Fields'), 'value' => '{allContentFields}'],
            ['label' => Craft::t('formie', 'All Visible Fields'), 'value' => '{allVisibleFields}'],
            ['label' => Craft::t('formie', 'Form Name'), 'value' => '{formName}'],
            ['label' => Craft::t('formie', 'Submission CP URL'), 'value' => '{submissionUrl}'],
            ['label' => Craft::t('formie', 'Submission ID'), 'value' => '{submissionId}'],
            ['label' => Craft::t('formie', 'Submission UID'), 'value' => '{submissionUid}'],
            ['label' => Craft::t('formie', 'Submission Date'), 'value' => '{submissionDate}'],
        ];
    }

    public static function getEmailVariables(): array
    {
        return [
            ['label' => Craft::t('formie', 'Email'), 'heading' => true],
            ['label' => Craft::t('formie', 'User Email'), 'value' => '{userEmail}'],
            ['label' => Craft::t('formie', 'System Email'), 'value' => '{systemEmail}'],
            ['label' => Craft::t('formie', 'System Reply-To'), 'value' => '{systemReplyTo}'],
        ];
    }

    public static function getGeneralVariables(): array
    {
        return [
            ['label' => Craft::t('formie', 'General'), 'heading' => true],
            ['label' => Craft::t('formie', 'System Name'), 'value' => '{systemName}'],
            ['label' => Craft::t('formie', 'Site Name'), 'value' => '{siteName}'],
            ['label' => Craft::t('formie', 'Site Handle'), 'value' => '{siteHandle}'],
            ['label' => Craft::t('formie', 'Timestamp'), 'value' => '{timestamp}'],
            ['label' => Craft::t('formie', 'Date (mm/dd/yyyy)'), 'value' => '{dateUs}'],
            ['label' => Craft::t('formie', 'Date (dd/mm/yyyy)'), 'value' => '{dateInt}'],
            ['label' => Craft::t('formie', 'Time (12h)'), 'value' => '{time12}'],
            ['label' => Craft::t('formie', 'Time (24h)'), 'value' => '{time24}'],
        ];
    }

    public static function getUsersVariables(): array
    {
        return [
            ['label' => Craft::t('formie', 'Users'), 'heading' => true],
            ['label' => Craft::t('formie', 'User IP Address'), 'value' => '{userIp}'],
            ['label' => Craft::t('formie', 'User ID'), 'value' => '{userId}'],
            ['label' => Craft::t('formie', 'User Email'), 'value' => '{userEmail}'],
            ['label' => Craft::t('formie', 'Username'), 'value' => '{username}'],
            ['label' => Craft::t('formie', 'User Full Name'), 'value' => '{userFullName}'],
            ['label' => Craft::t('formie', 'User First Name'), 'value' => '{userFirstName}'],
            ['label' => Craft::t('formie', 'User Last Name'), 'value' => '{userLastName}'],
        ];
    }

    public static function getVariables(): array
    {
        return array_merge(
            static::getFormVariables(),
            static::getGeneralVariables(),
            static::getEmailVariables(),
            static::getUsersVariables()
        );
    }

    public static function getVariablesArray(): array
    {
        $variables = [
            'form' => static::getFormVariables(),
            'general' => static::getGeneralVariables(),
            'email' => static::getEmailVariables(),
            'users' => static::getUsersVariables(),
        ];

        // Allow plugins to modify the variables
        $event = new RegisterVariablesEvent([
            'variables' => $variables,
        ]);
        Event::trigger(self::class, self::EVENT_REGISTER_VARIABLES, $event);

        return $event->variables;
    }

    public static function getParsedValue(mixed $value, Submission $submission = null, Form $form = null, Notification $notification = null, bool $includeSummary = false, bool $rawValue = false): ?string
    {
        $originalValue = $value;

        if ($value === null || $value === '' || is_array($value)) {
            return '';
        }

        // Check if we need to even process any variables for this value
        if (is_string($value) && !str_contains($value, '{')) {
            return $value;
        }

        // Convert any fields defined as `{field:number}` to `{field.number}` to be compatible with Twig
        if (is_string($value)) {
            $value = preg_replace_callback('/\{field:([^\}]+)\}/', function($matches) {
                return '{field.' . $matches[1] . '}';
            }, $value);
        }

        // Try and get the form from the submission if not set
        if ($submission && !$form) {
            $form = $submission->form;
        }

        // Parse aliases and env variables
        $value = App::parseEnv($value);

        // Use a cache key based on the submission, or for unsaved submissions - the formId.
        // Be sure to prefix things by what they are to prevent ID collision between form/submission elements.
        // This helps to only cache it per-submission, when being run in queues.
        $cacheKey = mt_rand();

        if ($submission && $submission->id) {
            $cacheKey = 'submission' . $submission->id;
        } else if ($form && $form->id) {
            $cacheKey = 'form' . $form->id;
        }

        // Check to see if we have these already calculated for the request and submission
        // Just saves a good bunch of calculating values like looping through fields
        if (!Formie::$plugin->getRenderCache()->getGlobalVariables($cacheKey)) {
            // Get the user making the submission. Some checks to do to get it right.
            $currentUser = self::_getCurrentUser($submission);

            // User Info
            $userId = $currentUser->id ?? '';
            $userEmail = $currentUser->email ?? '';
            $username = $currentUser->username ?? '';
            $userFullName = $currentUser->fullName ?? '';
            $userFirstName = $currentUser->firstName ?? '';
            $userLastName = $currentUser->lastName ?? '';
            $userIp = $submission->ipAddress ?? '';

            // Site Info
            $site = self::_getSite($submission);
            $siteId = $site->id ?? '';
            $siteName = $site->name ?? '';
            $siteHandle = $site->handle ?? '';

            // Force-set the current site. This will either be the current site the user is on for front-end requests,
            // or the site saved against the submission. When being run from a queue there's no concept of the 'site'
            // we're currently on, so using the `siteId` against the submission is the only way to determine that.
            if ($site) {
                Craft::$app->getSites()->setCurrentSite($site);
            }

            // Date Info
            $timeZone = Craft::$app->getTimeZone();
            $now = new DateTime('now', new DateTimeZone($timeZone));
            $dateCreated = $submission->dateCreated ?? null;

            // Form Info
            $formName = $form->title ?? '';

            $variables = [
                'formName' => $formName,
                'submissionUrl' => $submission?->getCpEditUrl() ?? '',
                'submissionId' => $submission->id ?? null,
                'submissionUid' => $submission->uid ?? null,
                'submissionDate' => $dateCreated?->format('Y-m-d H:i:s'),
                'submissionSite' => $submission?->siteId ?? null,

                // Keep this blank to fall back to Craft defaults, which resolve site overrides
                'systemEmail' => self::_getSystemEmailSetting('fromEmail'),
                'systemReplyTo' => self::_getSystemEmailSetting('replyToEmail'),
                'systemName' => self::_getSystemEmailSetting('fromName'),
                'craft' => new CraftVariable(),
                'currentSite' => $site,
                'currentUser' => $currentUser,
                'siteName' => $siteName,
                'siteUrl' => $site->getBaseUrl(),
                'siteId' => $siteId,
                'siteHandle' => $siteHandle,

                'timestamp' => $now->format('Y-m-d H:i:s'),
                'dateUs' => $now->format('m/d/Y'),
                'dateInt' => $now->format('d/m/Y'),
                'time12' => $now->format('h:i a'),
                'time24' => $now->format('H:i'),

                'userIp' => $userIp,
                'userId' => $userId,
                'userEmail' => $userEmail,
                'username' => $username,
                'userFullName' => $userFullName,
                'userFirstName' => $userFirstName,
                'userLastName' => $userLastName,
            ];

            // Add support for all global sets
            foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
                $variables[$globalSet->handle] = $globalSet;
            }

            // Cache variables in-memory for better performance next parse
            Formie::$plugin->getRenderCache()->setGlobalVariables($cacheKey, $variables);
        }

        $fieldVariables[] = self::getParsedFieldValues($form, $submission, $notification, $rawValue);

        if ($includeSummary) {
            // Populate a collection of fields for "all", "visible" and "with-content"
            $fieldVariables[] = self::getFieldsHtml($form, $notification, $submission);

            // We should also re-format the string to remove `<p>` tags from variables, which might produce invalid HTML
            // but just for these summary tags which are block-level
            $value = str_replace(['<p>{allFields}</p>'], '{allFields}', $value);
            $value = str_replace(['<p>{allContentFields}</p>'], '{allContentFields}', $value);
            $value = str_replace(['<p>{allVisibleFields}</p>'], '{allVisibleFields}', $value);
        }

        // For performance
        $fieldVariables = array_merge(...$fieldVariables);

        // Save variables to a render cache for performance
        Formie::$plugin->getRenderCache()->setFieldVariables($cacheKey, $fieldVariables);
        $variables = Formie::$plugin->getRenderCache()->getVariables($cacheKey);

        // Parse each variable on it's own to handle .env vars
        foreach ($variables as $key => $variable) {
            if (is_string($variable)) {
                $variables[$key] = App::parseEnv($variable);
            }
        }

        // Allow plugins to modify the variables
        $event = new ParseVariablesEvent([
            'submission' => $submission,
            'form' => $form,
            'notification' => $notification,
            'value' => $value,
            'variables' => $variables,
        ]);
        Event::trigger(self::class, self::EVENT_PARSE_VARIABLES, $event);

        // Try to parse submission + extra variables
        try {
            return Formie::$plugin->getTemplates()->renderObjectTemplate($value, $submission, $event->variables);
        } catch (Throwable $e) {
            Formie::error('Failed to render dynamic string “{value}”. Template error: “{message}” {file}:{line}', [
                'value' => $originalValue,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return '';
        }
    }

    public static function getFieldsHtml(?Form $form, ?Notification $notification, ?Submission $submission): array
    {
        $data = [
            'allFields' => '',
            'allContentFields' => '',
            'allVisibleFields' => '',
        ];

        if (!$form || !$submission) {
            return $data;
        }

        // If a specific notification isn't passed in, use a new instance of one. This is for times where we don't really mind
        // _which_ notification is used, like when a submission is made on the front-end, with a submit message.
        if (!$notification) {
            $notification = new Notification();
        }

        $renderOptions = [
            'form' => $form,
            'notification' => $notification,
            'submission' => $submission,
            'fields' => [],
        ];

        // Send through any fields that should be rendered
        foreach ($form->getFields() as $field) {
            if (!$field->includeInEmail) {
                continue;
            }

            if ($field->isConditionallyHidden($submission)) {
                continue;
            }

            $renderOptions['fields'][] = $field;
        }

        // Let the email templates take over to handle the rendering
        $data['allFields'] = $notification->renderTemplate('all-fields', $renderOptions);
        $data['allContentFields'] = $notification->renderTemplate('all-content-fields', $renderOptions);
        $data['allVisibleFields'] = $notification->renderTemplate('all-visible-fields', $renderOptions);

        return $data;
    }

    public static function getParsedFieldValues(?Form $form, ?Submission $submission, ?Notification $notification, bool $rawValue = false): array
    {
        $values = [];

        if (!$form || !$submission) {
            return $values;
        }

        // There are some circumstances where we're rendering email content, but not for an email. 
        // Slack integration rich text is one of them, there are likely more.
        $notification = $notification ?? new Notification();

        foreach ($submission->getFields() as $field) {
            $value = $submission->getFieldValue($field->fieldKey);

            if ($fieldValue = self::getParsedFieldValue($field, $value, $submission, $notification, $rawValue)) {
                $values['field.' . $field->fieldKey] = $fieldValue;
            }
        }

        return ArrayHelper::expand($values);
    }

    public static function getParsedFieldValue(FieldInterface $field, mixed $value, Submission $submission, Notification $notification, bool $rawValue = false): mixed
    {
        if ($field->getIsCosmetic()) {
            return [];
        }

        // TODO: handle this at the field level better in Formie 4
        if ($rawValue) {
            return $field->getValueForVariableRaw($value, $submission, $notification);
        }

        return $field->getValueForVariable($value, $submission, $notification);
    }


    // Private Methods
    // =========================================================================

    private static function _getCurrentUser(?Submission $submission = null): bool|User|IdentityInterface|null
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        // If this is a front-end request, check the current user. This only really applies
        // if `useQueueForNotifications = false`.
        if ($currentUser && Craft::$app->getRequest()->getIsSiteRequest()) {
            return $currentUser;
        }

        // Is the "Collect User" enabled on the form?
        if ($submission && $submission->getUser()) {
            return $submission->getUser();
        }

        return null;
    }

    public static function _getSite(?Submission $submission): ?Site
    {
        // Get the current site, based on front-end requests first. This will fail for a queue job.
        // For front-end requests where we want to parse content, we must respect the current site.
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        if ($currentSite) {
            return $currentSite;
        }

        // Otherwise, use the siteId for the submission
        $siteId = $submission->siteId ?? null;

        if ($siteId) {
            return Craft::$app->getSites()->getSiteById($siteId);
        }

        // If all else fails, the primary site.
        return Craft::$app->getSites()->getPrimarySite();
    }

    private static function _getSystemEmailSetting(string $setting): mixed
    {
        $mail = App::mailSettings();
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        $overrides = $mail->siteOverrides[$currentSite->uid] ?? [];

        if (isset($overrides[$setting])) {
            return App::parseEnv($overrides[$setting]);
        }

        return $mail->$setting;
    }
}
