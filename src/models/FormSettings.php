<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\helpers\CpSubmissionFieldConditions;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\elements\Form;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\ArrayHelper;

use Craft;
use craft\base\Model;
use craft\elements\Entry;
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
    public string $progressCalculation = 'completion';
    public string $progressPosition = 'end';
    public string $progressValuePosition = 'inside-center';
    public ?string $defaultLabelPosition = null;
    public ?string $defaultInstructionsPosition = null;
    public ?string $defaultErrorMessagePosition = null;
    public string $requiredIndicator = 'asterisk';

    // Behaviour
    public ?string $submitMethod = 'page-reload';
    public ?string $submitAction = 'message';
    public ?string $submitActionTab = 'same-tab';
    public ?string $submitActionUrl = null;
    public bool $submitActionFormHide = false;
    public bool $automaticSubmissionState = true;
    public RichText $submitActionMessage;
    public mixed $submitActionMessageTimeout = null;
    public string $submitActionMessagePosition = 'top-form';
    public ?string $loadingIndicator = null;
    public ?string $loadingIndicatorText = null;

    // Behaviour - Validation
    public bool $validationOnSubmit = true;
    public bool $validationOnFocus = false;
    public bool $disableSubmitButtonUntilValid = false;
    public RichText $errorMessage;
    public string $errorMessagePosition = 'top-form';

    // Behaviour - Restrictions
    public bool $requireUser = false;
    public RichText $requireUserMessage;
    public bool $scheduleForm = false;
    public ?DateTime $scheduleFormStart = null;
    public ?DateTime $scheduleFormEnd = null;
    public RichText $scheduleFormPendingMessage;
    public RichText $scheduleFormExpiredMessage;
    public bool|string|null $limitSubmissions = null;
    public ?int $limitSubmissionsNumber = null;
    public ?string $limitSubmissionsType = 'total';
    public RichText $limitSubmissionsMessage;
    public ?int $limitSubmissionsIpAddressNumber = null;
    public ?string $limitSubmissionsIpAddressType = null;
    public RichText $limitSubmissionsIpAddressMessage;

    // Integrations
    public array $integrations = [];

    // Settings
    public ?string $submissionTitleFormat = '{timestamp}';

    // Settings - Privacy
    public bool $collectIp = false;
    public bool $collectUser = false;
    public ?string $cpSubmissionFieldConditions = null;
    public ?string $dataRetention = null;
    public ?string $dataRetentionValue = null;
    public ?string $fileUploadsAction = null;

    // Other
    public ?string $redirectUrl = null;
    public ?string $pageRedirectUrl = null;
    public ?string $defaultEmailTemplateId = null;

    // Private (template-only)
    public bool $disableCaptchas = false;

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
        
        $config = $this->_normalizeRichTextAttributes($config);

        parent::__construct($config);
    }

    public function init(): void
    {
        parent::init();

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if ($this->errorMessage->isEmpty()) {
            $this->errorMessage = RichText::from('<p>' . Craft::t('formie', 'Couldn’t save submission due to errors.') . '</p>');
        }

        if ($this->submitActionMessage->isEmpty()) {
            $this->submitActionMessage = RichText::from('<p>' . Craft::t('formie', 'Submission saved.') . '</p>');
        }

        if (!$this->defaultLabelPosition) {
            $this->defaultLabelPosition = $settings->defaultLabelPosition;
        }

        if (!$this->defaultInstructionsPosition) {
            $this->defaultInstructionsPosition = $settings->defaultInstructionsPosition;
        }

        if (!$this->defaultErrorMessagePosition) {
            $this->defaultErrorMessagePosition = $settings->defaultErrorMessagePosition;
        }

        $this->defaultEmailTemplateId = $settings->getDefaultEmailTemplateId();
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            $values = $this->_normalizeRichTextAttributes($values);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function getForm(): ?Form
    {
        return $this->_form;
    }

    public function setForm($value): void
    {
        $this->_form = $value;
    }

    public function getFormBuilderConfig(): array
    {
        $config = $this->toArray();
        $config = $this->_serializeRichTextAttributes($config);
        $config['errors'] = $this->getErrors();

        foreach ($this->getEnabledIntegrations() as $key => $integration) {
            $config['integrations'][$integration->handle]['errors'] = $integration->getErrors();
        }

        return $config;
    }

    public function getSubmitActionMessage($submission = null): string
    {
        $message = $this->_getHtmlContent($this->submitActionMessage, $submission);

        return Craft::t('formie', $message);
    }

    public function getSubmitActionMessageHtml(): string
    {
        return $this->_getHtmlContent($this->submitActionMessage);
    }

    public function getErrorMessage(): string
    {
        $message = $this->_getHtmlContent($this->errorMessage);

        return Craft::t('formie', $message);
    }

    public function getErrorMessageHtml(): string
    {
        return $this->_getHtmlContent($this->errorMessage);
    }

    public function getRequireUserMessage(): string
    {
        $message = $this->_getHtmlContent($this->requireUserMessage);

        return Craft::t('formie', $message);
    }

    public function getRequireUserMessageHtml(): string
    {
        return $this->_getHtmlContent($this->requireUserMessage);
    }

    public function getScheduleFormPendingMessage(): string
    {
        $message = $this->_getHtmlContent($this->scheduleFormPendingMessage);

        return Craft::t('formie', $message);
    }

    public function getScheduleFormPendingMessageHtml(): string
    {
        return $this->_getHtmlContent($this->scheduleFormPendingMessage);
    }

    public function getScheduleFormExpiredMessage(): string
    {
        $message = $this->_getHtmlContent($this->scheduleFormExpiredMessage);

        return Craft::t('formie', $message);
    }

    public function getScheduleFormExpiredMessageHtml(): string
    {
        return $this->_getHtmlContent($this->scheduleFormExpiredMessage);
    }

    public function getLimitSubmissionsMessage(): string
    {
        if ($this->limitSubmissions === 'ipAddress') {
            $message = $this->_getHtmlContent($this->limitSubmissionsIpAddressMessage);
        } else {
            $message = $this->_getHtmlContent($this->limitSubmissionsMessage);
        }

        return Craft::t('formie', $message);
    }

    public function getLimitSubmissionsMessageHtml(): string
    {
        if ($this->limitSubmissions === 'ipAddress') {
            return $this->_getHtmlContent($this->limitSubmissionsIpAddressMessage);
        }

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

    public function getAjaxSubmissionRequirementMessages(): array
    {
        $messages = [];

        $form = $this->getForm();
        if (!$form) {
            return [];
        }

        foreach ($form->getFields() as $field) {
            if (!($field instanceof PaymentField)) {
                continue;
            }

            $integration = $field->getPaymentIntegration();
            if (!($integration instanceof PaymentIntegration)) {
                continue;
            }

            if (!$integration->requiresAjaxSubmission()) {
                continue;
            }

            $message = trim((string)$integration->getAjaxSubmissionRequirementMessage());

            if ($message !== '') {
                $messages[] = $message;
            }
        }

        return array_values(array_unique($messages));
    }

    public function getFormRedirectUrl(bool $checkLastPage = true): string
    {
        return $this->getForm()->getRedirectUrl($checkLastPage);
    }

    public function getRedirectEntry(): ?Entry
    {
        return $this->getForm()->getRedirectEntry();
    }

    public function validateIntegrations(): void
    {
        if ($form = $this->getForm()) {
            $integrations = Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($form);

            foreach ($integrations as $integration) {
                $integration->setScenario(Integration::SCENARIO_FORM);

                if (!$integration->validate()) {
                    foreach ($integration->getErrors() as $key => $error) {
                        $this->addError('integrations.' . $integration->handle . '.' . $key, $error[0]);
                    }
                }
            }
        }
    }

    public function validateSubmitMethod(string $attribute): void
    {
        $messages = $this->getAjaxSubmissionRequirementMessages();
        if (!$messages) {
            return;
        }

        if ($this->submitMethod !== 'ajax') {
            // Automatically enforce Ajax when required by configured payment providers.
            $this->submitMethod = 'ajax';
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['integrations'], 'validateIntegrations'];
        $rules[] = [['submitMethod'], 'validateSubmitMethod'];
        $rules[] = [['progressCalculation'], 'in', 'range' => ['completion', 'page-position']];
        $rules[] = [['cpSubmissionFieldConditions'], 'in', 'range' => array_merge([''], CpSubmissionFieldConditions::values())];

        return $rules;
    }
    

    // Private Methods
    // =========================================================================

    private function _getHtmlContent($content, $submission = null): string
    {
        return RichText::from($content)->toHtml($submission, false);
    }

    private function _serializeRichTextAttributes(array $config): array
    {
        foreach ([
            'submitActionMessage',
            'errorMessage',
            'requireUserMessage',
            'scheduleFormPendingMessage',
            'scheduleFormExpiredMessage',
            'limitSubmissionsMessage',
            'limitSubmissionsIpAddressMessage',
        ] as $attribute) {
            $config[$attribute] = $this->{$attribute}->getSchema();
        }

        return $config;
    }

    private function _normalizeRichTextAttributes(array $config): array
    {
        foreach ([
            'submitActionMessage',
            'errorMessage',
            'requireUserMessage',
            'scheduleFormPendingMessage',
            'scheduleFormExpiredMessage',
            'limitSubmissionsMessage',
            'limitSubmissionsIpAddressMessage',
        ] as $attribute) {
            $config[$attribute] = RichText::from($config[$attribute] ?? null);
        }

        return $config;
    }
}
