<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\RegisterVariablesEvent;
use verbb\formie\fields\formfields;
use verbb\formie\models\Notification;

use Craft;
use craft\elements\User;
use craft\fields\BaseRelationField;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;
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


    // Static Methods
    // =========================================================================

    /**
     * Returns a list of form variables.
     *
     * @return array
     */
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

    /**
     * Returns a list of email variables.
     *
     * @return array
     */
    public static function getEmailVariables(): array
    {
        return [
            ['label' => Craft::t('formie', 'Email'), 'heading' => true],
            ['label' => Craft::t('formie', 'User Email'), 'value' => '{userEmail}'],
            ['label' => Craft::t('formie', 'System Email'), 'value' => '{systemEmail}'],
            ['label' => Craft::t('formie', 'System Reply-To'), 'value' => '{systemReplyTo}'],
        ];
    }

    /**
     * Returns a list of general variables.
     *
     * @return array
     */
    public static function getGeneralVariables(): array
    {
        return [
            ['label' => Craft::t('formie', 'General'), 'heading' => true],
            ['label' => Craft::t('formie', 'System Name'), 'value' => '{systemName}'],
            ['label' => Craft::t('formie', 'Site Name'), 'value' => '{siteName}'],
            ['label' => Craft::t('formie', 'Timestamp'), 'value' => '{timestamp}'],
            ['label' => Craft::t('formie', 'Date (mm/dd/yyyy)'), 'value' => '{dateUs}'],
            ['label' => Craft::t('formie', 'Date (dd/mm/yyyy)'), 'value' => '{dateInt}'],
            ['label' => Craft::t('formie', 'Time (12h)'), 'value' => '{time12}'],
            ['label' => Craft::t('formie', 'Time (24h)'), 'value' => '{time24}'],
        ];
    }

    /**
     * Returns a list of users variables.
     *
     * @return array
     */
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

    /**
     * Returns a list of all available variables.
     *
     * @return array
     */
    public static function getVariables(): array
    {
        return array_merge(
            static::getFormVariables(),
            static::getGeneralVariables(),
            static::getEmailVariables(),
            static::getUsersVariables()
        );
    }

    /**
     * Returns a list of all available variables.
     *
     * @return array
     */
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

    /**
     * Renders and returns an object template, or null if it fails.
     *
     * @param string $value
     * @param Submission|null $submission
     * @param Form|null $form
     * @param Notification|null $notification
     * @return string|null
     * @throws Exception
     */
    public static function getParsedValue(mixed $value, Submission $submission = null, Form $form = null, Notification $notification = null, bool $includeSummary = false): ?string
    {
        $originalValue = $value;

        if ($value === null || $value === '' || is_array($value)) {
            return '';
        }

        // Check if we need to even process any variables for this value
        if (is_string($value) && !str_contains($value, '{')) {
            return $value;
        }

        // Try and get the form from the submission if not set
        if ($submission && !$form) {
            $form = $submission->form;
        }

        // Parse aliases and env variables
        $value = App::parseEnv($value);

        // Use a cache key based on the submission, or few unsaved submissions, the formId
        // This helps to only cache it per-submission, when being run in queues.
        $cacheKey = $submission->id ?? $form->id ?? mt_rand();

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
            $siteName = $site->name ?? '';

            // Force-set the current site. This will either be the current site the user is on for front-end requests,
            // or the site saved against the submission. When being run from a queue there's no concept of the 'site'
            // we're currently on, so using the `siteId` against the submission is the only way to determine that.
            if ($site) {
                Craft::$app->getSites()->setCurrentSite($site);
            }

            $craftMailSettings = App::mailSettings();
            $systemEmail = $craftMailSettings->fromEmail;
            $systemReplyTo = $craftMailSettings->replyToEmail;
            $systemName = $craftMailSettings->fromName;

            // Date Info
            $timeZone = Craft::$app->getTimeZone();
            $now = new DateTime('now', new DateTimeZone($timeZone));
            $dateCreated = $submission->dateCreated ?? null;

            // Form Info
            $formName = $form->title ?? '';

            Formie::$plugin->getRenderCache()->setGlobalVariables($cacheKey, [
                'formName' => $formName,
                'submissionUrl' => $submission->cpEditUrl ?? '',
                'submissionId' => $submission->id ?? null,
                'submissionUid' => $submission->uid ?? null,
                'submissionDate' => $dateCreated ? $dateCreated->format('Y-m-d H:i:s') : null,

                'siteName' => $siteName,
                'systemEmail' => $systemEmail,
                'systemReplyTo' => $systemReplyTo,
                'systemName' => $systemName,
                'craft' => new CraftVariable(),
                'currentSite' => $site,
                'currentUser' => $currentUser,
                'siteUrl' => $site->getBaseUrl(),

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
            ]);

            // Add support for all global sets
            foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
                Formie::$plugin->getRenderCache()->setGlobalVariables($cacheKey, [
                    $globalSet->handle => $globalSet,
                ]);
            }
        }

        $fieldVariables = [];

        if ($includeSummary) {
            // Populate a collection of fields for "all", "visible" and "with-content"
            $fieldVariables = self::getFormFieldsVariables($form, $notification, $submission);

            // We should also re-format the string to remove `<p>` tags from variables, which might produce invalid HTML
            // but just for these summary tags which are block-level
            $value = str_replace(['<p>{allFields}</p>'], '{allFields}', $value);
            $value = str_replace(['<p>{allContentFields}</p>'], '{allContentFields}', $value);
            $value = str_replace(['<p>{allVisibleFields}</p>'], '{allVisibleFields}', $value);
        }

        // Properly parse field values. There's seemingly performance benefits to doing this separate to the above
        $fieldVariables = array_merge($fieldVariables, self::_getParsedFieldValues($form, $submission, $notification));

        // Don't save anything unless we have values
        $fieldVariables = array_filter($fieldVariables);

        Formie::$plugin->getRenderCache()->setFieldVariables($cacheKey, $fieldVariables);

        $variables = Formie::$plugin->getRenderCache()->getVariables($cacheKey);

        // Parse each variable on it's own to handle .env vars
        foreach ($variables as $key => $variable) {
            if (is_string($variable)) {
                $variables[$key] = App::parseEnv($variable);
            }
        }

        // Try to parse submission + extra variables
        try {
            return Formie::$plugin->getTemplates()->renderObjectTemplate($value, $submission, $variables);
        } catch (Throwable $e) {
            Formie::error(Craft::t('formie', 'Failed to render dynamic string “{value}”. Template error: “{message}” {file}:{line}', [
                'value' => $originalValue,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]));

            return '';
        }
    }

    public static function getFormFieldsVariables(?Form $form, ?Notification $notification, ?Submission $submission): array
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

        // Need to switch back to the CP to render our fields email HTML
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        foreach ($form->getCustomFields() as $field) {
            if (!$field->includeInEmail) {
                continue;
            }

            if ($field->isConditionallyHidden($submission)) {
                continue;
            }

            $value = $submission->getFieldValue($field->handle);

            $html = $field->getEmailHtml($submission, $notification, $value);

            if ($html === false) {
                continue;
            }

            // Save to "allFields" for all fields
            $data['allFields'] .= (string)$html;

            // Save to "allVisibleFields" only if not hidden
            if (!$field->getIsHidden()) {
                $data['allVisibleFields'] .= (string)$html;
            }

            // Save to "allFields" only if it has content
            if (!empty($field->getValueAsString($value, $submission))) {
                $data['allContentFields'] .= (string)$html;
            }
        }

        $view->setTemplateMode($oldTemplateMode);

        return $data;
    }


    // Public Static Methods
    // =========================================================================

    public static function _getSite($submission): ?Site
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

    private static function _getParsedFieldValues($form, $submission, $notification): array
    {
        $values = [];

        if (!$form || !$submission) {
            return $values;
        }

        if ($submission->getFieldLayout()) {
            foreach ($submission->getFieldLayout()->getCustomFields() as $field) {
                $submissionValue = $submission->getFieldValue($field->handle);

                $values[] = self::_getParsedFieldValue($field, $submissionValue, $submission, $notification);
            }
        }

        // For performance
        $values = array_merge(...$values);

        return self::_expandArray($values);
    }

    private static function _getParsedFieldValue($field, $submissionValue, $submission, $notification): array
    {
        $values = [];

        if (!$submission) {
            return $values;
        }

        $prefix = 'field.';

        // For pretty much all cases, we want to use the value represented as a string
        $values["{$prefix}{$field->handle}"] = $field->getValueAsString($submissionValue, $submission);

        if ($field instanceof formfields\Date) {
            // Generate additional values
            if ($field->displayType !== 'calendar') {
                $props = [
                    'year' => 'Y',
                    'month' => 'm',
                    'day' => 'd',
                    'hour' => 'H',
                    'minute' => 'i',
                    'second' => 's',
                    'ampm' => 'a',
                ];

                foreach ($props as $k => $format) {
                    $formattedValue = '';

                    if ($submissionValue && $submissionValue instanceof DateTime) {
                        $formattedValue = $submissionValue->format($format);
                    }

                    $values["{$prefix}{$field->handle}.{$k}"] = $formattedValue;
                }
            }
        } else if ($field instanceof SubFieldInterface && $field->hasSubfields()) {
            foreach ($field->getSubFieldOptions() as $subfield) {
                $handle = "{$prefix}{$field->handle}.{$subfield['handle']}";

                $values[$handle] = $submissionValue[$subfield['handle']] ?? '';

                // Special handling for Prefix for a Name field. This can be removed in Formie 2
                if ($field instanceof formfields\Name && $subfield['handle'] === 'prefix') {
                    $prefixOptions = formfields\Name::getPrefixOptions();
                    $prefixOption = ArrayHelper::firstWhere($prefixOptions, 'value', $values[$handle]);
                    $values[$handle] = $prefixOption['label'] ?? '';
                }
            }
        } else if ($field instanceof formfields\Group) {
            if ($submissionValue && $row = $submissionValue->one()) {
                if ($fieldLayout = $row->getFieldLayout()) {
                    foreach ($row->getFieldLayout()->getCustomFields() as $nestedField) {
                        $submissionValue = $row->getFieldValue($nestedField->handle);
                        $fieldValues = self::_getParsedFieldValue($nestedField, $submissionValue, $submission, $notification);

                        foreach ($fieldValues as $key => $fieldValue) {
                            $handle = "{$prefix}{$field->handle}." . str_replace($prefix, '', $key);

                            $values[$handle] = $fieldValue;
                        }
                    }
                }
            }
        } else if ($field instanceof formfields\MultiLineText && !$field->useRichText) {
            $values["{$prefix}{$field->handle}"] = nl2br($field->getValueAsString($submissionValue, $submission));
        }

        // Some fields use the email template for the field, due to their complexity. 
        // Also good for performance rendering only when we need to here.
        if (
            $field instanceof BaseRelationField || 
            $field instanceof formfields\Table || 
            ($field instanceof formfields\MultiLineText && $field->useRichText) || 
            $field instanceof formfields\Repeater || 
            $field instanceof formfields\Signature || 
            $field instanceof formfields\Payment
        ) {
            // There are some circumstances where we're rendering email content, but not for an email. 
            // Slack integration rich text is one of them, there are likely more.
            $notification = $notification ?? new Notification();
            $parsedContent = (string)$field->getEmailHtml($submission, $notification, $submissionValue, ['hideName' => true]);

            $values["{$prefix}{$field->handle}"] = $parsedContent;
        }     

        return $values;
    }

    private static function _expandArray($array): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::_expandArray($value);
            }

            foreach (array_reverse(explode(".", $key)) as $k) {
                $value = [$k => $value];
            }

            $result[] = $value;
        }

        // For performance
        return array_merge_recursive(...$result);
    }

    private static function _getCurrentUser($submission = null): bool|User|IdentityInterface|null
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


    // Deprecated
    // =========================================================================

    public static function getFormFieldsHtml($form, $notification, $submission, $excludeEmpty = false, $excludeHidden = false, $asArray = false): array|string
    {
        // Deprecated in favour of a single function to generate all 3 states at once for performance
        Craft::$app->getDeprecator()->log(__METHOD__, 'Variables `getFormFieldsHtml()` method has been deprecated. Use `getFormFieldsVariables()` instead.');

        $fieldItems = $asArray ? [] : '';

        if (!$form || !$submission) {
            return $fieldItems;
        }

        // If a specific notification isn't passed in, use a new instance of one. This is for times where we don't really mind
        // _which_ notification is used, like when a submission is made on the front-end, with a submit message.
        if (!$notification) {
            $notification = new Notification();
        }

        // Need to switch back to the CP to render our fields email HTML
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        foreach ($form->getCustomFields() as $field) {
            if (!$field->includeInEmail) {
                continue;
            }

            if ($field->isConditionallyHidden($submission)) {
                continue;
            }

            if ($excludeHidden && $field->getIsHidden()) {
                continue;
            }

            $value = $submission->getFieldValue($field->handle);

            if (empty($field->getValueAsString($value, $submission)) && $excludeEmpty) {
                continue;
            }

            $html = $field->getEmailHtml($submission, $notification, $value);

            if ($html === false) {
                continue;
            }

            if ($asArray) {
                $fieldItems[$field->handle] = (string)$html;
            } else {
                $fieldItems .= $html;
            }
        }

        $view->setTemplateMode($oldTemplateMode);

        return $fieldItems;
    }
}
