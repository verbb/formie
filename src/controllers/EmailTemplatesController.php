<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\FileHelper;
use verbb\formie\models\EmailTemplate;

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

class EmailTemplatesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $emailTemplates = Formie::$plugin->getEmailTemplates()->getAllTemplates();

        return $this->renderTemplate('formie/settings/email-templates', compact('emailTemplates'));
    }

    public function actionEdit(int $id = null, EmailTemplate $template = null): Response
    {
        $variables = compact('id', 'template');

        if (!$variables['template']) {
            if ($variables['id']) {
                $variables['template'] = Formie::$plugin->getEmailTemplates()->getTemplateById($variables['id']);

                if (!$variables['template']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['template'] = new EmailTemplate();
            }
        }

        if ($variables['template']->id) {
            $variables['title'] = $variables['template']->name;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new template');
        }

        return $this->renderTemplate('formie/settings/email-templates/_edit', $variables);
    }

    public function actionSave(): void
    {
        $this->requirePostRequest();

        $request = $this->request;

        $template = new EmailTemplate();
        $template->id = $request->getBodyParam('id');
        $template->name = $request->getBodyParam('name');
        $template->handle = $request->getBodyParam('handle');
        $template->template = preg_replace('/\/index(?:\.html|\.twig)?$/', '', $request->getBodyParam('template'));
        $template->copyTemplates = (bool)$request->getBodyParam('copyTemplates', false);

        // Save it
        if (Formie::$plugin->getEmailTemplates()->saveTemplate($template)) {
            if ($template->copyTemplates) {
                FileHelper::copyTemplateDirectory('@verbb/formie/templates/_special/email-template', $template->template);
            }

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

        if (Formie::$plugin->getEmailTemplates()->reorderTemplates($ids)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder templates.')]);
    }

    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $templateId = (int)$this->request->getRequiredParam('id');

        if (Formie::$plugin->getEmailTemplates()->deleteTemplateById($templateId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive template.')]);
    }
}
