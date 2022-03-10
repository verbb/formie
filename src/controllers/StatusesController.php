<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\Status;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;

use Throwable;

use yii\web\HttpException;
use yii\web\Response;

class StatusesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return Response
     */
    public function actionIndex(): Response
    {
        $statuses = Formie::$plugin->getStatuses()->getAllStatuses();

        return $this->renderTemplate('formie/settings/statuses', compact('statuses'));
    }

    /**
     * @param int|null $id
     * @param Status|null $status
     * @return Response
     * @throws HttpException
     */
    public function actionEdit(int $id = null, Status $status = null): Response
    {
        $variables = compact('id', 'status');

        if (!$variables['status']) {
            if ($variables['id']) {
                $variables['status'] = Formie::$plugin->getStatuses()->getStatusById($variables['id']);

                if (!$variables['status']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['status'] = new Status();
            }
        }

        if ($variables['status']->id) {
            $variables['title'] = $variables['status']->name;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new status');
        }

        return $this->renderTemplate('formie/settings/statuses/_edit', $variables);
    }

    /**
     * @throws Throwable
     */
    public function actionSave(): void
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $status = new Status();
        $status->id = $request->getBodyParam('id');
        $status->name = $request->getBodyParam('name');
        $status->handle = $request->getBodyParam('handle');
        $status->color = $request->getBodyParam('color');
        $status->description = $request->getBodyParam('description');
        $status->isDefault = (bool)$request->getBodyParam('isDefault');

        // Save it
        if (Formie::$plugin->getStatuses()->saveStatus($status)) {
            Craft::$app->getSession()->setNotice(Craft::t('formie', 'Status saved.'));
            $this->redirectToPostedUrl($status);
        } else {
            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save status.'));
        }

        Craft::$app->getUrlManager()->setRouteParams(compact('status'));
    }

    /**
     * @return Response
     * @throws Throwable
     */
    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $ids = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));

        if (Formie::$plugin->getStatuses()->reorderStatuses($ids)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder statuses.')]);
    }

    /**
     * @return Response
     * @throws Throwable
     */
    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $statusId = (int)Craft::$app->getRequest()->getRequiredParam('id');

        if (Formie::$plugin->getStatuses()->deleteStatusById($statusId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive status.')]);
    }
}
