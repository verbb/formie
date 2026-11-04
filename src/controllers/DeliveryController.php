<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Submission;

use Craft;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DeliveryController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionBundle(): Response
    {
        $this->requireCpRequest();

        $uid = (string)$this->request->getRequiredParam('uid');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $row = $attempts->get($uid);
        $submission = $this->_requireSubmission((int)$row['submissionId']);
        $bundle = $attempts->supportBundle($uid);
        $bundle['submissionUrl'] = $submission->getCpEditUrl();

        return $this->asJson($bundle);
    }

    public function actionExportBundle(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $uid = (string)$this->request->getRequiredBodyParam('uid');
        $attempts = Formie::$plugin->getDeliveryAttempts();
        $this->_requireSubmission((int)$attempts->get($uid)['submissionId']);
        $attempts->checkpoint($uid, 'support-export', ['actorId' => Craft::$app->getUser()->getId()]);

        return $this->asJson($attempts->supportBundle($uid));
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
