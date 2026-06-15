<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\CpSubmissionFieldConditions;
use verbb\formie\helpers\IntegrationApiErrors;
use verbb\formie\helpers\Plugin;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;

use Craft;
use craft\base\Model;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\FileHelper;

use yii\validators\EmailValidator;

class Settings extends Model
{
    // Constants
    // =========================================================================

    public const SPAM_BEHAVIOUR_SUCCESS = 'showSuccess';
    public const SPAM_BEHAVIOUR_MESSAGE = 'showMessage';

    public const PLAIN_TEXT_HTML_SANITIZATION_MODE_PRESERVE = 'preserve';
    public const PLAIN_TEXT_HTML_SANITIZATION_MODE_SANITIZE = 'sanitize';

    public const ERROR_ARIA_LIVE_POLITE = 'polite';
    public const ERROR_ARIA_LIVE_ASSERTIVE = 'assertive';
    public const ERROR_ARIA_LIVE_OFF = 'off';
    
    public const SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_DESC = 'dateCreatedDesc';
    public const SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_ASC = 'dateCreatedAsc';
    public const SUBMISSION_SIDEBAR_FORM_ORDER_TITLE_ASC = 'titleAsc';
    public const SUBMISSION_SIDEBAR_FORM_ORDER_TITLE_DESC = 'titleDesc';
    public const SUBMISSION_SIDEBAR_FORM_ORDER_HANDLE_ASC = 'handleAsc';
    public const SUBMISSION_SIDEBAR_FORM_ORDER_HANDLE_DESC = 'handleDesc';


    // Properties
    // =========================================================================

    public string $pluginName = 'Formie';
    public string $defaultPage = 'forms';
    public bool $compatibilityMode = true;
    public bool $staticCacheRefreshOnLoad = false;

    // Forms
    public bool $validateCustomTemplates = true; // Allow power users to handle form template path checks on their own
    public string $defaultFormTemplate = '';
    public string $defaultFormStencil = '';
    public string $defaultEmailTemplate = '';
    public array $formDefaults = [];
    public array $fieldDefaults = [];
    public array $notificationDefaults = [];
    public array $integrationDefaults = [];
    public bool $enableUnloadWarning = true;
    public string $errorAriaLive = self::ERROR_ARIA_LIVE_POLITE;
    public bool $enableBackSubmission = true;
    public int $ajaxTimeout = 10;
    public bool $filterIntegrationMapping = true;
    public bool $includeDraftElementUsage = false;
    public bool $includeRevisionElementUsage = false;
    public bool $outputConsoleMessages = true;

    // General Fields
    public array $disabledFields = [];
    public string $defaultLabelPosition = AboveInput::class;
    public string $defaultInstructionsPosition = AboveInput::class;
    public string $defaultErrorMessagePosition = BelowInput::class;
    public array $validationMessageDefaults = [];

    // Fields
    public bool $allowPublicVolumes = true;
    public bool $allowMultiSelectDropdowns = true;
    public bool $allowPhoneCountrySelector = true;
    public bool $enableLargeFieldStorage = false;
    public bool $includeFlatpickrCss = true;
    public string $plainTextHtmlSanitizationMode = self::PLAIN_TEXT_HTML_SANITIZATION_MODE_PRESERVE;

    // Submissions
    public int $maxIncompleteSubmissionAge = 30;
    public bool $enableCsrfValidationForGuests = true;
    public bool $useQueueForNotifications = true;
    public bool $useQueueForIntegrations = true;
    public ?int $queuePriority = null;
    public ?string $redirectUri = null;
    public array $integrationApiErrorHandling = [];
    public bool $setOnlyCurrentPagePayload = false;
    public string|array $submissionsBehaviour = 'all';
    public int $submissionStateRetentionDays = 30;
    public string $submissionSidebarFormOrder = self::SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_DESC;
    public string $defaultCpSubmissionFieldConditions = CpSubmissionFieldConditions::FOLLOW;
    public int $saveResumeTokenTtlDays = 14;
    public int $maxSavedDraftsPerSession = 10;
    public int $anonymousClientBootstrapRateLimit = 30;
    public int $anonymousClientRefreshRateLimit = 120;
    public int $anonymousClientRateWindowSeconds = 60;

    // Sent Notifications
    public bool $sentNotifications = true;
    public int $maxSentNotificationsAge = 30;

    // Spam — runtime-managed via `SpamProtection` service (`formie_spam_settings`).
    public bool $saveSpam = true;
    public int $spamLimit = 500;
    public bool $spamEmailNotifications = false;
    public string $spamBehaviour = self::SPAM_BEHAVIOUR_SUCCESS;
    public string $spamKeywords = '';
    public string $spamBehaviourMessage = '';

    // Email Notifications
    public bool $sendEmailAlerts = false;
    public ?array $alertEmails = null;
    public ?string $alertEmailsUserGroup = null;
    public bool $sendIntegrationAlerts = false;
    public ?array $integrationAlertEmails = null;
    public ?string $integrationAlertEmailsUserGroup = null;
    public string $emptyValuePlaceholder = 'No response.';
    public int $maxEmailAttachmentSizeMb = 15;

    // PDFs
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';

    // Theme
    public array $themeConfig = [];
    public bool $useCssLayers = false;

    // Deprecated — captcha provider credentials now live in `formie_captcha_providers`.
    public array $captchas = [];

    // Export
    public string $defaultExportFolder = '@storage/formie-export';


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Remove deprecated settings
        unset($config['enableGatsbyCompatibility']);
        unset($config['submissionStateMode']);
        unset($config['submissionStore']);

        // Normalize config
        if (isset($config['submissionsBehaviour']) && is_array($config['submissionsBehaviour'])) {
            $config['submissionsBehaviour'] = 'all';
        }

        if (is_array($config) && Formie::$plugin?->getFormDefaults()) {
            $config = Formie::$plugin->getFormDefaults()->migrateLegacyFieldDefaults($config);
        }

        $config = $this->_normalizeFailAlertSettingsConfig($config);
        $config = $this->_normalizeIntegrationApiErrorHandlingConfig($config);

        parent::__construct($config);
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        // Normalize config
        if (isset($values['submissionsBehaviour']) && is_array($values['submissionsBehaviour'])) {
            $values['submissionsBehaviour'] = 'all';
        }
        unset($values['submissionStateMode']);
        unset($values['submissionStore']);

        if (is_array($values) && Formie::$plugin?->getFormDefaults()) {
            $values = Formie::$plugin->getFormDefaults()->migrateLegacyFieldDefaults($values);
        }

        if (is_array($values)) {
            $values = $this->_normalizeFailAlertSettingsConfig($values);
            $values = $this->_normalizeIntegrationApiErrorHandlingConfig($values);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function validateAlertEmails($attribute): void
    {
        $this->_validateFailAlertRecipients(
            $this->sendEmailAlerts,
            $this->getParsedAlertEmails(),
            $this->alertEmailsUserGroup,
            'alertEmails',
        );
    }

    public function validateIntegrationAlertEmails($attribute): void
    {
        $this->_validateFailAlertRecipients(
            $this->sendIntegrationAlerts,
            $this->getParsedIntegrationAlertEmails(),
            $this->integrationAlertEmailsUserGroup,
            'integrationAlertEmails',
        );
    }

    public function getAlertEmailRows(): array
    {
        return $this->_getAlertEmailRows($this->alertEmails);
    }

    public function getIntegrationAlertEmailRows(): array
    {
        return $this->_getAlertEmailRows($this->integrationAlertEmails);
    }

    public function getParsedAlertEmails(): array
    {
        return $this->_getParsedAlertEmails($this->alertEmails);
    }

    public function getParsedIntegrationAlertEmails(): array
    {
        return $this->_getParsedAlertEmails($this->integrationAlertEmails);
    }

    public function getFailAlertRecipients(): array
    {
        return $this->_resolveFailAlertRecipients($this->alertEmails, $this->alertEmailsUserGroup);
    }

    public function getIntegrationFailAlertRecipients(): array
    {
        return $this->_resolveFailAlertRecipients($this->integrationAlertEmails, $this->integrationAlertEmailsUserGroup);
    }

    public function getDefaultFormTemplateId(): ?int
    {
        if ($template = Formie::$plugin->getFormTemplates()->getTemplateByHandle($this->defaultFormTemplate)) {
            return $template->id;
        }

        return null;
    }

    public function getDefaultEmailTemplateId(): ?int
    {
        if ($template = Formie::$plugin->getEmailTemplates()->getTemplateByHandle($this->defaultEmailTemplate)) {
            return $template->id;
        }

        return null;
    }

    public function getNormalizedFormDefaults(): array
    {
        return array_replace([
            'defaultStatus' => '',
            'submissionTitleFormat' => '{timestamp}',
            'collectIp' => false,
            'collectUser' => false,
            'submitMethod' => 'page-reload',
            'dataRetention' => 'forever',
            'dataRetentionValue' => null,
            'fileUploadsAction' => 'retain',
            'displayFormTitle' => false,
            'displayCurrentPageTitle' => false,
            'displayPageTabs' => false,
            'displayPageProgress' => false,
            'progressCalculation' => 'completion',
            'progressPosition' => 'end',
            'scrollToTop' => true,
            'requiredIndicator' => 'asterisk',
            'cpSubmissionFieldConditions' => '',
        ], $this->formDefaults);
    }

    public function getNormalizedNotificationDefaults(): array
    {
        return array_replace([
            'fromName' => null,
            'from' => null,
            'replyTo' => null,
            'replyToName' => null,
            'subject' => null,
            'attachFiles' => null,
            'attachPdf' => null,
            'pdfTemplateId' => null,
            'enabled' => null,
        ], $this->notificationDefaults);
    }

    public function getNormalizedIntegrationDefaults(): array
    {
        return array_replace([
            'captchas' => [],
        ], $this->integrationDefaults);
    }

    public function getIntegrationApiErrorHandlingRows(): array
    {
        $rows = $this->integrationApiErrorHandling;

        if ($rows === []) {
            return IntegrationApiErrors::defaultHandlingRows();
        }

        return $rows;
    }

    public function getIntegrationApiErrorAction(string $severity): string
    {
        foreach ($this->getIntegrationApiErrorHandlingRows() as $row) {
            if (($row['severity'] ?? '') === $severity) {
                return (string)($row['action'] ?? IntegrationApiErrors::ACTION_FAIL_QUEUE);
            }
        }

        return IntegrationApiErrors::ACTION_FAIL_QUEUE;
    }

    public function shouldSaveSpam(Submission $submission): bool
    {
        if ($this->saveSpam) {
            if ($captcha = $submission->getSpamCaptcha()) {
                // Check only if explicitly set to `false` for backward compatibility
                if ($captcha->saveSpam === false) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function getSecurityKey(): string
    {
        if ($securityKey = App::env('FORMIE_SECURITY_KEY')) {
            return $securityKey;
        }

        return Craft::$app->getConfig()->getGeneral()->securityKey;
    }

    public function hasStaticCache(): bool
    {
        // If Blitz is installed and enabled, we can assume it's being used for static caching.
        if (Plugin::isPluginInstalledAndEnabled('blitz')) {
            return true;
        }

        return $this->staticCacheRefreshOnLoad;
    }

    public function getAbsoluteDefaultExportFolder(): ?string
    {
        $path = Craft::getAlias( $this->defaultExportFolder );
        $exportFolder = FileHelper::normalizePath($path);
        FileHelper::createDirectory($exportFolder);
     
        return $exportFolder;
    }

    public function getMaxEmailAttachmentSizeBytes(): ?int
    {
        if ($this->maxEmailAttachmentSizeMb <= 0) {
            return null;
        }

        return $this->maxEmailAttachmentSizeMb * 1024 * 1024;
    }

    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName', 'defaultPage', 'maxIncompleteSubmissionAge', 'maxSentNotificationsAge'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['maxIncompleteSubmissionAge', 'maxSentNotificationsAge'], 'number', 'integerOnly' => true];
        $rules[] = [['maxEmailAttachmentSizeMb'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['submissionStateRetentionDays'], 'number', 'integerOnly' => true, 'min' => 1];
        $rules[] = [['saveResumeTokenTtlDays'], 'number', 'integerOnly' => true, 'min' => 1];
        $rules[] = [['maxSavedDraftsPerSession'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['anonymousClientBootstrapRateLimit', 'anonymousClientRefreshRateLimit'], 'number', 'integerOnly' => true, 'min' => 0];
        $rules[] = [['anonymousClientRateWindowSeconds'], 'number', 'integerOnly' => true, 'min' => 1];
        $rules[] = [['plainTextHtmlSanitizationMode'], 'in', 'range' => [
            self::PLAIN_TEXT_HTML_SANITIZATION_MODE_PRESERVE,
            self::PLAIN_TEXT_HTML_SANITIZATION_MODE_SANITIZE,
        ]];
        $rules[] = [['sendEmailAlerts'], 'validateAlertEmails'];
        $rules[] = [['sendIntegrationAlerts'], 'validateIntegrationAlertEmails'];
        $rules[] = [['submissionSidebarFormOrder'], 'in', 'range' => [
            self::SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_DESC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_ASC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_TITLE_ASC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_TITLE_DESC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_HANDLE_ASC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_HANDLE_DESC,
        ]];
        $rules[] = [['defaultCpSubmissionFieldConditions'], 'in', 'range' => CpSubmissionFieldConditions::values()];
        $rules[] = [['errorAriaLive'], 'in', 'range' => [
            self::ERROR_ARIA_LIVE_POLITE,
            self::ERROR_ARIA_LIVE_ASSERTIVE,
            self::ERROR_ARIA_LIVE_OFF,
        ]];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeFailAlertSettingsConfig(array $config): array
    {
        if (array_key_exists('alertEmailsUserGroupUid', $config) && !array_key_exists('alertEmailsUserGroup', $config)) {
            $config['alertEmailsUserGroup'] = $config['alertEmailsUserGroupUid'];
        }

        unset($config['alertEmailsUserGroupUid']);

        if (array_key_exists('alertEmails', $config)) {
            $config['alertEmails'] = $this->_normalizeAlertEmails($config['alertEmails']);
        }

        if (array_key_exists('integrationAlertEmailsUserGroupUid', $config) && !array_key_exists('integrationAlertEmailsUserGroup', $config)) {
            $config['integrationAlertEmailsUserGroup'] = $config['integrationAlertEmailsUserGroupUid'];
        }

        unset($config['integrationAlertEmailsUserGroupUid']);

        if (array_key_exists('integrationAlertEmails', $config)) {
            $config['integrationAlertEmails'] = $this->_normalizeAlertEmails($config['integrationAlertEmails']);
        }

        return $config;
    }

    private function _validateFailAlertRecipients(
        bool $enabled,
        array $parsedEmails,
        ?string $userGroupUid,
        string $errorAttribute,
    ): void {
        if (!$enabled) {
            return;
        }

        $hasManualRecipients = !empty($parsedEmails);
        $hasUserGroup = !empty($userGroupUid);

        if (!$hasManualRecipients && !$hasUserGroup) {
            $this->addError($errorAttribute, Craft::t('formie', 'You must select a user group or enter at least one email address.'));
            return;
        }

        $emailValidator = new EmailValidator();

        foreach ($parsedEmails as $email) {
            if (!$emailValidator->validate($email)) {
                $this->addError($errorAttribute, Craft::t('formie', '“{email}” is not a valid email address.', [
                    'email' => $email,
                ]));
                return;
            }
        }
    }

    private function _getAlertEmailRows(?array $alertEmails): array
    {
        $rows = [];

        foreach ($alertEmails ?? [] as $row) {
            $email = $this->_extractAlertEmailValue($row);

            if ($email === '') {
                continue;
            }

            $rows[] = ['email' => $email];
        }

        return $rows;
    }

    private function _getParsedAlertEmails(?array $alertEmails): array
    {
        $emails = [];

        foreach ($alertEmails ?? [] as $row) {
            $email = trim((string)App::parseEnv($this->_extractAlertEmailValue($row)));

            if ($email === '') {
                continue;
            }

            $emails[] = $email;
        }

        return array_values(array_unique($emails));
    }

    private function _resolveFailAlertRecipients(?array $alertEmails, ?string $userGroupUid): array
    {
        $recipients = [];
        $seenEmails = [];

        foreach ($this->_getParsedAlertEmails($alertEmails) as $email) {
            $normalizedEmail = strtolower($email);

            if (isset($seenEmails[$normalizedEmail])) {
                continue;
            }

            $seenEmails[$normalizedEmail] = true;
            $recipients[] = [
                'name' => '',
                'email' => $email,
            ];
        }

        if ($userGroupUid) {
            $group = Craft::$app->getUserGroups()->getGroupByUid($userGroupUid);

            if ($group) {
                $users = User::find()
                    ->groupId($group->id)
                    ->status(null)
                    ->all();

                foreach ($users as $user) {
                    $email = $user->email ?? '';

                    if ($email === '') {
                        continue;
                    }

                    $normalizedEmail = strtolower($email);

                    if (isset($seenEmails[$normalizedEmail])) {
                        continue;
                    }

                    $seenEmails[$normalizedEmail] = true;
                    $recipients[] = [
                        'name' => $user->fullName ?: $user->username,
                        'email' => $email,
                    ];
                }
            }
        }

        return $recipients;
    }

    private function _normalizeAlertEmails(mixed $alertEmails): ?array
    {
        if (!is_array($alertEmails)) {
            return null;
        }

        $normalized = [];

        foreach ($alertEmails as $row) {
            $email = $this->_extractAlertEmailValue($row);

            if ($email === '') {
                continue;
            }

            $normalized[] = ['email' => $email];
        }

        return $normalized ?: null;
    }

    private function _extractAlertEmailValue(mixed $row): string
    {
        if (is_array($row)) {
            if (array_key_exists('email', $row)) {
                return trim((string)$row['email']);
            }

            if (array_is_list($row)) {
                return trim((string)($row[1] ?? $row[0] ?? ''));
            }
        }

        return trim((string)$row);
    }

    private function _normalizeIntegrationApiErrorHandlingConfig(array $config): array
    {
        if (!array_key_exists('integrationApiErrorHandling', $config)) {
            return $config;
        }

        $config['integrationApiErrorHandling'] = $this->_normalizeIntegrationApiErrorHandling($config['integrationApiErrorHandling']);

        return $config;
    }

    private function _normalizeIntegrationApiErrorHandling(mixed $rows): array
    {
        if (!is_array($rows)) {
            return IntegrationApiErrors::defaultHandlingRows();
        }

        $allowedSeverities = array_column(IntegrationApiErrors::severityOptions(), 'value');
        $allowedActions = array_column(IntegrationApiErrors::actionOptions(), 'value');
        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $severity = (string)($row['severity'] ?? '');
            $action = (string)($row['action'] ?? '');

            if (!in_array($severity, $allowedSeverities, true) || !in_array($action, $allowedActions, true)) {
                continue;
            }

            $normalized[] = [
                'severity' => $severity,
                'action' => $action,
            ];
        }

        return $normalized !== [] ? $normalized : IntegrationApiErrors::defaultHandlingRows();
    }
}
