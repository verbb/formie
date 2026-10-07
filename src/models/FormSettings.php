<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment as PaymentIntegration;
use verbb\formie\base\TranslatablePropertiesInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\CompletionBehavior;
use verbb\formie\enums\RedirectSource;
use verbb\formie\enums\RedirectTarget;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\CpSubmissionFieldConditions;
use verbb\formie\helpers\IntegrationSecrets;

use Craft;
use craft\base\Model;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;

use DateTime;
use DateTimeZone;

class FormSettings extends Model implements TranslatablePropertiesInterface
{
    // Static Methods
    // =========================================================================

    public static function translatableProperties(): array
    {
        return [
            'errorMessage',
            'successMessage',
            'limitSubmissionsMessage',
            'requireUserMessage',
            'scheduleFormPendingMessage',
            'scheduleFormExpiredMessage',
        ];
    }

    public static function translatableRichTextProperties(): array
    {
        return self::translatableProperties();
    }

    private static function _normalizeScheduleDateTimeValue(mixed $value): ?DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        if (is_array($value)) {
            return DateTimeHelper::toDateTime($value, true, true) ?: null;
        }

        $stringValue = trim((string)$value);

        if ($stringValue === '') {
            return null;
        }

        // Builder payloads and stored schedule values without an explicit offset are wall-clock
        // datetimes in the Craft app timezone, not UTC.
        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/', $stringValue)) {
            return DateTimeHelper::toDateTime($stringValue, true, true) ?: null;
        }

        return DateTimeHelper::toDateTime($stringValue) ?: null;
    }

    private static function _normalizeCompletionAttributes(array $config, bool $withDefaults = true): array
    {
        $aliases = [
            'submitActionUrl' => 'redirectUrl',
            'submitActionTab' => 'redirectTarget',
            'submitActionFormHide' => 'hideFormAfterSubmit',
            'submitActionMessage' => 'successMessage',
            'submitActionMessageTimeout' => 'successMessageTimeout',
            'submitActionMessagePosition' => 'successMessagePosition',
        ];

        foreach ($aliases as $legacy => $canonical) {
            $canonicalMissing = !array_key_exists($canonical, $config);

            if ($legacy === 'submitActionUrl') {
                // Formie 3 serialized a template-only redirectUrl beside the authored
                // submitActionUrl. A blank override must not discard the authored URL.
                $canonicalMissing = $canonicalMissing || $config[$canonical] === null || $config[$canonical] === '';
            }

            if (array_key_exists($legacy, $config) && $canonicalMissing) {
                $config[$canonical] = $config[$legacy];
            }
            unset($config[$legacy]);
        }

        if (array_key_exists('submitAction', $config) && !array_key_exists('completionBehavior', $config)) {
            $action = $config['submitAction'];
            $config['completionBehavior'] = in_array($action, ['entry', 'url'], true) ? 'redirect' : $action;
            $config['completionRedirectSource'] = $action === 'entry' ? 'entry' : 'url';
        }
        unset($config['submitAction']);

        if ($withDefaults) {
            $config['completionBehavior'] ??= CompletionBehavior::Message->value;
            $config['completionRedirectSource'] ??= RedirectSource::Url->value;
            $config['redirectTarget'] ??= RedirectTarget::SameTab->value;
        }

        return $config;
    }

    private static function _normalizeAllowedStatusIds(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $value))));

        return $ids === [] ? null : $ids;
    }

    private static function _normalizeLimitSubmissionsScope(?string $scope, mixed $limitSubmissions): string
    {
        if ($limitSubmissions === 'ipAddress') {
            return 'ipAddress';
        }

        $scope = trim((string)($scope ?? ''));

        if (in_array($scope, ['form', 'ipAddress', 'user'], true)) {
            return $scope;
        }

        return 'form';
    }


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
    public string $completionBehavior = 'message';
    public string $completionRedirectSource = 'url';
    public string $redirectTarget = 'same-tab';
    public ?string $redirectUrl = null;
    public bool $enableRedirectRules = false;
    public array $redirectRules = [];
    public bool $hideFormAfterSubmit = false;
    public bool $automaticSubmissionState = true;
    public RichText $successMessage;
    public mixed $successMessageTimeout = null;
    public string $successMessagePosition = 'top-form';
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
    public ?string $limitSubmissionsScope = 'form';
    public ?int $limitSubmissionsNumber = null;
    public ?string $limitSubmissionsType = 'total';
    public RichText $limitSubmissionsMessage;
    public ?int $limitSubmissionsIpAddressNumber = null;
    public ?string $limitSubmissionsIpAddressType = null;
    public RichText $limitSubmissionsIpAddressMessage;
    // Integrations
    public array $integrations = [];
    public array $integrationDispatch = [];
    // Settings
    public ?string $submissionTitleFormat = '{timestamp}';
    // Settings - Privacy
    public bool $collectIp = false;
    public bool $collectUser = false;
    public ?string $cpSubmissionFieldConditions = null;
    public bool $enableStatusRules = false;
    public array $statusRules = [];
    public bool $enableDefaultClientEvents = false;
    public array $defaultClientEvents = [];
    public ?array $allowedStatusIds = null;
    public ?string $dataRetention = null;
    public ?string $dataRetentionValue = null;
    public ?string $fileUploadsAction = null;
    // Settings - Permissions
    public bool $usePerFormPermissions = false;
    // Settings - Quiz scoring
    public bool $scoringEnabled = false;
    public float $quizPassPercentage = 70;
    public bool $quizAllowRetakes = true;
    public bool $quizShowScoreAfterSubmit = true;
    // Other
    public ?string $pageRedirectUrl = null;
    public ?string $defaultEmailTemplateId = null;
    // Private (template-only)
    public bool $disableCaptchas = false;

    private ?Form $_form = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        $config = self::_normalizeCompletionAttributes($config);
        CompletionBehavior::from($config['completionBehavior']);
        RedirectSource::from($config['completionRedirectSource']);
        RedirectTarget::from($config['redirectTarget']);

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

        if (array_key_exists('allowedStatusIds', $config)) {
            $config['allowedStatusIds'] = self::_normalizeAllowedStatusIds($config['allowedStatusIds']);
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

        $config = $this->_normalizeScheduleDateTimeAttributes($config);

        $config['limitSubmissionsScope'] = self::_normalizeLimitSubmissionsScope(
            $config['limitSubmissionsScope'] ?? null,
            $config['limitSubmissions'] ?? null,
        );

        $config = $this->_normalizeRichTextAttributes($config);

        parent::__construct($config);
    }

    public function init(): void
    {
        $this->integrations = IntegrationSecrets::reveal((array)$this->integrations);
        parent::init();

        /* @var Settings $settings */
        $settings = Formie::$plugin->getSettings();

        if ($this->errorMessage->isEmpty()) {
            $this->errorMessage = RichText::from('<p>' . Craft::t('formie', 'Couldn’t save submission due to errors.') . '</p>');
        }

        if ($this->successMessage->isEmpty()) {
            $this->successMessage = RichText::from('<p>' . Craft::t('formie', 'Submission saved.') . '</p>');
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
            $values = self::_normalizeCompletionAttributes($values, false);
            $values = $this->_normalizeRichTextAttributes($values, false);
            $values = $this->_normalizeScheduleDateTimeAttributes($values);

            if (array_key_exists('limitSubmissionsScope', $values) || array_key_exists('limitSubmissions', $values)) {
                $values['limitSubmissionsScope'] = self::_normalizeLimitSubmissionsScope(
                    $values['limitSubmissionsScope'] ?? $this->limitSubmissionsScope,
                    $values['limitSubmissions'] ?? $this->limitSubmissions,
                );
            }
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
        $config = $this->_serializeBuilderDateTimeAttributes($config);
        $config['errors'] = $this->getErrors();

        foreach ($this->getEnabledIntegrations() as $key => $integration) {
            $config['integrations'][$integration->handle]['errors'] = $integration->getErrors();
        }

        return $config;
    }

    public function getSuccessMessage(?Submission $submission = null): string
    {
        return $this->_getHtmlContent($this->successMessage, $submission);
    }

    public function getSuccessMessageHtml(): string
    {
        return $this->_getHtmlContent($this->successMessage);
    }

    /** @deprecated Use getSuccessMessage(). */
    public function getSubmitActionMessage(?Submission $submission = null): string
    {
        return $this->getSuccessMessage($submission);
    }

    /** @deprecated Use getSuccessMessageHtml(). */
    public function getSubmitActionMessageHtml(): string
    {
        return $this->getSuccessMessageHtml();
    }

    /** @deprecated Use completionBehavior and completionRedirectSource. */
    public function getSubmitAction(): string
    {
        return $this->completionBehavior === CompletionBehavior::Redirect->value ? $this->completionRedirectSource : $this->completionBehavior;
    }

    /** @deprecated Use completionBehavior and completionRedirectSource. */
    public function setSubmitAction(?string $value): void
    {
        $action = $value ?? CompletionBehavior::Message->value;
        $this->completionBehavior = in_array($action, [RedirectSource::Entry->value, RedirectSource::Url->value], true)
            ? CompletionBehavior::Redirect->value
            : CompletionBehavior::from($action)->value;
        $this->completionRedirectSource = $action === RedirectSource::Entry->value ? RedirectSource::Entry->value : RedirectSource::Url->value;
    }

    /** @deprecated Use redirectUrl. */
    public function getSubmitActionUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /** @deprecated Use redirectUrl. */
    public function setSubmitActionUrl(?string $value): void
    {
        $this->redirectUrl = $value;
    }

    /** @deprecated Use redirectTarget. */
    public function getSubmitActionTab(): string
    {
        return $this->redirectTarget;
    }

    /** @deprecated Use redirectTarget. */
    public function setSubmitActionTab(?string $value): void
    {
        $this->redirectTarget = RedirectTarget::from($value ?? RedirectTarget::SameTab->value)->value;
    }

    /** @deprecated Use hideFormAfterSubmit. */
    public function getSubmitActionFormHide(): bool
    {
        return $this->hideFormAfterSubmit;
    }

    /** @deprecated Use hideFormAfterSubmit. */
    public function setSubmitActionFormHide(bool $value): void
    {
        $this->hideFormAfterSubmit = $value;
    }

    /** @deprecated Use successMessage. */
    public function setSubmitActionMessage(mixed $value): void
    {
        $this->successMessage = RichText::from($value);
    }

    /** @deprecated Use successMessageTimeout. */
    public function getSubmitActionMessageTimeout(): mixed
    {
        return $this->successMessageTimeout;
    }

    /** @deprecated Use successMessageTimeout. */
    public function setSubmitActionMessageTimeout(mixed $value): void
    {
        $this->successMessageTimeout = $value;
    }

    /** @deprecated Use successMessagePosition. */
    public function getSubmitActionMessagePosition(): string
    {
        return $this->successMessagePosition;
    }

    /** @deprecated Use successMessagePosition. */
    public function setSubmitActionMessagePosition(?string $value): void
    {
        $this->successMessagePosition = $value ?? 'top-form';
    }

    public function getErrorMessage(): string
    {
        $message = $this->_getHtmlContent($this->errorMessage);

        return $message;
    }

    public function getErrorMessageHtml(): string
    {
        return $this->_getHtmlContent($this->errorMessage);
    }

    public function getRequireUserMessage(): string
    {
        $message = $this->_getHtmlContent($this->requireUserMessage);

        return $message;
    }

    public function getRequireUserMessageHtml(): string
    {
        return $this->_getHtmlContent($this->requireUserMessage);
    }

    public function getScheduleFormPendingMessage(): string
    {
        $message = $this->_getHtmlContent($this->scheduleFormPendingMessage);

        return $message;
    }

    public function getScheduleFormPendingMessageHtml(): string
    {
        return $this->_getHtmlContent($this->scheduleFormPendingMessage);
    }

    public function getScheduleFormExpiredMessage(): string
    {
        $message = $this->_getHtmlContent($this->scheduleFormExpiredMessage);

        return $message;
    }

    public function getScheduleFormExpiredMessageHtml(): string
    {
        return $this->_getHtmlContent($this->scheduleFormExpiredMessage);
    }

    public function getLimitSubmissionsMessage(): string
    {
        $message = $this->_getHtmlContent($this->limitSubmissionsMessage);

        return $message;
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
                $integration = Formie::$plugin->getIntegrations()->populateIntegrationFromFormSettings($integration, $formSettings);

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
        $pluginSettings = Formie::$plugin->getSettings();
        $ajaxRequired = (bool)$this->getAjaxSubmissionRequirementMessages();

        $this->submitMethod = $pluginSettings->coerceSubmitMethod(
            (string)$this->submitMethod,
            $ajaxRequired,
        );
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['integrations'], 'validateIntegrations'];
        $rules[] = [['submitMethod'], 'validateSubmitMethod'];
        $rules[] = [['completionBehavior'], 'in', 'range' => ['message', 'redirect', 'reload', 'reset']];
        $rules[] = [['completionRedirectSource'], 'in', 'range' => ['url', 'entry']];
        $rules[] = [['redirectTarget'], 'in', 'range' => ['same-tab', 'new-tab']];
        $rules[] = [['progressCalculation'], 'in', 'range' => ['completion', 'page-position']];
        $rules[] = [['cpSubmissionFieldConditions'], 'in', 'range' => array_merge([''], CpSubmissionFieldConditions::values())];
        $rules[] = [['quizPassPercentage'], 'number', 'min' => 0, 'max' => 100];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getHtmlContent($content, $submission = null): string
    {
        return RichText::from($content)->toHtml($submission, false);
    }

    private function _serializeBuilderDateTimeAttributes(array $config): array
    {
        $timezone = new DateTimeZone(Craft::$app->getTimeZone());

        foreach (['scheduleFormStart', 'scheduleFormEnd'] as $attribute) {
            $date = $this->{$attribute} ?? null;

            if ($date instanceof DateTime) {
                // Emit wall-clock values for the Craft app timezone, not UTC-normalized instants.
                $config[$attribute] = (clone $date)->setTimezone($timezone)->format('Y-m-d H:i:s');
            }
        }

        return $config;
    }

    private function _normalizeScheduleDateTimeAttributes(array $config): array
    {
        foreach (['scheduleFormStart', 'scheduleFormEnd'] as $attribute) {
            if (!array_key_exists($attribute, $config)) {
                continue;
            }

            $value = $config[$attribute];

            if ($value === null || $value === '') {
                $config[$attribute] = null;
                continue;
            }

            $config[$attribute] = self::_normalizeScheduleDateTimeValue($value);
        }

        return $config;
    }

    private function _serializeRichTextAttributes(array $config): array
    {
        foreach ([
            'successMessage',
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

    private function _normalizeRichTextAttributes(array $config, bool $withDefaults = true): array
    {
        foreach ([
            'successMessage',
            'errorMessage',
            'requireUserMessage',
            'scheduleFormPendingMessage',
            'scheduleFormExpiredMessage',
            'limitSubmissionsMessage',
            'limitSubmissionsIpAddressMessage',
        ] as $attribute) {
            if (!$withDefaults && !array_key_exists($attribute, $config)) {
                continue;
            }

            $config[$attribute] = RichText::from($config[$attribute] ?? null);
        }

        return $config;
    }
}
