<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\Plugin;
use verbb\formie\positions\AboveInput;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;

use yii\validators\EmailValidator;

use DateTime;

class Settings extends Model
{
    // Constants
    // =========================================================================

    public const SPAM_BEHAVIOUR_SUCCESS = 'showSuccess';
    public const SPAM_BEHAVIOUR_MESSAGE = 'showMessage';

    public const PLAIN_TEXT_HTML_SANITIZATION_MODE_PRESERVE = 'preserve';
    public const PLAIN_TEXT_HTML_SANITIZATION_MODE_SANITIZE = 'sanitize';
    
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
    public string $defaultEmailTemplate = '';
    public bool $enableUnloadWarning = true;
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

    // Fields
    public string $defaultFileUploadVolume = '';
    public bool $allowPublicVolumes = true;
    public string $defaultDateDisplayType = 'calendar';
    public string $defaultDateValueOption = '';
    public ?DateTime $defaultDateTime = null;
    public bool $enableLargeFieldStorage = false;
    public string $plainTextHtmlSanitizationMode = self::PLAIN_TEXT_HTML_SANITIZATION_MODE_PRESERVE;

    // Submissions
    public int $maxIncompleteSubmissionAge = 30;
    public bool $enableCsrfValidationForGuests = true;
    public bool $useQueueForNotifications = true;
    public bool $useQueueForIntegrations = true;
    public ?int $queuePriority = null;
    public bool $setOnlyCurrentPagePayload = false;
    public string|array $submissionsBehaviour = 'all';
    public int $submissionStateRetentionDays = 30;
    public string $submissionSidebarFormOrder = self::SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_DESC;
    public int $saveResumeTokenTtlDays = 14;
    public int $maxSavedDraftsPerSession = 10;
    public int $anonymousClientBootstrapRateLimit = 30;
    public int $anonymousClientRefreshRateLimit = 120;
    public int $anonymousClientRateWindowSeconds = 60;

    // Sent Notifications
    public bool $sentNotifications = true;
    public int $maxSentNotificationsAge = 30;

    // Spam
    public bool $saveSpam = true;
    public int $spamLimit = 500;
    public bool $spamEmailNotifications = false;
    public string $spamBehaviour = self::SPAM_BEHAVIOUR_SUCCESS;
    public string $spamKeywords = '';
    public string $spamBehaviourMessage = '';

    // Email Notifications
    public bool $sendEmailAlerts = false;
    public ?array $alertEmails = null;
    public string $emptyValuePlaceholder = 'No response.';
    public int $maxEmailAttachmentSizeMb = 15;

    // PDFs
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';

    // Theme
    public array $themeConfig = [];
    public bool $useCssLayers = false;

    // Captcha settings are stored in Project Config, but otherwise private
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

        parent::setAttributes($values, $safeOnly);
    }

    public function validateAlertEmails($attribute): void
    {
        if ($this->sendEmailAlerts) {
            if (empty($this->alertEmails)) {
                $this->addError($attribute, Craft::t('formie', 'You must enter at least one name and email.'));
                return;
            }

            foreach ($this->alertEmails as $fromNameEmail) {
                if ($fromNameEmail[0] === '' || $fromNameEmail[1] === '') {
                    $this->addError($attribute, Craft::t('formie', 'The name and email cannot be blank.'));
                    return;
                }

                $emailValidator = new EmailValidator();

                if (!$emailValidator->validate($fromNameEmail[1])) {
                    $this->addError($attribute, Craft::t('formie', 'An invalid email was entered.'));
                    return;
                }
            }
        }
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

    public function getDefaultDateTimeValue(): ?DateTime
    {
        if ($defaultDateTime = DateTimeHelper::toDateTime($this->defaultDateTime)) {
            return $this->defaultDateTime = $defaultDateTime;
        }

        return null;
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
        $rules[] = [['alertEmails'], 'validateAlertEmails'];
        $rules[] = [['submissionSidebarFormOrder'], 'in', 'range' => [
            self::SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_DESC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_DATE_CREATED_ASC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_TITLE_ASC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_TITLE_DESC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_HANDLE_ASC,
            self::SUBMISSION_SIDEBAR_FORM_ORDER_HANDLE_DESC,
        ]];

        return $rules;
    }
}
