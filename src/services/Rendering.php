<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
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

class Rendering extends Component
{
    // Constants
    // =========================================================================

    const EVENT_MODIFY_RENDER_FORM = 'modifyRenderForm';
    const EVENT_MODIFY_RENDER_PAGE = 'modifyRenderPage';
    const EVENT_MODIFY_RENDER_FIELD = 'modifyRenderField';
    const EVENT_MODIFY_FORM_RENDER_OPTIONS = 'modifyFormRenderOptions';
    const RENDER_TYPE_CSS = 'css';
    const RENDER_TYPE_JS = 'js';


    // Properties
    // =========================================================================

    private $_renderedJs = false;
    private $_cssFiles = [];
    private $_jsFiles = [];
    private $_filesBuffers = [];


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

        // Fire a 'modifyFormRenderOptions' event
        $event = new ModifyFormRenderOptionsEvent([
            'form' => $form,
            'options' => $options,
        ]);
        $this->trigger(self::EVENT_MODIFY_FORM_RENDER_OPTIONS, $event);
        $options = $event->options;

        // Get the active submission.
        $submission = $form->getCurrentSubmission();
        $jsVariables = $form->getFrontEndJsVariables();

        $html = $form->renderTemplate('form', [
            'form' => $form,
            'options' => $options,
            'submission' => $submission,
            'jsVariables' => $jsVariables,
        ]);

        // Fire a 'modifyRenderForm' event
        $event = new ModifyRenderEvent([
            'html' => TemplateHelper::raw($html),
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_FORM, $event);

        $output = $event->html;

        // We might want to explicitly disable JS/CSS for just this render call
        $renderCss = $options['renderCss'] ?? true;
        $renderJs = $options['renderJs'] ?? true;

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

        if ($outputJsLocation !== FormTemplate::MANUAL && $outputJs && $renderJs) {
            $js = $this->renderFormAssets($form, self::RENDER_TYPE_JS);

            $output = TemplateHelper::raw($output . $js);
        }

        return $output;
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
        if (!$form) {
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
            'options' => $options,
            'submission' => $submission,
        ]);

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
        if (!$form) {
            return null;
        }

        if (is_string($field)) {
            $field = $form->getFieldByHandle($field);

            if (!$field) {
                return null;
            }
        }

        // Allow fields to apply any render options in their own way
        if ($options) {
            $field->applyRenderOptions($options);
        }

        // Get the active submission.
        $element = $options['element'] ?? $form->getCurrentSubmission();

        /* @var FormField $field */
        $html = $form->renderTemplate('field', [
            'form' => $form,
            'field' => $field,
            'handle' => $field->handle,
            'options' => $options,
            'element' => $element,
        ]);

        // Fire a 'modifyRenderField' event
        $event = new ModifyRenderEvent([
            'html' => TemplateHelper::raw($html),
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_FIELD, $event);

        return $event->html;
    }

    /**
     * Registers a form's assets (CSS/JS), to be output to the page, as per the form template
     * settings. This should be used for cached forms, as their assets will not be output
     * when inside a cache tag. See `renderFormJs` and `renderFormCss` for more controlled output.
     *
     * @param Form|string $form
     * @param array $options
     * @return null
     */
    public function registerAssets($form, array $options = null)
    {
        // So we can easily re-use code, we just call the `renderForm` function
        // This will register any assets, and should be included outside of cached areas.
        // It should be called like `{% do craft.formie.registerAssets(handle) %}`
        $this->renderForm($form, $options);

        return null;
    }

    /**
     * Render the assets for a given form. Depending on the form template settings, this will
     * either add the JS/CSS to the head/footer of the page, or directly return the CSS/JS strings
     * for use when manually calling this function through render variable tags.
     *
     * @param Form|string $form
     * @param string $type
     * @param bool $forceInline
     * @param array $options
     * @return null
     */
    public function renderFormAssets($form, $type = null, $forceInline = false, $attributes = [])
    {
        if (is_string($form)) {
            $form = Form::find()->handle($form)->one();
        }

        if (!$form) {
            return null;
        }

        $view = Craft::$app->getView();

        $outputCssLayout = $form->getFrontEndTemplateOption('outputCssLayout');
        $outputCssTheme = $form->getFrontEndTemplateOption('outputCssTheme');
        $outputCssLocation = $form->getFrontEndTemplateLocation('outputCssLocation');
        $outputJsLocation = $form->getFrontEndTemplateLocation('outputJsLocation');

        $assetPath = '@verbb/formie/web/assets/frontend/dist/';
        $jsFile = Craft::$app->getAssetManager()->getPublishedUrl($assetPath . 'js/formie.js', true);
        $cssLayout = Craft::$app->getAssetManager()->getPublishedUrl($assetPath . 'css/formie-base.css', true);
        $cssTheme = Craft::$app->getAssetManager()->getPublishedUrl($assetPath . 'css/formie-theme.css', true);

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

    public function getFrontEndJsTranslations()
    {
        return $this->_getTranslatedStrings([
            'File {filename} must be smaller than {filesize} MB.',
            'File must be smaller than {filesize} MB.',
            'File must be larger than {filesize} MB.',
            'Choose up to {files} files.',
            '{num} characters left',
            '{num} words left',

            // Field validation messages
            'This field is required.',
            'Please select a value.',
            'Please select a value.',
            'Please select at least one value.',
            'Please fill out this field.',
            'Please enter a valid email address.',
            'Please enter a URL.',
            'Please enter a number',
            'Please match the following format: #rrggbb',
            'Please use the YYYY-MM-DD format',
            'Please use the 24-hour time format. Ex. 23:00',
            'Please use the YYYY-MM format',
            'Please match the requested format.',
            'Please select a value that is no more than {max}.',
            'Please select a value that is no less than {min}.',
            'Please shorten this text to no more than {maxLength} characters. You are currently using {length} characters.',
            'Please lengthen this text to {minLength} characters or more. You are currently using {length} characters.',
            'There was an error with this field.',

            'Unable to parse response `{e}`.',
            'Are you sure you want to leave?',
            'The request timed out.',
            'The request encountered a network error. Please try again.',

            // Phone field
            'Invalid number',
            'Invalid country code',
            'Too short',
            'Too long',
        ]);
    }

    public function populateFormValues($element, $values = [], $force = false)
    {
        // We allow a submission or a form to be passed in here. Handle and get both.
        if ($element instanceof Form || is_string($element)) {
            $form = $element;
            
            if (is_string($form)) {
                $form = Form::find()->handle($form)->one();
            }

            if (!$form) {
                return null;
            }
            
            // Fetch the existing submission, if there is one, in case we're force-applying
            $submission = $form->getCurrentSubmission();
        } 

        if ($element instanceof Submission) {
            $submission = $element;
            $form = $submission->getForm();

            if (!$form) {
                return null;
            }
        }
        
        $disabledValues = [];

        // Try to populate fields with their default value
        foreach ($values as $key => $value) {
            try {
                $field = $form->getFieldByHandle($key);

                // Store any visibly disabled fields against the form to apply later
                if ($field->visibility === 'disabled') {
                    $disabledValues[$key] = $value;
                }

                $field->populateValue($value);

                // If forcing, set the value every time this is called
                if ($force && $submission) {
                    // The value will be normalised already as the `defaulValue`
                    $submission->setFieldValue($field->handle, $field->defaultValue);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if ($disabledValues) {
            // Apply any disabled field values via session cache, to keep out of requests
            $form->setPopulatedFieldValues($disabledValues);
        }
    }

    /**
     * Starts a buffer for any files registered with `View::registerJsFile()` or `View::registerCssFile()`.
     *
     * @return void
     */
    public function startFileBuffer($type, $view): void
    {
        // Save any currently queued tags into a new buffer, and reset the active queue
        $this->_filesBuffers[$type][] = $view->$type;
        $view->$type = [];
    }

    /**
     * Clears and ends a file buffer, returning whatever files were registered while the buffer was active.
     *
     * @return array|false The files that were registered in the active buffer, grouped by position, or `false` if there isn’t one
     */
    public function clearFileBuffer($type, $view)
    {
        if (empty($this->_filesBuffers[$type])) {
            return false;
        }

        $bufferedFiles = $view->$type;
        $view->$type = array_pop($this->_filesBuffers[$type]);
        return $bufferedFiles;
    }

    /**
     * Returns the JS/CSS for the rendering of a form. This will include buffering any JS/CSS files
     * This is also done in a single function to capture both CSS/JS files which are only registered once per request
     *
     * @param Form|string $form
     * @param array|null $options
     * @return string
     */
    public function renderFormCssJs($form, array $options = null): void
    {
        // Don't re-render the form multiple times if it's already rendered
        if ($this->_jsFiles || $this->_cssFiles) {
            return;
        }

        $view = Craft::$app->getView();

        // Create our own buffer for CSS files. `View::startCssBuffer()` only handles CSS code, not files
        $this->startFileBuffer('cssFiles', $view);
        $css = $view->startCssBuffer();

        $this->startFileBuffer('jsFiles', $view);
        $js = $view->startJsBuffer();

        // Render the form, and capture any CSS being output to the asset manager. Grab that and output it directly.
        // This helps when targeting head/body/inline and ensure we output it **here**
        $this->renderForm($form, $options);

        $this->_cssFiles = $this->clearFileBuffer('cssFiles', $view);
        $this->_cssFiles = array_merge($this->_cssFiles, [$view->clearCssBuffer()]);

        $this->_jsFiles = $this->clearFileBuffer('jsFiles', $view);
        $this->_jsFiles = array_merge($this->_jsFiles, [$view->clearJsBuffer()]);

        $this->_cssFiles = array_filter($this->_cssFiles);
        $this->_jsFiles = array_filter($this->_jsFiles);
    }

    /**
     * Returns the CSS for the rendering of a form. This will include buffering any CSS files
     *
     * @param Form|string $form
     * @param array|null $options
     * @return string
     */
    public function renderFormCss($form, array $options = null)
    {
        $this->renderFormCssJs($form, $options);

        return TemplateHelper::raw(implode("\n", $this->_cssFiles));
    }

    /**
     * Returns the JS for the rendering of a form. This will include buffering any JS files
     *
     * @param Form|string $form
     * @param array|null $options
     * @return string
     */
    public function renderFormJs($form, array $options = null)
    {
        $this->renderFormCssJs($form, $options);

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


    // Private Methods
    // =========================================================================

    private function _getTranslatedStrings($array)
    {
        $strings = [];

        foreach ($array as $item) {
            $strings[$item] = Craft::t('formie', $item);
        }

        return $strings;
    }
}
