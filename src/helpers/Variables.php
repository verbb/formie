<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\ElementFieldInterface;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\SubFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\elements\User;
use craft\fields\BaseRelationField;
use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;
use craft\helpers\App;
use craft\models\Site;
use craft\web\twig\variables\CraftVariable;

use yii\web\IdentityInterface;

use DateTime;
use DateTimeZone;
use Throwable;
use Exception;

class Variables
{
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
        return [
            'form' => static::getFormVariables(),
            'general' => static::getGeneralVariables(),
            'email' => static::getEmailVariables(),
            'users' => static::getUsersVariables(),
        ];
    }

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
                'submissionUrl' => $submission?->getCpEditUrl() ?? '',
                'submissionId' => $submission->id ?? null,
                'submissionUid' => $submission->uid ?? null,
                'submissionDate' => $dateCreated?->format('Y-m-d H:i:s'),

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

        $fieldVariables[] = self::_getParsedFieldValues($form, $submission, $notification);

        if ($includeSummary) {
            // Populate a collection of fields for "all", "visible" and "with-content"
            $fieldVariables[] = self::getFieldsHtml($form, $notification, $submission);
        }

        // For performance
        $fieldVariables = array_merge(...$fieldVariables);

        // Save variables to a render cache for performance
        Formie::$plugin->getRenderCache()->setFieldVariables($cacheKey, $fieldVariables);
        $variables = Formie::$plugin->getRenderCache()->getVariables($cacheKey);

        try {
            return Formie::$plugin->getTemplates()->renderObjectTemplate($value, $submission, $variables);
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

        // Need to switch back to the CP to render our fields email HTML
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        foreach ($form->getFields() as $field) {
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


    // Private Methods
    // =========================================================================

    private static function _getParsedFieldValues(?Form $form, ?Submission $submission, ?Notification $notification): array
    {
        $values = [];

        if (!$form || !$submission) {
            return $values;
        }

        foreach ($submission->getFields() as $field) {
            if ($field->getIsCosmetic()) {
                continue;
            }

            $submissionValue = $submission->getFieldValue($field->handle);

            $values[] = self::_getParsedFieldValue($field, $submissionValue, $submission, $notification);
        }

        // For performance
        $values = array_merge(...$values);

        return ArrayHelper::expand($values);
    }

    private static function _getParsedFieldValue(FieldInterface $field, mixed $submissionValue, Submission $submission, ?Notification $notification): array
    {
        $values = [];

        $parsedContent = '';

        // If we're specifically trying to get the field value for use in emails, use the field's email template HTML.
        if ($notification) {
            $parsedContent = (string)$field->getEmailHtml($submission, $notification, $submissionValue, ['hideName' => true]);
        }

        $prefix = 'field.';

        if ($field instanceof fields\Date) {
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
        } else if ($field instanceof SubFieldInterface && $field->hasSubFields()) {
            foreach ($field->getFields() as $subField) {
                $submissionValue = $submission->getFieldValue($subField->fieldKey);
                $fieldValues = self::_getParsedFieldValue($subField, $submissionValue, $submission, $notification);

                foreach ($fieldValues as $key => $fieldValue) {
                    $handle = "{$prefix}{$field->handle}." . str_replace($prefix, '', $key);

                    $values[$handle] = $fieldValue;
                }
            }
        } else if ($field instanceof fields\Group) {
            foreach ($field->getFields() as $nestedField) {
                $submissionValue = $submission->getFieldValue($nestedField->fieldKey);
                $fieldValues = self::_getParsedFieldValue($nestedField, $submissionValue, $submission, $notification);

                foreach ($fieldValues as $key => $fieldValue) {
                    $handle = "{$prefix}{$field->handle}." . str_replace($prefix, '', $key);

                    $values[$handle] = $fieldValue;
                }
            }
        } else if ($field instanceof fields\MultiLineText && !$field->useRichText) {
            $values["{$prefix}{$field->handle}"] = nl2br($field->getValueAsString($submissionValue, $submission));
        } else {
            $values["{$prefix}{$field->handle}"] = $field->getValueAsString($submissionValue, $submission);
        }

        // Some fields use the email template for the field, due to their complexity. 
        // Also good for performance rendering only when we need to here.
        if (
            $field instanceof ElementFieldInterface || 
            $field instanceof fields\Table || 
            ($field instanceof fields\MultiLineText && $field->useRichText) || 
            $field instanceof fields\Repeater || 
            $field instanceof fields\Signature || 
            $field instanceof fields\Payment
        ) {
            // There are some circumstances where we're rendering email content, but not for an email. 
            // Slack integration rich text is one of them, there are likely more.
            $notification = $notification ?? new Notification();
            $parsedContent = (string)$field->getEmailHtml($submission, $notification, $submissionValue, ['hideName' => true]);

            $values["{$prefix}{$field->handle}"] = $parsedContent;
        }     

        return $values;
    }

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
}
