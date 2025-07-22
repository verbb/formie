<?php
namespace verbb\formie\base;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPaymentCurrencyOptionsEvent;
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\events\PaymentCallbackEvent;
use verbb\formie\events\PaymentWebhookEvent;
use verbb\formie\fields\Payment as PaymentField;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\Html;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Variables;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Notification;
use verbb\formie\models\Payment as PaymentModel;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
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

    public function supportsWebhooks(): bool
    {
        return false;
    }

    public function supportsCallbacks(): bool
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


    // Properties
    // =========================================================================

    public ?bool $throwApiError = false;

    private ?PaymentField $_field = null;


    // Public Methods
    // =========================================================================

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
        return UrlHelper::cpUrl('formie/settings/payments/edit/' . $this->id);
    }

    public function getIconUrl(): string
    {
        $handle = $this->getIntegrationHandle();

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/payments/{$handle}.svg");
    }

    public function getSettingsHtml(): ?string
    {
        $handle = $this->getIntegrationHandle();
        $variables = $this->getSettingsHtmlVariables();

        return Craft::$app->getView()->renderTemplate("formie/integrations/payments/{$handle}/_plugin-settings", $variables);
    }

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, PaymentField $field, array $renderOptions = null): Markup
    {
        $handle = $this->getIntegrationHandle();

        $inputOptions = array_merge($field->getEmailOptions($submission, $notification, $value, $renderOptions), [
            'field' => $field,
            'integration' => $this,
        ]);
        
        return Template::raw($notification->renderTemplate("integrations/payments/{$handle}/field", $inputOptions));
    }

    public function getSubmissionSummaryHtml(Submission $submission): ?string
    {
        $handle = $this->getIntegrationHandle();

        // Only show if there's payments for a submission
        $payments = $submission->getPayments();
        $subscriptions = $submission->getSubscriptions();

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

    public function getFrontEndHtml(FieldInterface $field, array $renderOptions = []): string
    {
        $handle = $this->getIntegrationHandle();
        $variables = $this->getFrontEndHtmlVariables();
        
        if (!$this->hasValidSettings()) {
            return '';
        }

        $this->setField($field);

        $variables['field'] = $field;
        $variables['renderOptions'] = $renderOptions;

        return $field->getForm()->renderTemplate("integrations/payments/{$handle}/field", $variables);
    }

    public function getFrontEndHtmlVariables(): array
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

        // For local development, we should use a proxy to ensure it works
        if (App::devMode()) {
            return "https://proxy.verbb.io?return=$url";
        }

        return $url;
    }

    public function getGqlHandle(): string
    {
        return StringHelper::toCamelCase($this->handle . 'Payment');
    }

    public function getAmount(Submission $submission): float
    {
        $amount = 0;
        $amountType = $this->getFieldSetting('amountType');
        $amountFixed = $this->getFieldSetting('amountFixed');
        $amountVariable = $this->getFieldSetting('amountVariable');

        if ($amountType === Payment::VALUE_TYPE_FIXED) {
            $amount = $amountFixed;
        } else if ($amountType === Payment::VALUE_TYPE_DYNAMIC) {
            $amount = Variables::getParsedValue($amountVariable, $submission, $submission->getForm());

            // Just in case there's a currency symbol in the value
            $symbols = ['$','€','£','¥','₣','₹','₻','₽','₾','₺','₼','₸','฿','원','₫','₱','₳','₵'];

            $amount = str_replace($symbols, '', $amount);
        }

        return (float)$amount;
    }

    public function getCurrency(Submission $submission): ?string
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

        // Fire a 'afterProcessCallback' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_PROCESS_CALLBACK)) {
            $this->trigger(self::EVENT_BEFORE_PROCESS_CALLBACK, new PaymentCallbackEvent([
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

    public function defineHtmlTag(string $key, array $context = []): ?HtmlTag
    {
        return null;
    }


    // Protected Methods
    // =========================================================================
    
    protected function getIntegrationHandle(): string
    {
        return StringHelper::toKebabCase(static::className());
    }
    
    protected function getPaymentFieldValue(Submission $submission): array
    {
        if ($field = $this->getField()) {
            // Find the field in the submission. Take note to check for nested fields. The format will be either `fieldHandle` or `group[fieldHandle]
            $fieldHandle = Html::getInputNameAttribute($field->getFullHandle());

            // Lookup the field value, ensuring we return an array with what we need.
            return $this->getMappedFieldValue($fieldHandle, $submission, new IntegrationField([
                'type' => IntegrationField::TYPE_ARRAY,
            ]));
        }

        return [];
    }

    protected function addFieldError(Submission $submission, string $message): void
    {
        if ($field = $this->getField()) {
            $handle = [];

            if ($parentField = $field->getParentField()) {
                $handle[] = $parentField->handle . '[0]';
            }

            $handle[] = $field->handle;

            $submission->addError(implode('.', $handle),  $message);
        }
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
}
