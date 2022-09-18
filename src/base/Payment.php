<?php
namespace verbb\formie\base;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\events\PaymentWebhookEvent;
use verbb\formie\fields\formfields\Payment as PaymentField;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Notification;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use yii\base\Event;
use yii\web\BadRequestHttpException;
use yii\web\Response;

use Throwable;

use Twig\Markup;

use Money\Currencies\ISOCurrencies;

abstract class Payment extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_PROCESS_PAYMENT = 'beforeProcessPayment';
    public const EVENT_AFTER_PROCESS_PAYMENT = 'afterProcessPayment';
    public const EVENT_BEFORE_PROCESS_WEBHOOK = 'beforeProcessWebhook';
    public const EVENT_AFTER_PROCESS_WEBHOOK = 'afterProcessWebhook';
    public const EVENT_MODIFY_CURRENCY_OPTIONS = 'modifyCurrencyOptions';

    public const PAYMENT_TYPE_SINGLE = 'single';
    public const PAYMENT_TYPE_SUBSCRIPTION = 'subscription';
    
    public const VALUE_TYPE_FIXED = 'fixed';
    public const VALUE_TYPE_DYNAMIC = 'dynamic';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function typeName(): string
    {
        return Craft::t('formie', 'Payments');
    }

    /**
     * @inheritDoc
     */
    public static function supportsPayloadSending(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function hasFormSettings(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }

    /**
     * Returns an array of currencies.
     *
     * @return array
     */
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


    // Properties
    // =========================================================================

    public ?bool $throwApiError = false;

    private ?PaymentField $_field = null;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = $this->getIntegrationHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/payments/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = $this->getIntegrationHandle();

        return Craft::$app->getView()->renderTemplate("formie/integrations/payments/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, PaymentField $field, array $renderOptions = null): Markup
    {
        $handle = $this->getIntegrationHandle();

        $inputOptions = array_merge($field->getEmailOptions($submission, $notification, $value, $renderOptions), [
            'field' => $field,
            'integration' => $this,
        ]);
        
        return Template::raw(Craft::$app->getView()->renderTemplate("formie/integrations/payments/{$handle}/_email", $inputOptions));
    }

    /**
     * @inheritDoc
     */
    public function getSubmissionSummaryHtml($submission): ?string
    {
        $handle = $this->getIntegrationHandle();

        // Only show if there's payments for a submission
        $payments = $submission->getPayments();
        $subscriptions = $submission->getSubscriptions();

        if (!$payments && !$subscriptions) {
            return null;
        }

        return Craft::$app->getView()->renderTemplate("formie/integrations/payments/{$handle}/_submission-summary", [
            'integration' => $this,
            'form' => $submission,
            'payments' => $payments,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFrontEndHtml($field, $renderOptions): string
    {
        $handle = $this->getIntegrationHandle();
        
        if (!$this->hasValidSettings()) {
            return '';
        }

        $this->setField($field);

        return Craft::$app->getView()->renderTemplate("formie/integrations/payments/{$handle}/_input", [
            'field' => $field,
            'renderOptions' => $renderOptions,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/payments/edit/' . $this->id);
    }
    /**
     * @inheritDoc
     */
    public function getRedirectUri(): string
    {
        return UrlHelper::siteUrl('formie/payment-webhooks/process-webhook', ['handle' => $this->handle]);
    }

    /**
     * @inheritDoc
     */
    public function getGqlHandle(): string
    {
        return StringHelper::toCamelCase($this->handle . 'Payment');
    }

    /**
     * @inheritDoc
     */
    public function getAmount($submission): float
    {
        $amountType = $this->getFieldSetting('amountType');
        $amountFixed = $this->getFieldSetting('amountFixed');
        $amountVariable = $this->getFieldSetting('amountVariable');

        if ($amountType === Payment::VALUE_TYPE_FIXED) {
            return (float)$amountFixed;
        } else if ($amountType === Payment::VALUE_TYPE_DYNAMIC) {
            return (float)Variables::getParsedValue($amountVariable, $submission, $submission->getForm());
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getCurrency($submission): ?string
    {
        $currencyType = $this->getFieldSetting('currencyType');
        $currencyFixed = $this->getFieldSetting('currencyFixed');
        $currencyVariable = $this->getFieldSetting('currencyVariable');

        if ($currencyType === Payment::VALUE_TYPE_FIXED) {
            return (string)$currencyFixed;
        } else if ($currencyType === Payment::VALUE_TYPE_DYNAMIC) {
            return (string)Variables::getParsedValue($currencyVariable, $submission, $submission->getForm());
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function processWebhooks(): Response
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
                $response = $this->processWebhook();
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

    /**
     * @inheritDoc
     */
    public function getField(): ?PaymentField
    {
        return $this->_field;
    }

    /**
     * @inheritDoc
     */
    public function setField($value): void
    {
        $this->_field = $value;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSetting($setting, $default = null): mixed
    {
        if ($field = $this->getField()) {
            $providerSettings = $field->providerSettings[$this->handle] ?? [];

            return ArrayHelper::getValue($providerSettings, $setting, $default);
        }

        return null;
    }


    // Protected Methods
    // =========================================================================
    
    /**
     * @inheritDoc
     */
    protected function getIntegrationHandle(): string
    {
        return StringHelper::toKebabCase(static::displayName());
    }

    /**
     * @inheritDoc
     */
    protected function beforeProcessPayment(Submission $submission): bool
    {
        $event = new PaymentIntegrationProcessEvent([
            'submission' => $submission,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_BEFORE_PROCESS_PAYMENT, $event);

        if (!$event->isValid) {
            Integration::log($this, 'Payment processing cancelled by event hook.');
        }

        return $event->isValid;
    }

    /**
     * @inheritDoc
     */
    protected function afterProcessPayment(Submission $submission, bool $result): bool
    {
        $event = new PaymentIntegrationProcessEvent([
            'submission' => $submission,
            'result' => $result,
            'integration' => $this,
        ]);
        $this->trigger(self::EVENT_AFTER_PROCESS_PAYMENT, $event);

        if (!$event->isValid) {
            Integration::log($this, 'Payment processing marked as invalid by event hook.');
        }

        return $event->isValid;
    }
}
