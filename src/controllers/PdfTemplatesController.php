<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\models\PdfTemplate;

use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\Json;
use craft\web\Controller;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

use Throwable;

class PdfTemplatesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $pdfTemplates = Formie::$plugin->getPdfTemplates()->getAllTemplates();

        return $this->renderTemplate('formie/settings/pdf-templates', compact('pdfTemplates'));
    }

    public function actionEdit(int $id = null, PdfTemplate $template = null): Response
    {
        $variables = compact('id', 'template');

        if (!$variables['template']) {
            if ($variables['id']) {
                $variables['template'] = Formie::$plugin->getPdfTemplates()->getTemplateById($variables['id']);

                if (!$variables['template']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['template'] = new PdfTemplate();
            }
        }

        if ($variables['template']->id) {
            $variables['title'] = $variables['template']->name;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new template');
        }

        return $this->renderTemplate('formie/settings/pdf-templates/_edit', $variables);
    }

    public function actionSave(): void
    {
        $this->requirePostRequest();

        $request = $this->request;

        $template = new PdfTemplate();
        $template->id = $request->getBodyParam('id');
        $template->name = $request->getBodyParam('name');
        $template->handle = $request->getBodyParam('handle');
        $template->template = preg_replace('/\/index(?:\.html|\.twig)?$/', '', $request->getBodyParam('template'));
        $template->filenameFormat = $request->getBodyParam('filenameFormat');

        // Save it
        if (Formie::$plugin->getPdfTemplates()->saveTemplate($template)) {
            $this->setSuccessFlash(Craft::t('formie', 'Template saved.'));
            $this->redirectToPostedUrl($template);
        } else {
            $this->setFailFlash(Craft::t('formie', 'Couldn’t save template.'));
        }

        Craft::$app->getUrlManager()->setRouteParams(compact('template'));
    }

    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $ids = Json::decode($this->request->getRequiredBodyParam('ids'));

        if ($success = Formie::$plugin->getPdfTemplates()->reorderTemplates($ids)) {
            return $this->asJson(['success' => $success]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder templates.')]);
    }

    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $templateId = (int)$this->request->getRequiredParam('id');

        if (Formie::$plugin->getPdfTemplates()->deleteTemplateById($templateId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive template.')]);
    }
}
