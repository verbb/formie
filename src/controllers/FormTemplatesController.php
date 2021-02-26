<?php
namespace verbb\formie\controllers;

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

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\FileHelper;
use verbb\formie\models\FormTemplate;

use Throwable;

class FormTemplatesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return Response
     */
    public function actionIndex(): Response
    {
        $formTemplates = Formie::$plugin->getFormTemplates()->getAllTemplates();

        return $this->renderTemplate('formie/settings/form-templates', compact('formTemplates'));
    }

    /**
     * @param int|null $id
     * @param FormTemplate|null $template
     * @return Response
     * @throws HttpException
     */
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

    /**
     * @throws MissingComponentException
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $id = $request->getBodyParam('id');
        $template = Formie::$plugin->getFormTemplates()->getTemplateById($id);

        if (!$template) {
            $template = new FormTemplate();
        }

        $template->name = $request->getBodyParam('name');
        $template->handle = $request->getBodyParam('handle');
        $template->template = preg_replace('/\/index(?:\.html|\.twig)?$/', '', $request->getBodyParam('template'));
        $template->useCustomTemplates = $request->getBodyParam('useCustomTemplates');
        $template->copyTemplates = $request->getBodyParam('copyTemplates', false);
        $template->outputCssLayout = $request->getBodyParam('outputCssLayout');
        $template->outputCssTheme = $request->getBodyParam('outputCssTheme');
        $template->outputJsBase = $request->getBodyParam('outputJsBase');
        $template->outputJsTheme = $request->getBodyParam('outputJsTheme');
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

            Craft::$app->getSession()->setNotice(Craft::t('formie', 'Template saved.'));
            $this->redirectToPostedUrl($template);
        } else {
            Craft::$app->getSession()->setError(Craft::t('formie', 'Couldn’t save template.'));
        }

        Craft::$app->getUrlManager()->setRouteParams(compact('template'));
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $ids = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));

        if ($success = Formie::$plugin->getFormTemplates()->reorderTemplates($ids)) {
            return $this->asJson(['success' => $success]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder templates.')]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    public function actionDelete()
    {
        $this->requireAcceptsJson();

        $templateId = Craft::$app->getRequest()->getRequiredParam('id');

        if (Formie::$plugin->getFormTemplates()->deleteTemplateById($templateId)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t archive template.')]);
    }
}
