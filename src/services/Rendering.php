<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyRenderEvent;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\web\assets\frontend\FrontEndAsset;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use craft\helpers\Template as TemplateHelper;
use craft\web\View;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;

use yii\base\Exception;
use yii\base\InvalidConfigException;

class Rendering extends Component
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_RENDER_FORM = 'modifyRenderForm';
    const EVENT_MODIFY_RENDER_PAGE = 'modifyRenderPage';
    const EVENT_MODIFY_RENDER_FIELD = 'modifyRenderField';


    // Public Methods
    // =========================================================================

    /**
     * Renders and returns a form's HTML.
     *
     * @param Form|string $form
     * @param array $options
     * @return Markup|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function renderForm($form, array $options = null)
    {
        if (is_string($form)) {
            $form = Form::find()->handle($form)->one();
        }

        if (!$form) {
            return null;
        }

        /* @var $form Form */

        $view = Craft::$app->getView();

        $templatePath = $this->getComponentTemplatePath($form, 'form');
        $view->setTemplatesPath($templatePath);

        // Get the active submission.
        $submission = $form->getCurrentSubmission();

        $html = $view->renderTemplate('form', [
            'form' => $form,
            'options' => $options,
            'submission' => $submission,
        ], View::TEMPLATE_MODE_SITE);

        /* @var FrontEndAsset $bundle */
        $bundle = $view->registerAssetBundle(FrontEndAsset::class);
        $bundle->form = $form;

        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        // Fire a 'modifyRenderForm' event
        $event = new ModifyRenderEvent([
            'html' => TemplateHelper::raw($html),
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_FORM, $event);

        return $event->html;
    }

    /**
     * Renders and returns a form page's HTML.
     *
     * @param Form $form
     * @param FieldLayoutPage $page
     * @param array|null $options
     * @return Markup
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderPage(Form $form, FieldLayoutPage $page = null, array $options = null)
    {
        $view = Craft::$app->getView();

        if (!$form) {
            return null;
        }

        $templatePath = $this->getComponentTemplatePath($form, 'page');
        $oldTemplatesPath = $view->getTemplatesPath();
        $view->setTemplatesPath($templatePath);

        if (!$page) {
            $page = $form->getCurrentPage();
        }

        $html = $view->renderTemplate('page', [
            'form' => $form,
            'page' => $page,
            'options' => $options,
        ], View::TEMPLATE_MODE_SITE);

        $view->setTemplatesPath($oldTemplatesPath);

        // Fire a 'modifyRenderPage' event
        $event = new ModifyRenderEvent([
            'html' => TemplateHelper::raw($html),
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_PAGE, $event);

        return $event->html;
    }

    /**
     * @param Form $form
     * @param FormFieldInterface $field
     * @param array|null $options
     * @return Markup
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderField(Form $form, $field, array $options = null)
    {
        $view = Craft::$app->getView();

        if (!$form) {
            return null;
        }

        if (is_string($field)) {
            $field = $form->getFieldByHandle($field);

            if (!$field) {
                return null;
            }
        }

        $templatePath = $this->getComponentTemplatePath($form, 'field');

        $oldTemplatePath = $view->getTemplatesPath();
        $view->setTemplatesPath($templatePath);

        // Get the active submission.
        $element = $options['element'] ?? $form->getCurrentSubmission();

        /* @var FormField $field */
        $html = $view->renderTemplate('field', [
            'form' => $form,
            'field' => $field,
            'handle' => $field->handle,
            'options' => $options,
            'element' => $element,
        ], View::TEMPLATE_MODE_SITE);

        $view->setTemplatesPath($oldTemplatePath);

        // Fire a 'modifyRenderField' event
        $event = new ModifyRenderEvent([
            'html' => TemplateHelper::raw($html),
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_FIELD, $event);

        return $event->html;
    }

    /**
     * Returns the template path for a form component.
     *
     * @param Form $form
     * @param string $component can be 'form', 'page' or 'field'.
     * @return string
     * @throws Exception
     * @throws LoaderError
     */
    public function getComponentTemplatePath(Form $form, string $component): string
    {
        $view = Craft::$app->getView();
        $oldTemplatePath = $view->getTemplatesPath();
        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        $templatePath = Craft::getAlias('@verbb/formie/templates/_special/form-template');

        if (($template = $form->getTemplate()) && $template->useCustomTemplates && $template->template) {
            $path = $template->template . DIRECTORY_SEPARATOR . $component;

            if ($view->resolveTemplate($path, View::TEMPLATE_MODE_SITE)) {
                $templatePath = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $template->template;
            }
        }

        $view->setTemplatesPath($oldTemplatePath);

        return $templatePath;
    }
}
