<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\base\Payment;
use verbb\formie\models\Payment as PaymentModel;

use Craft;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\View;

use Throwable;

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
        Craft::$app->getResponse()->setNoCacheHeaders();

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

                return $this->asJson([
                    'status' => 'failed',
                    'message' => Craft::t('formie', 'We were unable to verify your payment. Please try again or contact support.'),
                ]);
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

            // Has this submission already been marked as completed?
            if (!$submission->isIncomplete) {
                Formie::info('Payment poll: submission already completed (paymentUid {paymentUid}, submissionId {submissionId})', [
                    'paymentUid' => $paymentUid,
                    'submissionId' => $submission->id,
                ]);

                return $this->asJson([
                    'status' => 'failed',
                    'message' => Craft::t('formie', 'Submission has been completed.'),
                ]);
            }

            $submission->isIncomplete = false;
            Craft::$app->getElements()->saveElement($submission, false);

            // Fire any notifications/integrations (must not take down the JSON response — paid customers should still redirect)
            try {
                Formie::$plugin->getSubmissions()->sendNotifications($submission);
            } catch (Throwable $e) {
                Formie::error('Payment poll: sendNotifications failed for submissionId {submissionId}: {message}', [
                    'submissionId' => $submission->id,
                    'message' => $e->getMessage(),
                ]);
            }
            try {
                Formie::$plugin->getSubmissions()->triggerIntegrations($submission);
            } catch (Throwable $e) {
                Formie::error('Payment poll: triggerIntegrations failed for submissionId {submissionId}: {message}', [
                    'submissionId' => $submission->id,
                    'message' => $e->getMessage(),
                ]);
            }

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

            return $this->asJson([
                'status' => 'failed',
                'message' => Craft::t('formie', 'Your payment failed. Please try again.'),
            ]);
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

        $statusBefore = $payment->status;
        $integrationHandle = $integration->handle;

        // Some gateways (GoCardless) take over the status state handling
        // Always poll the API in dev mode, or when explicitly requested. Webhooks likely won't be delivered locally.
        $integration->getTransactionStatus($payment);

        $paymentAfter = Formie::$plugin->getPayments()->getPaymentByUid($paymentUid) ?? $payment;

        Formie::info('Payment return URL: paymentUid {paymentUid}, integration {integration}, submissionId {submissionId}, status {statusBefore} to {statusAfter}', [
            'paymentUid' => $paymentUid,
            'integration' => $integrationHandle,
            'submissionId' => $paymentAfter->submissionId,
            'statusBefore' => $statusBefore,
            'statusAfter' => $paymentAfter->status,
        ]);

        $general = Craft::$app->getConfig()->getGeneral();
        $pollStatusUrl = $general->headlessMode
            ? UrlHelper::actionUrl('formie/payment-webhooks/poll-status')
            : UrlHelper::siteUrl('formie/payment-webhooks/poll-status');

        return $this->renderTemplate('formie/integrations/payments/status', [
            'payment' => $paymentAfter,
            'pollStatusUrl' => $pollStatusUrl,
        ], View::TEMPLATE_MODE_CP);
    }
}
