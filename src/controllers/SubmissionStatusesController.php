<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\SubmissionStatus;

use Craft;
use craft\helpers\Json;

use yii\web\HttpException;
use yii\web\Response;

class SubmissionStatusesController extends SettingsAccessController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $statuses = Formie::$plugin->getSubmissionStatuses()->getAllStatuses();

        return $this->renderTemplate('formie/settings/statuses', compact('statuses'));
    }

    public function actionEdit(int $id = null, SubmissionStatus $status = null): Response
    {
        $variables = compact('id', 'status');

        if (!$variables['status']) {
            if ($variables['id']) {
                $variables['status'] = Formie::$plugin->getSubmissionStatuses()->getStatusById($variables['id']);

                if (!$variables['status']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['status'] = new SubmissionStatus();
            }
        }

        if ($variables['status']->id) {
            $variables['title'] = $variables['status']->name;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new submission status');
        }

        return $this->renderTemplate('formie/settings/statuses/_edit', $variables);
    }

    public function actionSave(): void
    {
        $this->requirePostRequest();
        $request = $this->request;

        $status = new SubmissionStatus();
        $status->id = $request->getBodyParam('id');
        $status->name = $request->getBodyParam('name');
        $status->handle = $request->getBodyParam('handle');
        $status->color = $request->getBodyParam('color');
        $status->description = $request->getBodyParam('description');
        $status->isDefault = (bool)$request->getBodyParam('isDefault');

        if (Formie::$plugin->getSubmissionStatuses()->saveStatus($status)) {
            $this->setSuccessFlash(Craft::t('formie', 'Submission status saved.'));
            $this->redirectToPostedUrl($status);
        } else {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save submission status.'));
        }

        Craft::$app->getUrlManager()->setRouteParams(compact('status'));
    }

    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $ids = Json::decode($this->request->getRequiredBodyParam('ids'));

        if (Formie::$plugin->getSubmissionStatuses()->reorderStatuses($ids)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder submission statuses.')]);
    }

    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $statusId = (int)$this->request->getRequiredParam('id');

        if (Formie::$plugin->getSubmissionStatuses()->deleteStatusById($statusId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive submission status.')]);
    }
}
