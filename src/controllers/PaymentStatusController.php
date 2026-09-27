<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\PaymentResumeMode;
use verbb\formie\helpers\PaymentAccess;
use verbb\formie\models\Payment as PaymentModel;
use verbb\formie\models\payments\PaymentStatusCommand;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

use Throwable;

class PaymentStatusController extends Controller
{
    // Constants
    // =========================================================================

    private const STATUS_POLL_RATE_LIMIT = 120;
    private const STATUS_GATEWAY_CHECK_RATE_LIMIT = 20;
    private const STATUS_RATE_WINDOW_SECONDS = 60;


    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['poll-status', 'status'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        $profile = \verbb\formie\helpers\BrowserRequestProfile::enter();
        \verbb\formie\helpers\CrossOriginRequestHelper::applyHeaders($this->request, $this->response);
        if ($profile === \verbb\formie\helpers\BrowserRequestProfile::CROSS_ORIGIN) {
            $this->enableCsrfValidation = false;
        }

        if ($this->request->getIsOptions()) {
            $this->response->setStatusCode(204);
            return false;
        }

        return parent::beforeAction($action);
    }

    public function actionPollStatus(): Response
    {
        $payment = $this->_requirePaymentFromStatusToken();

        // Status token is the capability; do not require a client-supplied paymentUid.
        $paymentUid = (string)$payment->uid;
        $command = new PaymentStatusCommand((string)$this->request->getRequiredParam('statusToken'));
        $shouldCheckGateway = $command->mode() === PaymentResumeMode::RECONCILE;

        if (!$integration = $payment->getIntegration()) {
            throw new NotFoundHttpException('Integration not found');
        }

        $integrationHandle = $integration->handle;
        $calledGateway = $shouldCheckGateway;

        // Only a reconciliation capability permits a provider read.
        if ($calledGateway) {
            try {
                $payment = Formie::$plugin->getPayments()->resolveStatus($command);
            } catch (Throwable $e) {
                Formie::error('Payment poll: gateway verification failed for paymentUid {paymentUid} ({integration}): {message}', [
                    'paymentUid' => $paymentUid,
                    'integration' => $integrationHandle,
                    'message' => $e->getMessage(),
                ]);

                return $this->asJson(['status' => 'unknown', 'message' => Craft::t('formie', 'Payment verification is pending. Please check again shortly.')]);
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
                return $this->asJson(['status' => 'pending', 'message' => Craft::t('formie', 'Payment received; submission completion is pending.')]);
            }

            $form = $submission->getForm();
            $flashNamespace = $form->getFlashNamespace();
            $submitMessage = $form->settings->getSubmitActionMessage($submission);

            Formie::$plugin->getService()->setFlash($flashNamespace, 'submitted', true);
            if ($submitMessage) {
                Formie::$plugin->getService()->setNotice($flashNamespace, $submitMessage);
            }
            $url = '';

            $completion = (new \verbb\formie\services\CompletionResolver())->resolve($form, $submission);
            $url = $completion->url;
            $returnUrl = \verbb\formie\helpers\CompletionRedirectPolicy::validate((string)$payment->redirectUrl);

            Formie::info('Payment poll: finalising paymentUid {paymentUid}, submissionId {submissionId}, formId {formId}', [
                'paymentUid' => $paymentUid,
                'submissionId' => $submission->id,
                'formId' => $form->id,
            ]);

            return $this->asJson([
                'status' => 'success',
                'completion' => $completion->toArray(),
                'returnUrl' => $returnUrl,
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

        return $this->asJson(['status' => in_array($payment->status, [PaymentModel::STATUS_UNKNOWN, PaymentModel::STATUS_CANCELLED], true) ? $payment->status : 'pending']);
    }

    public function actionStatus(): Response
    {
        $payment = $this->_requirePaymentFromStatusToken(true);

        if (!$integration = $payment->getIntegration()) {
            throw new NotFoundHttpException('Integration not found');
        }

        // Some gateways (GoCardless) take over the status state handling
        // Only a reconciliation capability permits a provider read.
        // Rendering a status page never changes payment state.

        return $this->renderTemplate('formie/integrations/payments/status', [
            'payment' => $payment,
            'statusToken' => (string)$this->request->getRequiredParam('statusToken'),
        ], \craft\web\View::TEMPLATE_MODE_CP);
    }


    // Private Methods
    // =========================================================================

    private function _requirePaymentFromStatusToken(bool $gatewayCheck = false): PaymentModel
    {
        $statusToken = (string)$this->request->getRequiredParam('statusToken');
        $payload = PaymentAccess::resolveStatusToken($statusToken);

        if (!$payload) {
            throw new NotFoundHttpException('Payment not found');
        }

        $this->_enforceStatusTokenRateLimit($statusToken, $payload['purpose'] === 'reconcile');

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
        if (!$lockAcquired) { throw new TooManyRequestsHttpException('Payment status is busy.'); }

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

}
