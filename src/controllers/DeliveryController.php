<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\jobs\SendNotification;
use verbb\formie\jobs\TriggerIntegration;
use verbb\formie\models\IntegrationResult;

use Craft;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DeliveryController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionHistory(): Response
    {
        $this->requireCpRequest();
        $id = (int)$this->request->getRequiredParam('submissionId');
        $this->_requireSubmission($id);
        return $this->asJson(['attempts' => Formie::$plugin->getDeliveryAttempts()->history($id)]);
    }

    public function actionBundle(): Response
    {
        $this->requireCpRequest();
        $uid = (string)$this->request->getRequiredParam('uid');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $row = $attempts->get($uid);
        $submission = $this->_requireSubmission((int)$row['submissionId']);
        $bundle = $attempts->supportBundle($uid);
        $bundle['submissionUrl'] = $submission->getCpEditUrl();
        $bundle['canReconcile'] = Craft::$app->getUser()->checkPermission('formie-reconcileDeliveries');
        $canSave = Formie::$plugin->getPermissions()->canSaveSubmissions(Craft::$app->getUser()->getIdentity(), $submission->getForm());
        $bundle['canRetry'] = $canSave && !empty($bundle['result']['retryable']) && in_array($row['step'], ['integration', 'dispatch', 'notification'], true);
        $bundle['canForce'] = $row['step'] === 'integration' && Craft::$app->getUser()->checkPermission('formie-forceIntegrations') && $canSave;
        $bundle['canExportSensitive'] = Craft::$app->getUser()->checkPermission('formie-exportSensitiveDeliveryEvidence');
        return $this->asJson($bundle);
    }

    public function actionSensitiveEvidence(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('formie-exportSensitiveDeliveryEvidence');

        if ($this->request->getBodyParam('acknowledged') !== true) {
            throw new ForbiddenHttpException('Acknowledge sensitive evidence before exporting.');
        }
        $uid = (string)$this->request->getRequiredBodyParam('uid');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $this->_requireSubmission((int)$attempts->get($uid)['submissionId']);
        return $this->asJson($attempts->sensitiveEvidence($uid));
    }

    public function actionExportBundle(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        if ($this->request->getBodyParam('acknowledged') !== true) {
            throw new ForbiddenHttpException('Acknowledge personal data before exporting.');
        }
        $uid = (string)$this->request->getRequiredBodyParam('uid');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $this->_requireSubmission((int)$attempts->get($uid)['submissionId']);
        $attempts->checkpoint($uid, 'support-export', ['actorId' => Craft::$app->getUser()->getId()]);

        return $this->asJson($attempts->supportBundle($uid));
    }

    public function actionRetry(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $uid = (string)$this->request->getRequiredBodyParam('uid');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $row = $attempts->get($uid);
        $submission = $this->_requireSubmission((int)$row['submissionId']);

        if (!Formie::$plugin->getPermissions()->canSaveSubmissions(Craft::$app->getUser()->getIdentity(), $submission->getForm())) {
            throw new ForbiddenHttpException('Not permitted to retry this submission.');
        }
        $result = $row['result'] ? IntegrationResult::fromStorage(Json::decode($row['result'])) : null;

        if (!$result?->retryable || !in_array($row['step'], ['integration', 'dispatch', 'notification'], true)) {
            throw new ForbiddenHttpException('Only a definitely failed delivery can retry. Reconcile unknown operations first, then retry their parent.');
        }
        $job = $row['step'] === 'notification' ? new SendNotification(['deliveryAttemptUid' => $uid]) : new TriggerIntegration(['deliveryAttemptUid' => $uid]);
        $attempts->checkpoint($uid, 'retry-requested', ['actorId' => Craft::$app->getUser()->getId()]);
        Queue::push($job, Formie::$plugin->getSettings()->queuePriority);
        return $this->asJson(['queued' => true]);
    }

    public function actionForce(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requirePermission('formie-forceIntegrations');
        $submission = $this->_requireSubmission((int)$this->request->getRequiredBodyParam('submissionId'));
        $handle = (string)$this->request->getRequiredBodyParam('handle');

        foreach (Formie::$plugin->getIntegrations()->getAllEnabledIntegrationsForForm($submission->getForm()) as $integration) {
            if ($integration->handle === $handle && $integration->supportsPayloadSending()) {
                $result = Formie::$plugin->getIntegrationTriggers()->forceIntegration($integration, $submission, (string)$this->request->getRequiredBodyParam('reason'));
                return $this->asJson($result->toStorage());
            }
        }
        throw new NotFoundHttpException('Enabled form integration not found.');
    }

    public function actionReconcile(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $uid = (string)$this->request->getRequiredBodyParam('uid');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $this->_requireSubmission((int)$attempts->get($uid)['submissionId']);
        $result = match ($this->request->getBodyParam('outcome')) {
            'succeeded' => IntegrationResult::succeeded(),
            'notDelivered' => IntegrationResult::failed('confirmed_not_delivered', true),
            default => throw new ForbiddenHttpException('Choose a confirmed delivery outcome.'),
        };
        $attempts->reconcile($uid, $result, (string)$this->request->getRequiredBodyParam('reason'));
        return $this->asJson(['result' => $result->toStorage()]);
    }


    // Private Methods
    // =========================================================================

    private function _requireSubmission(int $id): Submission
    {
        $this->requirePermission('formie-viewDeliveryDiagnostics');
        $submission = Submission::find()->id($id)->status(null)->isIncomplete(null)->isSpam(null)->one();

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found.');
        }

        if (!Formie::$plugin->getPermissions()->canViewSubmissions(Craft::$app->getUser()->getIdentity(), $submission->getForm())) {
            throw new ForbiddenHttpException('Not permitted to view this submission.');
        }
        return $submission;
    }
}
