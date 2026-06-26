<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\models\Payment as PaymentModel;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use Throwable;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

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

    protected array|bool|int $allowAnonymous = ['process-webhook', 'process-callback', 'poll-status', 'status'];


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

        return $integration->processWebhooks();
    }

    public function actionProcessCallback(): Response
    {
        // Query string overrides body param, which we sometimes don't want
        $handle = trim((string)($this->request->getBodyParam('handle') ?: $this->request->getParam('handle')));

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

        return $integration->processCallbacks();
    }

    public function actionPollStatus(): Response
    {
        $payment = $this->_requirePaymentFromStatusToken();

        $paymentUid = $this->request->getRequiredParam('paymentUid');
        $shouldCheckGateway = (bool)$this->request->getParam('checkGateway');

        if (!$integration = $payment->getIntegration()) {
            throw new NotFoundHttpException('Integration not found');
        }

        $integrationHandle = $integration->handle;
        $calledGateway = App::devMode() || $shouldCheckGateway;

        // Always poll the API in dev mode, or when explicitly requested. Webhooks likely won't be delivered locally.
        if ($calledGateway) {
            try {
                $integration->getTransaction($payment);
            } catch (Throwable $e) {
                Formie::error('Payment poll: gateway verification failed for paymentUid {paymentUid} ({integration}): {message}', [
                    'paymentUid' => $paymentUid,
                    'integration' => $integrationHandle,
                    'message' => $e->getMessage(),
                ]);

                return $this->asJson($this->_buildPaymentFailurePollResponse(
                    $payment,
                    Craft::t('formie', 'We were unable to verify your payment. Please try again or contact support.'),
                ));
            }

            if ($shouldCheckGateway) {
                Formie::info('Payment poll: gateway check for paymentUid {paymentUid} ({integration}), status is now {status}', [
                    'paymentUid' => $paymentUid,
                    'integration' => $integrationHandle,
                    'status' => $payment->status,
                ]);
            }
        }

        if ($payment->status === PaymentModel::STATUS_SUCCESS) {
            $submission = $payment->getSubmission();

            if (!$submission) {
                Formie::error('Payment poll: payment marked success but no submission for paymentUid {paymentUid}', [
                    'paymentUid' => $paymentUid,
                ]);

                return $this->asJson([
                    'status' => 'failed',
                    'message' => Craft::t('formie', 'Unable to find submission for payment.'),
                ]);
            }

            if ($submission->isIncomplete) {
                Formie::info('Payment poll: submission already completed (paymentUid {paymentUid}, submissionId {submissionId})', [
                    'paymentUid' => $paymentUid,
                    'submissionId' => $submission->id,
                ]);

                $replay = Formie::$plugin->getSubmissionProcessor()->replayPaymentIfSuccessful($payment);

                if ($replay && !$replay->response?->success) {
                    return $this->asJson([
                        'status' => 'failed',
                        'message' => Craft::t('formie', 'We were unable to finalize your payment-backed submission. Please try again or contact support.'),
                    ]);
                }
            }

            $form = $submission->getForm();
            $flashNamespace = $form->getFlashNamespace();
            $submitMessage = $form->settings->getSubmitActionMessage($submission);

            Formie::$plugin->getService()->setFlash($flashNamespace, 'submitted', true);
            if ($submitMessage) {
                Formie::$plugin->getService()->setNotice($flashNamespace, $submitMessage);
            }
            $url = '';

            // Handle heading back to the form and either redirecting to the form's redirect or show a message
            if ($form->settings->submitAction == 'message' || $form->settings->submitAction == 'reload') {
                // When reloading the page, provide a `submission` variable to pick up on the finalise submission
                Craft::$app->getUrlManager()->setRouteParams([
                    'submission' => $submission,
                ]);

                $url = $payment->redirectUrl;
            } else {
                $url = $form->getRedirectUrl(false, false);
            }

            $url = Formie::$plugin->getPayments()->resolvePaymentSuccessRedirectUrl($payment, $submission, $form, $url);

            Formie::info('Payment poll: finalising paymentUid {paymentUid}, submissionId {submissionId}, formId {formId}', [
                'paymentUid' => $paymentUid,
                'submissionId' => $submission->id,
                'formId' => $form->id,
            ]);

            return $this->asJson([
                'status' => 'success',
                'redirectUrl' => $url,
            ]);
        }

        if ($payment->status === PaymentModel::STATUS_FAILED) {
            Formie::info('Payment poll: gateway reports failed for paymentUid {paymentUid} ({integration})', [
                'paymentUid' => $paymentUid,
                'integration' => $integrationHandle,
            ]);

            return $this->asJson($this->_buildPaymentFailurePollResponse(
                $payment,
                Craft::t('formie', 'Your payment failed. Please try again.'),
            ));
        }

        if ($shouldCheckGateway) {
            Formie::info('Payment poll: still pending after gateway check (paymentUid {paymentUid}, {integration})', [
                'paymentUid' => $paymentUid,
                'integration' => $integrationHandle,
            ]);
        }

        return $this->asJson(['status' => 'pending']);
    }

    public function actionStatus(): Response
    {
        $payment = $this->_requirePaymentFromStatusToken(true);

        if (!$integration = $payment->getIntegration()) {
            throw new NotFoundHttpException('Integration not found');
        }

        // Some gateways (GoCardless) take over the status state handling
        // Always poll the API in dev mode, or when explicitly requested. Webhooks likely won't be delivered locally.
        $integration->getTransactionStatus($payment);

        return $this->renderTemplate('formie/integrations/payments/status', [
            'payment' => $payment,
            'statusToken' => PaymentAccess::issueStatusToken($payment),
        ]);
    }

    private function _requirePaymentFromStatusToken(bool $gatewayCheck = false): PaymentModel
    {
        $statusToken = (string)$this->request->getRequiredParam('statusToken');
        $payload = PaymentAccess::resolveStatusToken($statusToken);

        if (!$payload) {
            throw new NotFoundHttpException('Payment not found');
        }

        $this->_enforceStatusTokenRateLimit($statusToken, $gatewayCheck || (bool)$this->request->getParam('checkGateway'));

        $payment = Formie::$plugin->getPayments()->getPaymentByUid($payload['paymentUid']);

        if (!$payment || (int)$payment->id !== (int)$payload['paymentId']) {
            throw new NotFoundHttpException('Payment not found');
        }

        return $payment;
    }

    private function _buildPaymentFailurePollResponse(PaymentModel $payment, string $defaultMessage): array
    {
        $message = trim((string)($payment->message ?? '')) ?: $defaultMessage;
        $response = [
            'status' => 'failed',
            'message' => $message,
        ];

        $submission = $payment->getSubmission();

        if (!$submission instanceof Submission) {
            return $response;
        }

        $form = $submission->getForm();

        if (!$form instanceof Form) {
            return $response;
        }

        Formie::$plugin->getService()->setError($form->getFlashNamespace(), $message);

        $redirectUrl = Formie::$plugin->getPayments()->resolvePaymentFailureRedirectUrl($payment, $submission, $form);

        if ($redirectUrl !== '') {
            $response['redirectUrl'] = $redirectUrl;
        }

        return $response;
    }

    private function _enforceStatusTokenRateLimit(string $statusToken, bool $gatewayCheck): void
    {
        $limit = $gatewayCheck ? self::STATUS_GATEWAY_CHECK_RATE_LIMIT : self::STATUS_POLL_RATE_LIMIT;
        $window = self::STATUS_RATE_WINDOW_SECONDS;
        $ipAddress = Craft::$app->getRequest()->getUserIP();
        $cacheKey = 'formie.payment-status-rate.' . md5($statusToken . '|' . ($gatewayCheck ? 'gateway' : 'poll') . '|' . $ipAddress);
        $mutexKey = 'formie.payment-status-rate-lock.' . md5($statusToken . '|' . ($gatewayCheck ? 'gateway' : 'poll') . '|' . $ipAddress);
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + $window,
                ];
            }

            if ((int)$entry['count'] >= $limit) {
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, (int)$entry['resetAt'] - $now));

                throw new TooManyRequestsHttpException('Too many payment status requests. Please try again shortly.');
            }

            $entry['count'] = (int)$entry['count'] + 1;
            $cache->set($cacheKey, $entry, max(1, (int)$entry['resetAt'] - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
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
