<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\RichTextHelper;
use verbb\formie\prosemirror\toprosemirror\Renderer as ProseMirrorRenderer;

use Craft;
use craft\base\Model;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use Twig\Error\SyntaxError;
use Twig\Error\LoaderError;

use DateTime;

class FormSettings extends Model
{
    // Properties
    // =========================================================================

    // Appearance
    public bool $displayFormTitle = false;
    public bool $displayCurrentPageTitle = false;
    public bool $displayPageTabs = false;
    public bool $displayPageProgress = false;
    public bool $scrollToTop = true;
    public string $progressPosition = 'end';
    public ?string $defaultLabelPosition = null;
    public ?string $defaultInstructionsPosition = null;

    // Behaviour
    public ?string $submitMethod = null;
    public ?string $submitAction = null;
    public ?string $submitActionTab = null;
    public ?string $submitActionUrl = null;
    public bool $submitActionFormHide = false;
    public mixed $submitActionMessage = null;
    public mixed $submitActionMessageTimeout = null;
    public string $submitActionMessagePosition = 'top-form';
    public ?string $loadingIndicator = null;
    public ?string $loadingIndicatorText = null;

    // Behaviour - Validation
    public bool $validationOnSubmit = true;
    public bool $validationOnFocus = false;
    public mixed $errorMessage = null;
    public string $errorMessagePosition = 'top-form';

    // Behaviour - Restrictions
    public bool $requireUser = false;
    public mixed $requireUserMessage = null;
    public bool $scheduleForm = false;
    public ?DateTime $scheduleFormStart = null;
    public ?DateTime $scheduleFormEnd = null;
    public mixed $scheduleFormPendingMessage = null;
    public mixed $scheduleFormExpiredMessage = null;
    public bool $limitSubmissions = false;
    public ?int $limitSubmissionsNumber = null;
    public ?string $limitSubmissionsType = null;
    public mixed $limitSubmissionsMessage = null;

    // Integrations
    public array $integrations = [];

    // Settings
    public string $submissionTitleFormat = '{timestamp}';

    // Settings - Privacy
    public bool $collectIp = false;
    public bool $collectUser = false;
    public ?string $dataRetention = null;
    public ?string $dataRetentionValue = null;
    public ?string $fileUploadsAction = null;

    // Other
    public ?string $redirectUrl = null;
    public ?string $defaultEmailTemplateId = null;

    // Private (template-only)
    public bool $disableCaptchas = false;


    // Private Properties
    // =========================================================================

    private ?Form $_form = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('customAttributes', $config)) {
            if (is_string($config['customAttributes'])) {
                $config['customAttributes'] = Json::decodeIfJson($config['customAttributes']);
            }

            if (!is_array($config['customAttributes'])) {
                $config['customAttributes'] = [];
            }
        }

        if (array_key_exists('storeData', $config)) {
            unset($config['storeData']);
        }

        if (array_key_exists('userDeletedAction', $config)) {
            unset($config['userDeletedAction']);
        }

        if (array_key_exists('availabilityMessage', $config)) {
            unset($config['availabilityMessage']);
        }

        if (array_key_exists('availabilityMessageDate', $config)) {
            unset($config['availabilityMessageDate']);
        }

        if (array_key_exists('availabilityMessageSubmissions', $config)) {
            unset($config['availabilityMessageSubmissions']);
        }

        if (array_key_exists('scheduleFormStart', $config)) {
            if (is_array($config['scheduleFormStart'])) {
                $config['scheduleFormStart'] = DateTimeHelper::toDateTime($config['scheduleFormStart']);
            }
        }

        if (array_key_exists('scheduleFormEnd', $config)) {
            if (is_array($config['scheduleFormEnd'])) {
                $config['scheduleFormEnd'] = DateTimeHelper::toDateTime($config['scheduleFormEnd']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if (!$this->errorMessage) {
            $errorMessage = (new ProseMirrorRenderer)->render('<p>' . Craft::t('formie', 'Couldn’t save submission due to errors.') . '</p>');

            $this->errorMessage = $errorMessage['content'];
        }

        if (!$this->submitActionMessage) {
            $submitActionMessage = (new ProseMirrorRenderer)->render('<p>' . Craft::t('formie', 'Submission saved.') . '</p>');

            $this->submitActionMessage = $submitActionMessage['content'];
        }

        if (!$this->defaultLabelPosition) {
            $this->defaultLabelPosition = $settings->defaultLabelPosition;
        }

        if (!$this->defaultInstructionsPosition) {
            $this->defaultInstructionsPosition = $settings->defaultInstructionsPosition;
        }

        $this->defaultEmailTemplateId = $settings->getDefaultEmailTemplateId();
    }

    public function getForm(): ?Form
    {
        return $this->_form;
    }

    public function setForm($value): void
    {
        $this->_form = $value;
    }

    public function getSubmitActionMessage($submission = null): string
    {
        return Craft::t('formie', $this->_getHtmlContent($this->submitActionMessage, $submission));
    }

    public function getSubmitActionMessageHtml(): string
    {
        return $this->_getHtmlContent($this->submitActionMessage);
    }

    public function getErrorMessage(): string
    {
        return Craft::t('formie', $this->_getHtmlContent($this->errorMessage));
    }

    public function getErrorMessageHtml(): string
    {
        return $this->_getHtmlContent($this->errorMessage);
    }

    public function getRequireUserMessage(): string
    {
        return Craft::t('formie', $this->_getHtmlContent($this->requireUserMessage));
    }

    public function getRequireUserMessageHtml(): string
    {
        return $this->_getHtmlContent($this->requireUserMessage);
    }

    public function getScheduleFormPendingMessage(): string
    {
        return Craft::t('formie', $this->_getHtmlContent($this->scheduleFormPendingMessage));
    }

    public function getScheduleFormPendingMessageHtml(): string
    {
        return $this->_getHtmlContent($this->scheduleFormPendingMessage);
    }

    public function getScheduleFormExpiredMessage(): string
    {
        return Craft::t('formie', $this->_getHtmlContent($this->scheduleFormExpiredMessage));
    }

    public function getScheduleFormExpiredMessageHtml(): string
    {
        return $this->_getHtmlContent($this->scheduleFormExpiredMessage);
    }

    public function getLimitSubmissionsMessage(): string
    {
        return Craft::t('formie', $this->_getHtmlContent($this->limitSubmissionsMessage));
    }

    public function getLimitSubmissionsMessageHtml(): string
    {
        return $this->_getHtmlContent($this->limitSubmissionsMessage);
    }

    public function getEnabledIntegrations(): array
    {
        $enabledIntegrations = [];

        // Use all integrations + captchas
        $integrations = array_merge(Formie::$plugin->getIntegrations()->getAllIntegrations(), Formie::$plugin->getIntegrations()->getAllCaptchas());

        // Find all the form-enabled integrations
        $formIntegrationSettings = $this->integrations ?? [];
        $enabledFormSettings = ArrayHelper::where($formIntegrationSettings, 'enabled', true);

        foreach ($enabledFormSettings as $handle => $formSettings) {
            $integration = ArrayHelper::firstWhere($integrations, 'handle', $handle);

            // If this disabled globally? Then don't include it, otherwise populate the settings
            if ($integration && $integration->getEnabled()) {
                $integration->setAttributes($formSettings, false);

                $enabledIntegrations[] = $integration;
            }
        }

        return $enabledIntegrations;
    }

    /**
     * Gets the form's redirect URL.
     *
     * @param bool $checkLastPage
     * @return String
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function getFormRedirectUrl(bool $checkLastPage = true): string
    {
        return $this->getForm()->getRedirectUrl($checkLastPage);
    }

    /**
     * Gets the form's redirect entry, or null if not set.
     *
     * @return Entry|null
     */
    public function getRedirectEntry(): ?Entry
    {
        return $this->getForm()->getRedirectEntry();
    }


    // Private Methods
    // =========================================================================

    private function _getHtmlContent($content, $submission = null): string
    {
        return RichTextHelper::getHtmlContent($content, $submission);
    }
}
