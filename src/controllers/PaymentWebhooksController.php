<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\payments\PaymentWebhookCommand;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

use Throwable;

class PaymentWebhooksController extends Controller
{
    // Constants
    // =========================================================================

    private const STATUS_POLL_RATE_LIMIT = 120;
    private const STATUS_GATEWAY_CHECK_RATE_LIMIT = 20;
    private const STATUS_RATE_WINDOW_SECONDS = 60;
    private const WEBHOOK_RATE_LIMIT = 60;
    private const WEBHOOK_RATE_WINDOW_SECONDS = 60;
    

    // Properties
    // =========================================================================

    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = ['process-webhook'];


    // Public Methods
    // =========================================================================

    public function actionProcessWebhook(): Response
    {
        $handle = trim((string)$this->request->getParam('handle', ''));

        if ($handle === '') {
            throw new NotFoundHttpException('Integration not found');
        }

        if (!$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)) {
            throw new NotFoundHttpException('Integration not found');
        }

        if (!($integration instanceof Payment)) {
            throw new NotFoundHttpException('Integration not found');
        }

        $this->_enforceWebhookRateLimit($handle);

        return $integration->processWebhooks(PaymentWebhookCommand::fromRequest($integration->id));
    }

    private function _enforceWebhookRateLimit(string $handle): void
    {
        $window = self::WEBHOOK_RATE_WINDOW_SECONDS;
        $ipAddress = Craft::$app->getRequest()->getUserIP();
        $providerReference = (string)(Craft::$app->getRequest()->getParam('id') ?: md5(Craft::$app->getRequest()->getRawBody()));
        $fingerprint = md5($handle . '|' . $providerReference . '|' . $ipAddress);
        $cacheKey = 'formie.payment-webhook-rate.' . $fingerprint;
        $mutexKey = 'formie.payment-webhook-rate-lock.' . $fingerprint;
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;
        if (!$lockAcquired) { throw new TooManyRequestsHttpException('Webhook processing is busy.'); }

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + $window,
                ];
            }

            if ((int)$entry['count'] >= self::WEBHOOK_RATE_LIMIT) {
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, (int)$entry['resetAt'] - $now));

                throw new TooManyRequestsHttpException('Too many payment webhook requests. Please try again shortly.');
            }

            $entry['count'] = (int)$entry['count'] + 1;
            $cache->set($cacheKey, $entry, max(1, (int)$entry['resetAt'] - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
    }
}
