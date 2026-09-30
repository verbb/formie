<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\compatibility\payments\LegacyPaymentCredentials;
use verbb\formie\compatibility\payments\LegacyPaymentWebhooks;
use verbb\formie\elements\Submission;
use verbb\formie\enums\SubscriptionCancellationMode;
use verbb\formie\enums\SubscriptionStatus;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\FieldReferenceHelper;
use verbb\formie\helpers\IntegrationSecrets;
use verbb\formie\helpers\PaymentAmountHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\Notification;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\PaymentDecision;
use verbb\formie\models\PaymentFieldPayload;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Subscription;
use verbb\formie\models\payments\PaymentWebhookCommand;
use verbb\formie\models\payments\PaymentWebhookReceipt;
use verbb\formie\models\payments\SubscriptionSnapshot;
use verbb\formie\models\payments\VerifiedWebhookBatch;
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

use DateTimeImmutable;
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
    public const EVENT_BEFORE_VERIFY_WEBHOOK = 'beforeVerifyWebhook';
    public const EVENT_AFTER_VERIFY_WEBHOOK = 'afterVerifyWebhook';
    public const EVENT_WEBHOOK_FAILED = 'webhookFailed';
    public const EVENT_MODIFY_CURRENCY_OPTIONS = 'modifyCurrencyOptions';

    public const PAYMENT_TYPE_SINGLE = 'single';
    public const PAYMENT_TYPE_SUBSCRIPTION = 'subscription';
    
    public const VALUE_TYPE_FIXED = 'fixed';
    public const VALUE_TYPE_DYNAMIC = 'dynamic';


    // Traits
    // =========================================================================

    use LegacyPaymentWebhooks;
    use LegacyPaymentCredentials;


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
            $isSubscription = $this->getFieldSetting('type') === self::PAYMENT_TYPE_SUBSCRIPTION;
            $payment = $payments->prepareAttempt(
                $this,
                $submission,
                !$isSubscription,
                $isSubscription ? 'subscriptionSetup' : 'payment',
            );
            if ($payment->status === PaymentModel::STATUS_SUCCEEDED || ($payment->scope['providerOutcome']['status'] ?? null) === PaymentModel::STATUS_SUCCEEDED) {
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
            if ($isSubscription) {
                $payments->prepareSubscription($this, $submission);
            }
            $decision = $this->executePayment($submission);
            $payment = $payments->getPaymentById($payment->id);
            $payment->status = match ($decision->status) {
                PaymentDecision::STATUS_SUCCEEDED => PaymentModel::STATUS_SUCCEEDED,
                PaymentDecision::STATUS_FAILED => PaymentModel::STATUS_FAILED,
                PaymentDecision::STATUS_CANCELLED => PaymentModel::STATUS_CANCELLED,
                PaymentDecision::STATUS_UNKNOWN => PaymentModel::STATUS_UNKNOWN,
                PaymentDecision::STATUS_ACTION_REQUIRED => PaymentModel::STATUS_REQUIRES_ACTION,
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
            return $payment || $e instanceof \verbb\formie\errors\DeliveryOutcomeUnknownException ? PaymentDecision::unknown('Unable to confirm the payment outcome.', $this->handle, $payment?->reference)
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
            'monetaryPayments' => array_values(array_filter($payments, fn(PaymentModel $payment) => $payment->getIsMonetary())),
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

    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModule
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
            'surface' => BrowserModule::SURFACE_SERVER_RENDERED,
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

    public function getWebhookUrl(): string
    {
        if (!$this->uid) {
            throw new RuntimeException('Save the payment integration before registering its webhook URL.');
        }

        if (Craft::$app->getConfig()->getGeneral()->headlessMode) {
            $url = UrlHelper::actionUrl('formie/payment-webhooks/process-webhook', ['integrationUid' => $this->uid]);
        } else {
            $url = UrlHelper::siteUrl('formie/payment-webhooks/process-webhook', ['integrationUid' => $this->uid]);
        }

        return self::applyPaymentWebhookProxy($url);
    }

    /** @return SubscriptionCancellationMode[] */
    public function getSubscriptionCancellationModes(): array
    {
        return [SubscriptionCancellationMode::IMMEDIATE];
    }

    public function getDefaultSubscriptionCancellationMode(): SubscriptionCancellationMode
    {
        return $this->getSubscriptionCancellationModes()[0];
    }

    /**
     * Canonical cancellation boundary. The fallback isolates Formie 3 payment
     * integrations that still implement cancelSubscription(reference, params).
     */
    public function cancelSubscriptionSnapshot(Subscription $subscription, SubscriptionCancellationMode $mode): ?SubscriptionSnapshot
    {
        if (!method_exists($this, 'cancelSubscription')) {
            return null;
        }

        $data = $this->cancelSubscription($subscription->reference, [
            'cancelImmediately' => $mode === SubscriptionCancellationMode::IMMEDIATE,
        ]);

        if (!is_array($data)) {
            return null;
        }

        $immediate = $mode === SubscriptionCancellationMode::IMMEDIATE;

        return new SubscriptionSnapshot(
            $immediate ? SubscriptionStatus::CANCELLED : $subscription->getState(),
            'legacyCancellation',
            reference: $subscription->reference,
            cancelAt: $immediate ? null : $subscription->currentPeriodEndsAt ?? $subscription->nextPaymentAt,
            cancelledAt: $immediate ? new DateTimeImmutable() : null,
            endedAt: $immediate ? new DateTimeImmutable() : null,
            cancellationMode: $mode,
            rawData: $data,
        );
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

    public function receiveWebhook(PaymentWebhookCommand $command): Response
    {
        if (!$this->supportsWebhooks()) {
            throw new BadRequestHttpException('Integration does not support webhooks.');
        }

        if ($this->hasLegacyWebhookHandler()) {
            return $this->processLegacyWebhook();
        }

        return Formie::$plugin->getPaymentWebhooks()->receive($this, $command);
    }

    public function verifyWebhook(PaymentWebhookCommand $request): VerifiedWebhookBatch
    {
        throw new BadRequestHttpException('Integration does not implement verified webhooks.');
    }

    public function handleWebhook(PaymentWebhookReceipt $receipt): void
    {
    }

    public function getWebhookAcknowledgement(): Response
    {
        $response = Craft::$app->getRequest()->getIsConsoleRequest()
            ? new \craft\web\Response()
            : Craft::$app->getResponse();
        $response->format = Response::FORMAT_RAW;
        $response->setStatusCode(200);
        $response->data = 'ok';

        return $response;
    }

    public function getWebhookAccountFingerprint(string $environment, ?string $accountIdentity = null): string
    {
        return hash('sha256', static::class . '|' . $this->uid . '|' . $environment . '|' . ($accountIdentity ?? 'default'));
    }

    /** Account identity is independent of credential rotation whenever the provider exposes it. */
    public function getPaymentAccountFingerprint(): string
    {
        $identity = $this->getPaymentAccountIdentity();
        if ($identity === null) {
            // Providers without an account identifier remain conservatively bound to credentials.
            $credentials = [];
            $attributes = array_unique([...IntegrationSecrets::sensitiveAttributes($this), ...$this->getLegacyPaymentCredentialAttributes()]);
            foreach ($attributes as $attribute) {
                if (str_contains(strtolower($attribute), 'webhook')) {
                    continue;
                }
                $value = $this->$attribute;
                $credentials[$attribute] = is_string($value) ? App::parseEnv($value) : $value;
            }
            ksort($credentials);
            $identity = 'credentials:' . hash_hmac('sha256', Json::encode($credentials), Formie::$plugin->getSettings()->getSecurityKey());
        }
        return $this->getWebhookAccountFingerprint($this->getPaymentEnvironment(), $identity);
    }

    public function getPaymentEnvironment(): string
    {
        return property_exists($this, 'useSandbox') && App::parseBooleanEnv($this->useSandbox) ? 'test' : 'live';
    }

    protected function getPaymentAccountIdentity(): ?string
    {
        return null;
    }

    public function getReconciliationInterval(PaymentModel $payment): int
    {
        return 10;
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

    protected function defineSlotTag(string $key, RenderContext $context): ?SlotTag
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
