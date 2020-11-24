<?php
namespace verbb\formie\helpers;

use Craft;
use craft\errors\SiteNotFoundException;
use craft\helpers\App;

use DateTime;
use DateTimeZone;
use Throwable;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;

class Variables
{
    // Properties
    // =========================================================================

    public static $extras = [];


    // Public Methods
    // =========================================================================

    /**
     * Returns a list of form variables.
     *
     * @return array
     */
    public static function getFormVariables(): array
    {
        return [
            [ 'label' => Craft::t('formie', 'Form'), 'heading' => true ],
            [ 'label' => Craft::t('formie', 'All Form Fields'), 'value' => '{allFields}' ],
            [ 'label' => Craft::t('formie', 'All Non Empty Fields'), 'value' => '{allContentFields}' ],
            [ 'label' => Craft::t('formie', 'Form Name'), 'value' => '{formName}' ],
            [ 'label' => Craft::t('formie', 'Submission CP URL'), 'value' => '{submissionUrl}' ],
            [ 'label' => Craft::t('formie', 'Submission ID'), 'value' => '{submissionId}' ],
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
            [ 'label' => Craft::t('formie', 'Email'), 'heading' => true ],
            [ 'label' => Craft::t('formie', 'System Email'), 'value' => '{systemEmail}' ],
            [ 'label' => Craft::t('formie', 'System Reply-To'), 'value' => '{systemReplyTo}' ],
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
            [ 'label' => Craft::t('formie', 'General'), 'heading' => true ],
            [ 'label' => Craft::t('formie', 'Site Name'), 'value' => '{siteName}' ],
            [ 'label' => Craft::t('formie', 'Timestamp'), 'value' => '{timestamp}' ],
            [ 'label' => Craft::t('formie', 'Date (mm/dd/yyyy)'), 'value' => '{dateUs}' ],
            [ 'label' => Craft::t('formie', 'Date (dd/mm/yyyy)'), 'value' => '{dateInt}' ],
            [ 'label' => Craft::t('formie', 'Time (12h)'), 'value' => '{time12}' ],
            [ 'label' => Craft::t('formie', 'Time (24h)'), 'value' => '{time24}' ],
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
            [ 'label' => Craft::t('formie', 'Users'), 'heading' => true ],
            [ 'label' => Craft::t('formie', 'User IP Address'), 'value' => '{userIp}' ],
            [ 'label' => Craft::t('formie', 'User ID'), 'value' => '{userId}' ],
            [ 'label' => Craft::t('formie', 'User Email'), 'value' => '{userEmail}' ],
            [ 'label' => Craft::t('formie', 'Username'), 'value' => '{username}' ],
            [ 'label' => Craft::t('formie', 'User Full Name'), 'value' => '{userFullName}' ],
            [ 'label' => Craft::t('formie', 'User First Name'), 'value' => '{userFirstName}' ],
            [ 'label' => Craft::t('formie', 'User Last Name'), 'value' => '{userLastName}' ],
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
        return [
            'form' => static::getFormVariables(),
            'general' => static::getGeneralVariables(),
            'email' => static::getEmailVariables(),
            'users' => static::getUsersVariables(),
        ];
    }

    /**
     * Renders and returns an object template, or null if it fails.
     *
     * @param string $value
     * @param Submission $submission
     * @param Form $form
     * @return string|null
     */
    public static function getParsedValue($value, Submission $submission = null, Form $form = null)
    {
        $originalValue = $value;

        if (!$value) {
            return '';
        }

        if (is_array($value)) {
            return '';
        }

        // Try and get the form from the submission if not set
        if ($submission && !$form) {
            $form = $submission->form;
        }

        // Parse aliases and env variables
        $value = Craft::parseEnv($value);

        // Use a cache key based on the submission, or few unsaved submissions, the formId
        // This helps to only cache it per-submission, when being run in queues.
        $cacheKey = $submission->id ?? $form->id ?? rand();

        $extras = self::$extras[$cacheKey] ?? [];

        // Check to see if we have these already calculated for the request and submission
        // Just saves a good bunch of calculating values like looping through fields
        if (!$extras) {
            // User Info
            $currentUser = Craft::$app->getUser()->getIdentity();
            $userId = $currentUser->id ?? '';
            $userEmail = $currentUser->email ?? '';
            $username = $currentUser->username ?? '';
            $userFullName = $currentUser->fullName ?? '';
            $userFirstName = $currentUser->firstName ?? '';
            $userLastName = $currentUser->lastName ?? '';
            $userIp = $submission->ipAddress ?? '';

            // Site Info
            $siteId = $submission->siteId ?? Craft::$app->getSites()->getPrimarySite()->id;
            $site = Craft::$app->getSites()->getSiteById($siteId);
            $siteName = $site->name ?? '';

            $craftMailSettings = App::mailSettings();
            $systemEmail = $craftMailSettings->fromEmail;
            $systemReplyTo = $craftMailSettings->replyToEmail;
            $systemName = $craftMailSettings->fromName;

            // Date Info
            $timeZone = Craft::$app->getTimeZone();
            $now = new DateTime('now', new DateTimeZone($timeZone));

            // Form Info
            $formName = $form->title ?? '';

            $fieldHtml = self::getFormFieldsHtml($form, $submission);
            $fieldContentHtml = self::getFormFieldsHtml($form, $submission, true);

            $extras = self::$extras[$cacheKey] = [
                'allFields' => $fieldHtml,
                'allContentFields' => $fieldContentHtml,
                'formName' => $formName,
                'submissionUrl' => $submission->cpEditUrl ?? '',
                'submissionId' => $submission->id ?? '',

                'siteName' => $siteName,
                'systemEmail' => $systemEmail,
                'systemReplyTo' => $systemReplyTo,
                'systemName' => $systemName,

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
        }

        // Try to parse submission + extra variables
        $view = Craft::$app->getView();

        try {
            return $view->renderObjectTemplate($value, $submission, $extras);
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

    public static function getFormFieldsHtml($form, $submission, $excludeEmpty = false)
    {
        if (!$form) {
            return '';
        }

        $fieldHtml = '';

        // Need to switch back to the CP to render our fields email HTML
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        foreach ($form->getFields() as $field) {
            $value = $submission->getFieldValue($field->handle);

            if (empty($value) && $excludeEmpty) {
                continue;
            }

            $html = $field->getEmailHtml($submission, $value);

            if ($html === false) {
                continue;
            }

            $fieldHtml .= $html;
        }

        $view->setTemplateMode($oldTemplateMode);

        return $fieldHtml;
    }
}
