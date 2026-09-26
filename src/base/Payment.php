<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\PaymentCallbackEvent;
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\events\PaymentWebhookEvent;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\FieldReferenceHelper;
use verbb\formie\helpers\PaymentAmountHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\Notification;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\PaymentFieldPayload;
use verbb\formie\models\SlotTag;
use verbb\formie\models\payments\PaymentWebhookCommand;
use verbb\formie\references\ReferenceContext;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use yii\base\Event;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use NumberFormatter;
use RuntimeException;
use Throwable;

use Money\Currencies\ISOCurrencies;
use Twig\Markup;

abstract class Payment extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_PROCESS_PAYMENT = 'beforeProcessPayment';
    public const EVENT_AFTER_PROCESS_PAYMENT = 'afterProcessPayment';
    public const EVENT_BEFORE_PROCESS_WEBHOOK = 'beforeProcessWebhook';
    public const EVENT_AFTER_PROCESS_WEBHOOK = 'afterProcessWebhook';
    public const EVENT_BEFORE_PROCESS_CALLBACK = 'beforeProcessCallback';
    public const EVENT_AFTER_PROCESS_CALLBACK = 'afterProcessCallback';
    public const EVENT_MODIFY_CURRENCY_OPTIONS = 'modifyCurrencyOptions';

    public const PAYMENT_TYPE_SINGLE = 'single';
    public const PAYMENT_TYPE_SUBSCRIPTION = 'subscription';
    
    public const VALUE_TYPE_FIXED = 'fixed';
    public const VALUE_TYPE_DYNAMIC = 'dynamic';


    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Payments');
    }

    public static function supportsPayloadSending(): bool
    {
        return false;
    }

    public static function hasFormSettings(): bool
    {
        return false;
    }

    public static function getCurrencyOptions(): array
    {
        $currencies = [];

        foreach (new ISOCurrencies() as $currency) {
            $currencies[] = ['label' => $currency->getCode(), 'value' => $currency->getCode()];
        }

        usort($currencies, function($a, $b) {
            return $a['label'] <=> $b['label'];
        });

        // Raise a `modifyCurrencyOptions` event
        $event = new ModifyPaymentCurrencyOptionsEvent([
            'currencies' => $currencies,
        ]);
        Event::trigger(static::class, self::EVENT_MODIFY_CURRENCY_OPTIONS, $event);

        return $event->currencies;
    }

    public static function getDefaultCurrencyCode(): string
    {
        $locale = Craft::$app->getLocale();

        // Older Craft versions expose the locale but not its default-currency helper.
        $currency = method_exists($locale, 'getDefaultCurrency')
            ? $locale->getDefaultCurrency()
            : (new NumberFormatter($locale->aliasOf ?? $locale->id, NumberFormatter::CURRENCY))->getTextAttribute(NumberFormatter::CURRENCY_CODE);
        $currency = strtoupper((string)($currency ?: 'USD'));

        return $currency !== '' ? $currency : 'USD';
    }

    public function supportsWebhooks(): bool
    {
        return false;
    }

    public function supportsCallbacks(): bool
    {
        return false;
    }

    public function requiresAjaxSubmission(): bool
    {
        return false;
    }

    public function getAjaxSubmissionRequirementMessage(): string
    {
        return Craft::t('formie', '{name} requires Ajax submissions.', [
            'name' => static::displayName(),
        ]);
    }

    public function getPaymentFieldSettingsDefaults(): array
    {
        $defaults = array_merge([
            'integration' => static::class,
        ], $this->definePaymentFieldSettingsDefaults());

        return $defaults;
    }

    // Properties
    // =========================================================================

    public ?bool $throwApiError = false;

    private ?PaymentField $_field = null;


    // Public Methods
    // =========================================================================

    public function processPayment(Submission $submission): PaymentDecision
    {
        $lock = 'formie.payment-execution.' . $submission->id . '.' . $this->id . '.' . $this->getField()?->id;
        $mutex = Craft::$app->getMutex();
        if (!$mutex->acquire($lock, 10)) {
            return PaymentDecision::pending('Payment is already being processed.', $this->handle);
        }
        $payment = null;
        $db = Craft::$app->getDb();
        $enableSlaves = $db->enableSlaves;
        $db->enableSlaves = false;
        try {
            if (Craft::$app->getDb()->getTransaction()?->getIsActive()) {
                throw new RuntimeException('Commit the submission before provider execution.');
            }
            $payments = Formie::$plugin->getPayments();
            $payment = $payments->prepareAttempt($this, $submission);
            if ($payment->status === PaymentModel::STATUS_SUCCESS || ($payment->scope['providerOutcome']['status'] ?? null) === PaymentModel::STATUS_SUCCESS) {
                return PaymentDecision::succeeded($this->handle, $payment->reference);
            }
            if (!($payment->scope['initial'] ?? false)) {
                $payment->status = PaymentModel::STATUS_UNKNOWN;
                $payments->savePayment($payment);
                return PaymentDecision::unknown('The provider outcome requires reconciliation.', $this->handle, $payment->reference);
            }
            if ($payment->status === PaymentModel::STATUS_CANCELLED) {
                return PaymentDecision::cancelled($payment->message, $this->handle, $payment->reference);
            }
            if ($this->getFieldSetting('type') === self::PAYMENT_TYPE_SUBSCRIPTION) {
                $payments->prepareSubscription($this, $submission);
            }
            $decision = $this->executePayment($submission);
            $payment = $payments->getPaymentById($payment->id);
            $payment->status = match ($decision->status) {
                PaymentDecision::STATUS_SUCCEEDED => PaymentModel::STATUS_SUCCESS,
                PaymentDecision::STATUS_FAILED => PaymentModel::STATUS_FAILED,
                PaymentDecision::STATUS_CANCELLED => PaymentModel::STATUS_CANCELLED,
                PaymentDecision::STATUS_UNKNOWN => PaymentModel::STATUS_UNKNOWN,
                PaymentDecision::STATUS_ACTION_REQUIRED => $payment->status,
                default => $payment->status,
            };
            $payments->savePayment($payment);
            return $decision;
        } catch (Throwable $e) {
            if ($payment) {
                $payment = Formie::$plugin->getPayments()->getPaymentById($payment->id);
                $payment->status = PaymentModel::STATUS_UNKNOWN;
                $payment->message = 'Provider outcome requires reconciliation.';
                Formie::$plugin->getPayments()->savePayment($payment);
            }
            return $payment ? PaymentDecision::unknown('Unable to confirm the payment outcome.', $this->handle, $payment->reference)
                : PaymentDecision::failed('Unable to establish the payment amount and ownership.', $this->handle);
        } finally {
            $db->enableSlaves = $enableSlaves;
            $mutex->release($lock);
        }
    }


    public function resolvePaymentDecision(Submission $submission): PaymentDecision
    {
        return $this->processPayment($submission);
    }

    public function getType(): string
    {
        return self::TYPE_PAYMENT;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_PAYMENTS;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/integrations/payments/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getIntegrationHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/payments/{$handle}.svg");
    }

    public function getCpIconPath(): string
    {
        $category = trim((string)$this->getCategoryHandle());
        $handle = trim((string)$this->getIntegrationHandle());

        if ($category === '' || $handle === '') {
            return '';
        }

        return "icons/{$category}/{$handle}.svg";
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getIntegrationHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/payments/{$handle}/_plugin-settings", $variables);
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, PaymentField $field, array $renderOptions = null): Markup
    {
        $handle = $this->getIntegrationHandle();

        $inputOptions = array_merge($field->getReferenceBlockOptions($submission, $notification, $value, $renderOptions), [
            'field' => $field,
            'integration' => $this,
        ]);
        
        return Template::raw($notification->renderTemplate("integrations/payments/{$handle}/field", $inputOptions));
    }

    public function getSubmissionSummaryHtml(Submission $submission, ?PaymentField $field = null): ?string
    {
        $handle = $this->getIntegrationHandle();

        // Only show if there's payments for a submission
        $payments = $submission->getPayments();
        $subscriptions = $submission->getSubscriptions();

        if ($field) {
            $payments = array_values(array_filter($payments, fn($payment) => (int)$payment->fieldId === (int)$field->id));
            $subscriptions = array_values(array_filter($subscriptions, fn($subscription) => (int)$subscription->fieldId === (int)$field->id));
        }

        if (!$payments && !$subscriptions) {
            return null;
        }

        return $submission->getForm()->renderTemplate("integrations/payments/{$handle}/submission-summary", [
            'integration' => $this,
            'form' => $submission,
            'payments' => $payments,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function renderFieldHtml(FieldInterface $field): string
    {
        $handle = $this->getIntegrationHandle();
        $variables = $this->getFieldHtmlVariables();

        if (!$this->hasValidSettings()) {
            return '';
        }

        $this->setField($field);

        $variables['field'] = $field;
        $variables['form'] = $field->getForm();

        return $field->getForm()->renderTemplate("integrations/payments/{$handle}/field", $variables);
    }

    public function getFieldHtmlVariables(): array
    {
        return [];
    }

    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModuleEntry
    {
        return null;
    }

    public function getGraphqlPaymentInputFieldKeys(FieldInterface $field): array
    {
        $this->setField($field);

        $module = $this->getBrowserModule(new BrowserModuleContext([
            'form' => $field->getForm(),
            'field' => $field,
            'integration' => $this,
            'surface' => BrowserModuleEntry::SURFACE_SERVER_RENDERED,
        ]));

        $required = [];

        if (is_array($module?->config['requiredInputSuffixes'] ?? null)) {
            $required = $module->config['requiredInputSuffixes'];
        }

        return array_values(array_unique(array_merge($required, $this->getOptionalGraphqlPaymentInputFieldKeys())));
    }

    protected function getOptionalGraphqlPaymentInputFieldKeys(): array
    {
        return [];
    }

    public function getRedirectUri(): string
    {
        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            $url = UrlHelper::actionUrl('formie/payment-webhooks/process-webhook', ['handle' => $this->handle]);
        } else {
            $url = UrlHelper::siteUrl('formie/payment-webhooks/process-webhook', ['handle' => $this->handle]);
        }

        return self::applyPaymentWebhookProxy($url);
    }

    public static function applyPaymentWebhookProxy(string $url): string
    {
        $proxyBase = App::parseEnv(Formie::$plugin->getSettings()->paymentWebhookProxyUrl);

        return self::applyDevAccessibleUrl($url, App::devMode(), $proxyBase);
    }

    public static function applyDevAccessibleUrl(string $url, bool $devMode, mixed $proxyBase): string
    {
        if (!$devMode) {
            return $url;
        }

        // An explicit empty string disables the dev proxy and uses the local URL as-is.
        if ($proxyBase === '') {
            return $url;
        }

        if (!is_string($proxyBase) || trim($proxyBase) === '') {
            $proxyBase = 'https://proxy.verbb.io';
        } else {
            $proxyBase = rtrim(trim($proxyBase), '/');
        }

        return $proxyBase . '?return=' . $url;
    }

    public function getGqlHandle(): string
    {
        return StringHelper::toCamelCase($this->handle . 'Payment');
    }

    public function getAmount(Submission $submission): string|int|float
    {
        $amount = 0;
        $amountType = $this->getFieldSetting('amountType');
        $amountFixed = $this->getFieldSetting('amountFixed');
        $amountVariable = $this->getFieldSetting('amountVariable');

        if ($amountType === Payment::VALUE_TYPE_FIXED) {
            $amount = PaymentAmountHelper::parseAmount($amountFixed);
        } else if ($amountType === Payment::VALUE_TYPE_DYNAMIC) {
            $amount = PaymentAmountHelper::parseAmount(References::resolveValue($amountVariable, ReferenceContext::forSubmission($submission))->requireValue());
        }

        return $amount;
    }

    public function getCurrency(Submission $submission): ?string
    {
        $currencyType = $this->getFieldSetting('currencyType');
        $currencyFixed = $this->getFieldSetting('currencyFixed');
        $currencyVariable = $this->getFieldSetting('currencyVariable');

        if ($currencyType === Payment::VALUE_TYPE_FIXED) {
            return (string)$currencyFixed;
        } else if ($currencyType === Payment::VALUE_TYPE_DYNAMIC) {
            return (string)References::resolveValue($currencyVariable, ReferenceContext::forSubmission($submission))->requireValue();
        }

        return $this->getFieldSetting('currency');
    }

    /**
     * Resolve the amount in the major currency units stored on payment records.
     * Providers whose getAmount() returns API units must convert them here.
     */
    public function getPaymentAmount(Submission $submission): string|int|float
    {
        return $this->getAmount($submission);
    }

    public function processWebhooks(?PaymentWebhookCommand $command = null): Response
    {
        $response = null;

        // Fire a 'beforeProcessWebhook' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PROCESS_WEBHOOK)) {
            $this->trigger(self::EVENT_BEFORE_PROCESS_WEBHOOK, new PaymentWebhookEvent([
                'integration' => $this,
            ]));
        }

        try {
            if ($this->supportsWebhooks()) {
                $command ??= PaymentWebhookCommand::fromRequest($this->id);
                if ($command->integrationId !== $this->id) { throw new BadRequestHttpException('Webhook integration mismatch.'); }
                $response = $this->processWebhook($command);
            } else {
                throw new BadRequestHttpException('Integration does not support webhooks.');
            }
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'Exception while processing webhook: “{message}” {file}:{line}. Trace: “{trace}”.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]));

            $response = Craft::$app->getResponse();
            $response->setStatusCodeByException($e);
        }

        // Fire a 'afterProcessWebhook' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PROCESS_WEBHOOK)) {
            $this->trigger(self::EVENT_AFTER_PROCESS_WEBHOOK, new PaymentWebhookEvent([
                'integration' => $this,
                'response' => $response,
            ]));
        }

        return $response;
    }

    public function processCallbacks(): Response
    {
        $response = null;

        // Fire a 'beforeProcessCallback' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PROCESS_CALLBACK)) {
            $this->trigger(self::EVENT_BEFORE_PROCESS_CALLBACK, new PaymentCallbackEvent([
                'integration' => $this,
            ]));
        }

        try {
            if ($this->supportsCallbacks()) {
                $response = $this->processCallback();
            } else {
                throw new BadRequestHttpException('Integration does not support callbacks.');
            }
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'Exception while processing webhook: “{message}” {file}:{line}. Trace: “{trace}”.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]));

            $response = Craft::$app->getResponse();
            $response->setStatusCodeByException($e);
        }

        // Fire an 'afterProcessCallback' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_PROCESS_CALLBACK)) {
            $this->trigger(self::EVENT_AFTER_PROCESS_CALLBACK, new PaymentCallbackEvent([
                'integration' => $this,
                'response' => $response,
            ]));
        }

        return $response;
    }

    public function getTransaction(PaymentModel $payment): void
    {

    }

    public function getTransactionStatus(PaymentModel $payment): void
    {

    }

    public function getField(): ?PaymentField
    {
        return $this->_field;
    }

    public function setField(?PaymentField $value): void
    {
        $this->_field = $value;
    }

    public function getFieldSetting(string $setting, mixed $default = null): mixed
    {
        if ($field = $this->getField()) {
            $providerSettings = $field->providerSettings[$this->handle] ?? [];

            return ArrayHelper::getValue($providerSettings, $setting, $default) ?: $default;
        }

        return $default;
    }

    public function modifyFieldSettings(array $settings): array
    {
        return $settings;
    }


    // Protected Methods
    // =========================================================================

    protected function executePayment(Submission $submission): PaymentDecision
    {
        return PaymentDecision::notRequired();
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        return null;
    }
    
    protected function getIntegrationHandle(): string
    {
        return StringHelper::toKebabCase(static::className());
    }
    
    protected function getPaymentFieldValue(Submission $submission): array
    {
        return $this->getPaymentFieldPayload($submission)->all();
    }

    protected function getPaymentFieldPayload(Submission $submission): PaymentFieldPayload
    {
        if ($field = $this->getField()) {
            // Resolve as the field's array projection; payment integrations then
            // interpret provider-specific keys from this canonical payload.
            $value = $submission->getFieldValueAsData($field->valueKey());

            return new PaymentFieldPayload($this->handle ?? '', $field->valueKey(), is_array($value) ? $value : []);
        }

        return new PaymentFieldPayload($this->handle ?? '');
    }

    protected function addFieldError(Submission $submission, string $message): void
    {
        if ($field = $this->getField()) {
            $submission->addError($field->errorKey(), $message);
        }
    }

    protected function getFriendlyPaymentErrorMessage(Throwable $error, int $maxLength = 120): string
    {
        $message = trim((string)$error->getMessage());

        if ($message === '') {
            return Craft::t('formie', 'An unexpected payment error occurred.');
        }

        if (strlen($message) > $maxLength) {
            return substr($message, 0, $maxLength) . '...';
        }

        return $message;
    }

    protected function beforeProcessPayment(Submission $submission): bool
    {
        $event = new PaymentIntegrationProcessEvent([
            'submission' => $submission,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_PROCESS_PAYMENT, $event);

        if (!$event->isValid) {
            Integration::info($this, 'Payment processing cancelled by event hook.');
        }

        return $event->isValid;
    }

    protected function afterProcessPayment(Submission $submission, bool $result): bool
    {
        $event = new PaymentIntegrationProcessEvent([
            'submission' => $submission,
            'result' => $result,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_AFTER_PROCESS_PAYMENT, $event);

        if (!$event->isValid) {
            Integration::info($this, 'Payment processing marked as invalid by event hook.');
        }

        return $event->isValid;
    }

    protected function definePaymentFieldSettingsDefaults(): array
    {
        return [];
    }

    /**
     * Resolve a billing-details static-table row to a field reference handle or token.
     *
     * Payment provider billing tables store the selected field in each row's `value`
     * column (for example `billingDetails.billingName.value`), not on the row itself.
     */
    protected function getPaymentBillingFieldKey(string $rowKey): ?string
    {
        $raw = $this->getFieldSetting("billingDetails.{$rowKey}.value");

        if ($raw === null || $raw === '') {
            $raw = $this->getFieldSetting("billingDetails.{$rowKey}");
        }

        if (is_array($raw)) {
            $raw = $raw['value'] ?? '';
        }

        $fieldKey = $this->normalizeFieldMappingValue($raw);
        $fieldKey = str_replace('.__toString', '', $fieldKey);

        return $fieldKey !== '' ? $fieldKey : null;
    }

    protected function normalizeClientFieldReference(mixed $value): ?string
    {
        $raw = trim((string)$value);

        if ($raw === '') {
            return null;
        }

        $expression = References::parseReferenceExpression($raw);

        if ($expression->isValid && $expression->target === 'field' && $expression->identifier !== '') {
            return FieldReferenceHelper::resolveClientFieldKey($expression->identifier);
        }

        return $raw;
    }

}
