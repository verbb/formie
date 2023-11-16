<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\FileHelper;
use verbb\formie\models\FormTemplate;

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

class FormTemplatesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        $formTemplates = Formie::$plugin->getFormTemplates()->getAllTemplates();

        return $this->renderTemplate('formie/settings/form-templates', compact('formTemplates'));
    }

    public function actionEdit(int $id = null, FormTemplate $template = null): Response
    {
        $variables = compact('id', 'template');

        if (!$variables['template']) {
            if ($variables['id']) {
                $variables['template'] = Formie::$plugin->getFormTemplates()->getTemplateById($variables['id']);

                if (!$variables['template']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['template'] = new FormTemplate();
            }
        }

        if ($variables['template']->id) {
            $variables['title'] = $variables['template']->name;
        } else {
            $variables['title'] = Craft::t('formie', 'Create a new template');
        }

        return $this->renderTemplate('formie/settings/form-templates/_edit', $variables);
    }

    public function actionSave(): void
    {
        $this->requirePostRequest();

        $request = $this->request;

        $template = new FormTemplate();
        $template->id = $request->getBodyParam('id');
        $template->name = $request->getBodyParam('name');
        $template->handle = $request->getBodyParam('handle');
        $template->template = preg_replace('/\/index(?:\.html|\.twig)?$/', '', $request->getBodyParam('template'));
        $template->useCustomTemplates = (bool)$request->getBodyParam('useCustomTemplates');
        $template->copyTemplates = (bool)$request->getBodyParam('copyTemplates', false);
        $template->outputCssLayout = (bool)$request->getBodyParam('outputCssLayout');
        $template->outputCssTheme = (bool)$request->getBodyParam('outputCssTheme');
        $template->outputJsBase = (bool)$request->getBodyParam('outputJsBase');
        $template->outputJsTheme = (bool)$request->getBodyParam('outputJsTheme');
        $template->outputCssLocation = $request->getBodyParam('outputCssLocation');
        $template->outputJsLocation = $request->getBodyParam('outputJsLocation');

        // Set the form field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Form::class;
        $template->setFieldLayout($fieldLayout);

        // Save it
        if (Formie::$plugin->getFormTemplates()->saveTemplate($template)) {
            if ($template->copyTemplates) {
                FileHelper::copyTemplateDirectory('@verbb/formie/templates/_special/form-template', $template->template);
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

        if (Formie::$plugin->getFormTemplates()->reorderTemplates($ids)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder templates.')]);
    }

    public function actionDelete(): Response
    {
        $this->requireAcceptsJson();

        $templateId = $this->request->getRequiredParam('id');

        if (Formie::$plugin->getFormTemplates()->deleteTemplateById($templateId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive template.')]);
    }
}
