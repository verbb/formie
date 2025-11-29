<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\models\Payment as PaymentModel;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\web\Controller;
use craft\web\View;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentWebhooksController extends Controller
{
    // Properties
    // =========================================================================

    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = ['process-webhook', 'process-callback', 'poll-status', 'status'];


    // Public Methods
    // =========================================================================

    public function actionProcessWebhook(): Response
    {
        $handle = $this->request->getRequiredParam('handle');

        if (!$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)) {
            throw new NotFoundHttpException('Integration not found');
        }

        if (!($integration instanceof Payment)) {
            throw new BadRequestHttpException('Invalid integration: ' . $handle);
        }

        return $integration->processWebhooks();
    }

    public function actionProcessCallback(): Response
    {
        // Query string overrides body param, which we sometimes don't want
        $handle = $this->request->getBodyParam('handle') ?: $this->request->getParam('handle');

        if (!$handle) {
            throw new NotFoundHttpException('Integration ' . $handle . ' not found');
        }

        if (!$integration = Formie::$plugin->getIntegrations()->getIntegrationByHandle($handle)) {
            throw new NotFoundHttpException('Integration not found');
        }

        if (!($integration instanceof Payment)) {
            throw new BadRequestHttpException('Invalid integration: ' . $handle);
        }

        return $integration->processCallbacks();
    }

    public function actionPollStatus(): Response
    {
        $paymentUid = $this->request->getRequiredParam('paymentUid');
        $shouldCheckGateway = (bool)$this->request->getParam('checkGateway');

        if (!$paymentUid) {
            throw new NotFoundHttpException('Payment ' . $paymentUid . ' not found');
        }

        if (!$payment = Formie::$plugin->getPayments()->getPaymentByUid($paymentUid)) {
            throw new NotFoundHttpException('Payment not found');
        }

        if (!$integration = $payment->getIntegration()) {
            throw new NotFoundHttpException('Integration not found');
        }

        // Always poll the API in dev mode, or when explicitly requested. Webhooks likely won't be delivered locally.
        if (App::devMode() || $checkGateway) {
            try {
                $integration->getTransaction($payment);
            } catch (Throwable $e) {
                return $this->asJson([
                    'status' => 'failed',
                    'message' => Craft::t('formie', 'We were unable to verify your payment. Please try again or contact support.'),
                ]);
            }
        }

        if ($payment->status === PaymentModel::STATUS_SUCCESS) {
            $submission = $payment->getSubmission();

            if (!$submission) {
                return $this->asJson([
                    'status' => 'failed',
                    'message' => Craft::t('formie', 'Unable to find submission for payment.'),
                ]);
            }

            // Has this submission already been marked as completed?
            if (!$submission->isIncomplete) {
                return $this->asJson([
                    'status' => 'failed',
                    'message' => Craft::t('formie', 'Submission has been completed.'),
                ]);
            }

            $submission->isIncomplete = false;
            Craft::$app->getElements()->saveElement($submission, false);

            // Fire any notifications/integrations
            Formie::$plugin->getSubmissions()->sendNotifications($submission);
            Formie::$plugin->getSubmissions()->triggerIntegrations($submission);

            $form = $submission->getForm();

            Formie::$plugin->getService()->setFlash($form->id, 'submitted', true);
            $url = '';

            // Handle heading back to the form and either redirecting to the form's redirect or show a message
            if ($form->settings->submitAction == 'message' || $form->settings->submitAction == 'reload') {
                if ($form->settings->submitAction == 'message') {
                    Formie::$plugin->getService()->setNotice($form->id, $form->settings->getSubmitActionMessage($submission));
                }

                // When reloading the page, provide a `submission` variable to pick up on the finalise submission
                Craft::$app->getUrlManager()->setRouteParams([
                    'submission' => $submission,
                ]);

                $url = $payment->redirectUrl;
            } else {
                $url = $form->getRedirectUrl(false, false);
            }

            return $this->asJson([
                'status' => 'success',
                'redirectUrl' => $url,
            ]);
        }

        if ($payment->status === PaymentModel::STATUS_FAILED) {
            return $this->asJson([
                'status' => 'failed',
                'message' => Craft::t('formie', 'Your payment failed. Please try again.'),
            ]);
        }

        return $this->asJson(['status' => 'pending']);
    }

    public function actionStatus(): Response
    {
        $paymentUid = $this->request->getRequiredParam('paymentUid');

        if (!$paymentUid) {
            throw new NotFoundHttpException('Payment ' . $paymentUid . ' not found');
        }

        if (!$payment = Formie::$plugin->getPayments()->getPaymentByUid($paymentUid)) {
            throw new NotFoundHttpException('Payment not found');
        }

        if (!$integration = $payment->getIntegration()) {
            throw new NotFoundHttpException('Integration not found');
        }

        // Some gateways (GoCardless) take over the status state handling
        // Always poll the API in dev mode, or when explicitly requested. Webhooks likely won't be delivered locally.
        $integration->getTransactionStatus($payment);

        return $this->renderTemplate('formie/integrations/payments/status', ['payment' => $payment], View::TEMPLATE_MODE_CP);
    }
}
