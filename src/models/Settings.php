<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
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
    public int $ajaxTimeout = 10;

    // Fields
    public array $disabledFields = [];

    // General Fields
    public string $defaultLabelPosition = AboveInput::class;
    public string $defaultInstructionsPosition = AboveInput::class;

    // Fields
    public string $defaultFileUploadVolume = '';
    public string $defaultDateDisplayType = '';
    public string $defaultDateValueOption = '';
    public ?DateTime $defaultDateTime = null;

    public int $maxIncompleteSubmissionAge = 30;
    public bool $enableGatsbyCompatibility = false;
    public int $maxSentNotificationsAge = 30;

    // Submissions
    public bool $enableCsrfValidationForGuests = true;
    public bool $useQueueForNotifications = true;
    public bool $useQueueForIntegrations = true;

    // Spam
    public bool $saveSpam = true;
    public int $spamLimit = 500;
    public bool $spamEmailNotifications = false;
    public string $spamBehaviour = self::SPAM_BEHAVIOUR_SUCCESS;
    public string $spamKeywords = '';
    public string $spamBehaviourMessage = '';

    // Notifications
    public bool $sendEmailAlerts = false;
    public bool $sentNotifications = true;
    public ?array $alertEmails = null;

    // PDFs
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';

    public array $captchas = [];


    // Public Methods
    // =========================================================================

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
