<?php
namespace verbb\formie\compatibility\payments;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Payment;
use verbb\formie\events\PaymentWebhookEvent;
use verbb\formie\models\payments\PaymentWebhookCommand;

use Craft;

use yii\web\BadRequestHttpException;
use yii\web\Response;

use ReflectionMethod;
use Throwable;

trait LegacyPaymentWebhooks
{
    // Public Methods
    // =========================================================================

    /**
     * Formie 3 payment integrations used the generic redirect-URI name for
     * their webhook endpoint.
     *
     * @deprecated in 4.0.0. Use getWebhookUrl().
     */
    public function getRedirectUri(): string
    {
        return $this->getWebhookUrl();
    }

    /**
     * Runs the Formie 3 synchronous webhook surface for compatibility and
     * direct migration testing. Canonical HTTP intake uses receiveWebhook().
     *
     * @deprecated in 4.0.0. Webhook controllers use receiveWebhook().
     */
    public function processWebhooks(?PaymentWebhookCommand $command = null): Response
    {
        try {
            if (!$this->supportsWebhooks()) {
                throw new BadRequestHttpException('Integration does not support webhooks.');
            }

            if ($this->hasLegacyWebhookHandler()) {
                return $this->processLegacyWebhook();
            }

            $command ??= PaymentWebhookCommand::fromRequest((int)$this->id);

            return Formie::$plugin->getPaymentWebhooks()->receive($this, $command, true);
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'Exception while processing webhook: “{message}” {file}:{line}. Trace: “{trace}”.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]));

            return $this->createWebhookErrorResponse($e);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function hasLegacyWebhookHandler(): bool
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled() || !method_exists($this, 'processWebhook')) {
            return false;
        }

        $method = new ReflectionMethod($this, 'processWebhook');

        return $method->isPublic() && $method->getNumberOfRequiredParameters() === 0;
    }

    /**
     * Isolates the inseparable Formie 3 verify-and-handle hook from the Formie 4 inbox.
     */
    protected function processLegacyWebhook(): Response
    {
        if ($this->hasEventHandlers(Payment::EVENT_BEFORE_PROCESS_WEBHOOK)) {
            $this->trigger(Payment::EVENT_BEFORE_PROCESS_WEBHOOK, new PaymentWebhookEvent([
                'integration' => $this,
            ]));
        }

        try {
            $response = (new ReflectionMethod($this, 'processWebhook'))->invoke($this);

            if (!$response instanceof Response) {
                throw new BadRequestHttpException('Legacy webhook handlers must return a response.');
            }
        } catch (Throwable $e) {
            Integration::error($this, Craft::t('formie', 'Exception while processing legacy webhook: “{message}” {file}:{line}. Trace: “{trace}”.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]));

            $response = $this->createWebhookErrorResponse($e);
        }

        if ($this->hasEventHandlers(Payment::EVENT_AFTER_PROCESS_WEBHOOK)) {
            $this->trigger(Payment::EVENT_AFTER_PROCESS_WEBHOOK, new PaymentWebhookEvent([
                'integration' => $this,
                'response' => $response,
            ]));
        }

        return $response;
    }

    protected function createWebhookErrorResponse(Throwable $error): Response
    {
        $response = Craft::$app->getRequest()->getIsConsoleRequest()
            ? new \craft\web\Response()
            : Craft::$app->getResponse();
        $response->setStatusCodeByException($error);
        $response->format = Response::FORMAT_RAW;
        $response->data = 'error';

        return $response;
    }
}
