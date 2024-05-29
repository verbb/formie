<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFormRenderOptionsEvent;
use verbb\formie\events\ModifyRenderEvent;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;

use Craft;
use craft\base\Component;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template as TemplateHelper;
use craft\web\View;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Markup;

use yii\base\Exception;
use yii\base\InvalidConfigException;

use Throwable;
use craft\errors\MissingComponentException;

class Rendering extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_RENDER_FORM = 'modifyRenderForm';
    public const EVENT_MODIFY_RENDER_PAGE = 'modifyRenderPage';
    public const EVENT_MODIFY_RENDER_FIELD = 'modifyRenderField';
    public const EVENT_MODIFY_FORM_RENDER_OPTIONS = 'modifyFormRenderOptions';
    public const RENDER_TYPE_CSS = 'css';
    public const RENDER_TYPE_JS = 'js';


    // Properties
    // =========================================================================

    private bool $_renderedJs = false;
    private array $_cssFiles = [];
    private array $_jsFiles = [];
    private array $_filesBuffers = [];


    // Public Methods
    // =========================================================================

    public function renderForm(Form|string|null $form, array $renderOptions = [], bool $fullRender = true): ?Markup
    {
        // Allow an empty form to fail silently
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        // Give the form a unique ID for each render, to help with multiple renders of the same form
        if ($fullRender) {
            $form->setFormId($form->getFormId(false));
        }

        // Fire a 'modifyFormRenderOptions' event
        $event = new ModifyFormRenderOptionsEvent([
            'form' => $form,
            'renderOptions' => $renderOptions,
        ]);
        $this->trigger(self::EVENT_MODIFY_FORM_RENDER_OPTIONS, $event);
        $renderOptions = $event->renderOptions;

        // Allow the form to handle how to apply render variables
        $form->applyRenderOptions($renderOptions);

        // Get the active submission.
        $submission = $form->getCurrentSubmission();
        $jsVariables = $form->getFrontEndJsVariables();

        $html = $form->renderTemplate('form', [
            'form' => $form,
            'renderOptions' => $renderOptions,
            'submission' => $submission,
            'jsVariables' => $jsVariables,
        ]);

        // Fire a 'modifyRenderForm' event
        $event = new ModifyRenderEvent([
            'html' => TemplateHelper::raw($html),
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_FORM, $event);

        $output = TemplateHelper::raw($event->html);

        // We might want to explicitly disable JS/CSS for just this render call
        $renderCss = $renderOptions['renderCss'] ?? true;
        $renderJs = $renderOptions['renderJs'] ?? true;

        // We might need to output CSS and JS inline, or at the head/footer. `renderFormAssets`
        // will sort this out, but we don't want to do anything if rendering manually
        $outputCssLocation = $form->getFrontEndTemplateLocation('outputCssLocation');
        $outputJsLocation = $form->getFrontEndTemplateLocation('outputJsLocation');

        $outputCss = $form->getFrontEndTemplateOption('outputCssLayout');
        $outputJs = $form->getFrontEndTemplateOption('outputJsBase');

        if ($outputCssLocation !== FormTemplate::MANUAL && $outputCss && $renderCss) {
            $css = $this->renderFormAssets($form, self::RENDER_TYPE_CSS);

            $output = TemplateHelper::raw($output . $css);
        }

        // Some attributes are JS-render related
        $jsAttributes = [];

        if (isset($renderOptions['initJs']) && $renderOptions['initJs'] === false) {
            $jsAttributes['data-manual-init'] = true;
        }

        if (isset($renderOptions['useObserver']) && $renderOptions['useObserver'] === false) {
            $jsAttributes['data-bypass-observer'] = true;
        }

        if ($outputJsLocation !== FormTemplate::MANUAL && $outputJs && $renderJs) {
            $js = $this->renderFormAssets($form, self::RENDER_TYPE_JS, false, $jsAttributes);

            $output = TemplateHelper::raw($output . $js);
        }

        return $output;
    }

    public function renderPage(Form|string|null $form, FieldLayoutPage|null $page = null, array $renderOptions = []): ?Markup
    {
        // Allow an empty form to fail silently
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        if (!$page) {
            $page = $form->getCurrentPage();
        }

        // Get the active submission.
        $submission = $form->getCurrentSubmission();

        $html = $form->renderTemplate('page', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => $renderOptions,
            'submission' => $submission,
        ]);

        // Fire a 'modifyRenderPage' event
        $event = new ModifyRenderEvent([
            'html' => $html,
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_PAGE, $event);

        return TemplateHelper::raw($event->html);
    }

    public function renderField(Form|string|null $form, FieldInterface|string $field, array $renderOptions = []): ?Markup
    {
        // Allow an empty form to fail silently
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        $view = Craft::$app->getView();

        if (is_string($field)) {
            $field = $form->getFieldByHandle($field);

            if (!$field) {
                return null;
            }
        }

        // Allow fields to apply any render options in their own way
        $field->applyRenderOptions($form, $renderOptions);

        // Get the active submission.
        $element = $form->getCurrentSubmission();

        /* @var Field $field */
        $html = $form->renderTemplate('field', [
            'form' => $form,
            'field' => $field,
            'handle' => $field->handle,
            'renderOptions' => $renderOptions,
            'element' => $element,
        ]);

        // Fire a 'modifyRenderField' event
        $event = new ModifyRenderEvent([
            'html' => $html,
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_FIELD, $event);

        return TemplateHelper::raw($event->html);
    }

    public function registerAssets(Form|string|null $form, array $renderOptions = []): void
    {
        // So we can easily re-use code, we just call the `renderForm` function
        // This will register any assets, and should be included outside of cached areas.
        // It should be called like `{% do craft.formie.registerAssets(handle) %}`
        $this->renderForm($form, $renderOptions, false);
    }

    public function renderFormAssets(Form|string|null $form, string $type = null, bool $forceInline = false, array $attributes = []): ?Markup
    {
        // Allow an empty form to fail silently
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        $view = Craft::$app->getView();

        $outputCssLayout = $form->getFrontEndTemplateOption('outputCssLayout');
        $outputCssTheme = $form->getFrontEndTemplateOption('outputCssTheme');
        $outputCssLocation = $form->getFrontEndTemplateLocation('outputCssLocation');
        $outputJsLocation = $form->getFrontEndTemplateLocation('outputJsLocation');

        $assetPath = '@verbb/formie/web/assets/frontend/dist/';
        $jsFile = Craft::$app->getAssetManager()->getPublishedUrl($assetPath, true, 'js/formie.js');
        $cssLayout = Craft::$app->getAssetManager()->getPublishedUrl($assetPath, true, 'css/formie-base.css');
        $cssTheme = Craft::$app->getAssetManager()->getPublishedUrl($assetPath, true, 'css/formie-theme.css');

        $output = [];

        if ($type !== self::RENDER_TYPE_JS) {
            // Only output this if we're not showing the theme. We bundle the two together
            // during build, so we don't have to serve two stylesheets.
            if ($outputCssLayout && !$outputCssTheme) {
                if ($outputCssLocation === FormTemplate::PAGE_HEADER && !$forceInline) {
                    $view->registerCssFile($cssLayout);
                } else {
                    $output[] = Html::cssFile($cssLayout, $attributes);
                }
            }

            if ($outputCssLayout && $outputCssTheme) {
                if ($outputCssLocation === FormTemplate::PAGE_HEADER && !$forceInline) {
                    $view->registerCssFile($cssTheme);
                } else {
                    $output[] = Html::cssFile($cssTheme, $attributes);
                }
            }
        }

        if ($type !== self::RENDER_TYPE_CSS) {
            // Only output this file once. It's applicable to all forms on a page.
            if (!$this->_renderedJs) {
                if ($outputJsLocation === FormTemplate::PAGE_FOOTER && !$forceInline) {
                    $view->registerJsFile($jsFile, array_merge(['defer' => true], $attributes));
                } else {
                    $output[] = Html::jsFile($jsFile, array_merge(['defer' => true], $attributes));
                }

                // Add locale definition JS variables
                $jsString = 'window.FormieTranslations=' . Json::encode($this->getFrontEndJsTranslations()) . ';';

                if ($outputJsLocation === FormTemplate::PAGE_FOOTER && !$forceInline) {
                    $view->registerJs($jsString, View::POS_END);
                } else {
                    $output[] = Html::script($jsString, ['type' => 'text/javascript']);
                }

                $this->_renderedJs = true;
            }
        }

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }

    public function getFrontEndJsTranslations(): array
    {
        return $this->_getTranslatedStrings([
            // Core validators
            '{attribute} cannot be blank.',
            '{attribute} is not a valid email address.',
            '{attribute} is not a valid URL.',
            '{attribute} is not a valid number.',
            '{attribute} is not a valid format.',
            '{name} must match {value}.',

            // Custom validators
            'File {filename} must be smaller than {filesize} MB.',
            'File must be smaller than {filesize} MB.',
            'File must be larger than {filesize} MB.',
            'Choose up to {files} files.',
            '{startTag}{num}{endTag} character left',
            '{startTag}{num}{endTag} characters left',
            '{startTag}{num}{endTag} word left',
            '{startTag}{num}{endTag} words left',

            // General
            'Unable to parse response `{e}`.',
            'Are you sure you want to leave?',
            'The request timed out.',
            'The request encountered a network error. Please try again.',

            // Phone field
            'Invalid number',
            'Invalid country code',
            'Too short',
            'Too long',

            // PayPal
            'Missing Authorization ID for approval.',
            'Payment authorized. Finalize the form to complete payment.',
            'Unable to authorize payment. Please try again.',

            // Opayo
            'The request timed out.',
            'The request encountered a network error. Please try again.',

            // Stripe
            'Invalid amount.',
            'Invalid currency.',
            'Provide a value for “{label}” to proceed.',
        ]);
    }

    public function getFormComponentTemplatePath(Form $form, string $component): string
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

    public function getEmailComponentTemplatePath(?Notification $notification, string $component): string
    {
        $view = Craft::$app->getView();
        $oldTemplatePath = $view->getTemplatesPath();
        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        $templatePath = Craft::getAlias('@verbb/formie/templates/_special/email-template');

        if ($notification && ($template = $notification->getTemplate()) && $template->template) {
            $path = $template->template . DIRECTORY_SEPARATOR . $component;

            if ($view->resolveTemplate($path, View::TEMPLATE_MODE_SITE)) {
                $templatePath = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $template->template;
            }
        }

        $view->setTemplatesPath($oldTemplatePath);

        return $templatePath;
    }

    public function populateFormValues($element, $values = [], $force = false): void
    {
        $submission = null;
        $form = null;

        // We allow a submission or a form to be passed in here. Handle and get both.
        if ($element instanceof Form || is_string($element)) {
            $form = $element;

            if (is_string($form)) {
                $form = Form::find()->handle($form)->one();
            }

            if (!$form) {
                return;
            }

            // Fetch the existing submission, if there is one, in case we're force-applying
            $submission = $form->getCurrentSubmission();
        }

        if ($element instanceof Submission) {
            $submission = $element;
            $form = $submission->getForm();

            if (!$form) {
                return;
            }
        }

        $disabledValues = [];

        // Try to populate fields with their default value
        foreach ($values as $key => $value) {
            try {
                $field = $form->getFieldByHandle($key);

                // Prevent users using long-hand Twig `{{` to prevent injection execution. Only an issue for some fields like Hidden fields.
                if (is_string($value)) {
                    $value = str_replace(['{{', '}}', '{%', '%}'], ['{', '}', '', ''], $value);
                }

                if ($field) {
                    // Store any visibly disabled fields against the form to apply later
                    if ($field->visibility === 'disabled') {
                        $disabledValues[$key] = $value;
                    }
                    
                    // Ensure that the field has a chance to populate the default value correctly
                    $field->populateValue($value, $submission);

                    // If forcing, set the value every time this is called
                    if ($force && $submission) {
                        // The value will be normalised already as the `defaultValue`
                        $submission->setFieldValue($field->handle, $field->defaultValue);
                    }
                }
            } catch (Throwable $e) {
                Formie::error('Error populating form values for “{key}”. Template error: “{message}” {file}:{line}', [
                    'key' => $key,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                continue;
            }
        }

        if ($disabledValues) {
            // Apply any disabled field values via session cache, to keep out of requests
            $form->setPopulatedFieldValues($disabledValues);
        }
    }

    public function startFileBuffer($type, $view): void
    {
        // Save any currently queued tags into a new buffer, and reset the active queue
        $this->_filesBuffers[$type][] = $view->$type;
        $view->$type = [];
    }

    public function clearFileBuffer($type, $view): bool|array
    {
        if (empty($this->_filesBuffers[$type])) {
            return false;
        }

        $bufferedFiles = $view->$type;
        $view->$type = array_pop($this->_filesBuffers[$type]);
        return $bufferedFiles;
    }

    public function renderFormCssJs(Form|string|null $form, array $renderOptions = []): void
    {
        // Don't re-render the form multiple times if it's already rendered
        if ($this->_jsFiles || $this->_cssFiles) {
            return;
        }

        $view = Craft::$app->getView();

        // Create our own buffer for CSS files. `View::startCssBuffer()` only handles CSS code, not files
        $this->startFileBuffer('cssFiles', $view);
        $view->startCssBuffer();

        $this->startFileBuffer('jsFiles', $view);
        $view->startJsBuffer();

        // Render the form, and capture any CSS being output to the asset manager. Grab that and output it directly.
        // This helps when targeting head/body/inline and ensure we output it **here**
        $this->renderForm($form, $renderOptions, false);

        $this->_cssFiles = $this->clearFileBuffer('cssFiles', $view);
        $this->_cssFiles = array_merge($this->_cssFiles, [$view->clearCssBuffer()]);

        $this->_jsFiles = $this->clearFileBuffer('jsFiles', $view);
        $this->_jsFiles = array_merge($this->_jsFiles, [$view->clearJsBuffer()]);

        $this->_cssFiles = array_filter($this->_cssFiles);
        $this->_jsFiles = array_filter($this->_jsFiles);
    }

    public function renderFormCss(Form|string|null $form, array $renderOptions = []): Markup
    {
        $this->renderFormCssJs($form, $renderOptions);

        return TemplateHelper::raw(implode("\n", $this->_cssFiles));
    }

    public function renderFormJs(Form|string|null $form, array $renderOptions = []): Markup
    {
        $this->renderFormCssJs($form, $renderOptions);

        $allJsFiles = [];

        foreach ($this->_jsFiles as $jsFile) {
            if (is_array($jsFile)) {
                $allJsFiles = array_merge($allJsFiles, $jsFile);
            } else {
                $allJsFiles[] = $jsFile;
            }
        }

        return TemplateHelper::raw(implode("\n", $allJsFiles));
    }

    public function renderCss(bool $inline = false, array $renderOptions = []): ?Markup
    {
        $view = Craft::$app->getView();
        $assetPath = '@verbb/formie/web/assets/frontend/dist/';
        $cssLayout = Craft::$app->getAssetManager()->getPublishedUrl($assetPath, true, 'css/formie-base.css');
        $cssTheme = Craft::$app->getAssetManager()->getPublishedUrl($assetPath, true, 'css/formie-theme.css');

        $output = [];

        if ($inline) {
            $output[] = Html::cssFile($cssLayout);
            $output[] = Html::cssFile($cssTheme);
        } else {
            $view->registerCssFile($cssLayout);
            $view->registerCssFile($cssTheme);
        }

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }

    public function renderJs(bool $inline = false, array $renderOptions = []): ?Markup
    {
        $view = Craft::$app->getView();
        $assetPath = '@verbb/formie/web/assets/frontend/dist/';
        $jsFile = Craft::$app->getAssetManager()->getPublishedUrl($assetPath, true, 'js/formie.js');

        $output = [];

        // Add locale definition JS variables
        $jsString = 'window.FormieTranslations=' . Json::encode($this->getFrontEndJsTranslations()) . ';';

        // Some attributes are JS-render related
        $jsAttributes = $renderOptions;

        if (isset($renderOptions['initJs']) && $renderOptions['initJs'] === false) {
            $jsAttributes['data-manual-init'] = true;
        }

        if (isset($renderOptions['useObserver']) && $renderOptions['useObserver'] === false) {
            $jsAttributes['data-bypass-observer'] = false;
        }

        if ($inline) {
            $output[] = Html::jsFile($jsFile, array_merge(['defer' => true], $jsAttributes));
            $output[] = Html::script($jsString, ['type' => 'text/javascript']);
        } else {
            $view->registerJsFile($jsFile, array_merge(['defer' => true], $jsAttributes));
            $view->registerJs($jsString, View::POS_END);
        }

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }


    // Private Methods
    // =========================================================================

    private function _getTranslatedStrings(array $array): array
    {
        $strings = [];

        foreach ($array as $item) {
            $strings[$item] = Craft::t('formie', $item);
        }

        return $strings;
    }

    private function _getFormFromTemplate(Form|string|null $form): ?Form
    {
        if ($form instanceof Form) {
            return $form;
        }
        
        if ($form && is_string($form)) {
            if ($form = Form::find()->handle($form)->one()) {
                return $form;
            }
        }

        return null;
    }
}
