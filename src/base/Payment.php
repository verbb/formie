<?php
namespace verbb\formie\base;

use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\events\PaymentIntegrationProcessEvent;
use verbb\formie\events\PaymentWebhookEvent;
use verbb\formie\fields\formfields\Payment as PaymentField;
use verbb\formie\helpers\UrlHelper as FormieUrlHelper;
use verbb\formie\models\Notification;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use yii\web\BadRequestHttpException;
use yii\web\Response;

use Throwable;

use Twig\Markup;

abstract class Payment extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_PROCESS_PAYMENT = 'beforeProcessPayment';
    public const EVENT_AFTER_PROCESS_PAYMENT = 'afterProcessPayment';
    public const EVENT_BEFORE_PROCESS_WEBHOOK = 'beforeProcessWebhook';
    public const EVENT_AFTER_PROCESS_WEBHOOK = 'afterProcessWebhook';

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
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
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
    public function init(): void
    {
        // Throw an error if we're in `devMode` to help with local development - even if opting not to
        // which is the default for payment integrations, because unlike other integrations, these don't
        // run via the queue, but for non-devMode, we of course don't want to throw errors inline.
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $this->throwApiError = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/payments/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/payments/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, PaymentField $field, array $options = null): Markup
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        $inputOptions = array_merge($field->getEmailOptions($submission, $notification, $value, $options), [
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
        $handle = StringHelper::toKebabCase(static::displayName());

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
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/payments/edit/' . $this->id);
    }
    /**
     * @inheritDoc
     */
    public function getRedirectUri(): string
    {
        return FormieUrlHelper::siteActionUrl('formie/payment-webhooks/process-webhook', ['handle' => $this->handle]);
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
