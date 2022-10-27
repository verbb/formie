<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\positions\AboveInput;

use Craft;
use craft\base\Model;
use craft\helpers\DateTimeHelper;

use yii\validators\EmailValidator;

use DateTime;

class Settings extends Model
{
    // Constants
    // =========================================================================

    public const SPAM_BEHAVIOUR_SUCCESS = 'showSuccess';
    public const SPAM_BEHAVIOUR_MESSAGE = 'showMessage';

    // Properties
    // =========================================================================

    public string $pluginName = 'Formie';
    public string $defaultPage = 'forms';

    // Forms
    public string $defaultFormTemplate = '';
    public string $defaultEmailTemplate = '';
    public bool $enableUnloadWarning = true;
    public bool $enableBackSubmission = true;
    public int $ajaxTimeout = 10;
    public bool $includeDraftElementUsage = false;
    public bool $includeRevisionElementUsage = false;

    // General Fields
    public array $disabledFields = [];
    public string $defaultLabelPosition = AboveInput::class;
    public string $defaultInstructionsPosition = AboveInput::class;

    // Fields
    public string $defaultFileUploadVolume = '';
    public string $defaultDateDisplayType = 'calendar';
    public string $defaultDateValueOption = '';
    public ?DateTime $defaultDateTime = null;

    // Submissions
    public int $maxIncompleteSubmissionAge = 30;
    public bool $enableCsrfValidationForGuests = true;
    public bool $useQueueForNotifications = true;
    public bool $useQueueForIntegrations = true;
    public ?int $queuePriority = null;

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

    // PDFs
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';

    // Theme
    public array $themeConfig = [];

    // Captcha settings are stored in Project Config, but otherwise private
    public array $captchas = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Remove deprecated settings
        unset($config['enableGatsbyCompatibility']);

        parent::__construct($config);
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

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName', 'defaultPage', 'maxIncompleteSubmissionAge', 'maxSentNotificationsAge'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['maxIncompleteSubmissionAge', 'maxSentNotificationsAge'], 'number', 'integerOnly' => true];
        $rules[] = [['alertEmails'], 'validateAlertEmails'];

        return $rules;
    }
}
