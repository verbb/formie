<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\compatibility\messages\ValidationMessageCompatibility;
use verbb\formie\deprecations\RenderingDeprecations;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyBrowserJsTranslationsEvent;
use verbb\formie\events\ModifyFormRenderOptionsEvent;
use verbb\formie\events\ModifyRenderEvent;
use verbb\formie\helpers\UploadAccess;
use verbb\formie\helpers\ValidationMessagesHelper;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FormTemplate;
use verbb\formie\models\Notification;
use verbb\formie\models\RenderFrame;
use verbb\formie\models\ResolvedTheme;
use verbb\formie\models\SubmissionConfig;
use verbb\formie\web\FieldRenderCallContext;

use Craft;
use craft\base\Component;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template as TemplateHelper;
use craft\web\View;

use Twig\Markup;

class Rendering extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_RENDER_FORM = 'modifyRenderForm';
    public const EVENT_MODIFY_RENDER_PAGE = 'modifyRenderPage';
    public const EVENT_MODIFY_RENDER_FIELD = 'modifyRenderField';
    public const EVENT_MODIFY_FORM_RENDER_OPTIONS = 'modifyFormRenderOptions';
    public const EVENT_MODIFY_BROWSER_JS_TRANSLATIONS = 'modifyBrowserJsTranslations';
    public const RENDER_TYPE_CSS = 'css';
    public const RENDER_TYPE_JS = 'js';


    // Traits
    // =========================================================================

    use RenderingDeprecations;


    // Properties
    // =========================================================================

    private bool $_renderedJs = false;
    private array $_filesBuffers = [];
    private array $_renderVariables = [];
    // Stack of active render calls so nested page/field/template code can read the
    // current form and resolved render options without threading them everywhere.
    private array $_renderFrames = [];


    // Public Methods
    // =========================================================================

    public function renderForm(Form|string|null $form, array $renderOptions = [], bool $fullRender = true): ?Markup
    {
        // Allow an empty form to fail silently
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        // Give the form a unique render ID for each render, to help with multiple
        // renders of the same state identity on one page.
        if ($fullRender) {
            $form->setRenderId($form->getRenderId(false));
        }

        // Fire a 'modifyFormRenderOptions' event
        $event = new ModifyFormRenderOptionsEvent([
            'form' => $form,
            'renderOptions' => $renderOptions,
        ]);
        $this->trigger(self::EVENT_MODIFY_FORM_RENDER_OPTIONS, $event);
        $renderOptions = $this->_normalizeRenderOptions($event->renderOptions);

        $this->_prepareFormForRender($form, $renderOptions);
        // Push the outer render context so nested field/template helpers can read it.
        $this->pushRenderFrame($form, $renderOptions);

        try {
            // Get the active submission and hydrate page continuity before template access.
            $submission = $this->_hydrateSubmitFlowProgress($form);

            $html = $form->renderTemplate('form', [
                'form' => $form,
                'renderOptions' => $renderOptions,
                'submission' => $submission,
                'customInputs' => $renderOptions['customInputs'] ?? [],
                'csrfInputOptions' => $this->_resolveCsrfInputOptions($renderOptions),
                'uploadCreateToken' => UploadAccess::issueCreateToken($form),
            ]);

            // Fire a 'modifyRenderForm' event
            $event = new ModifyRenderEvent([
                'html' => TemplateHelper::raw($html),
            ]);
            $this->trigger(self::EVENT_MODIFY_RENDER_FORM, $event);

            $output = TemplateHelper::raw($event->html);

            // We might need to output CSS and JS inline, or at the head/footer. `formAssets()`
            // will sort this out, but we don't want to do anything if rendering manually.
            $assetSettings = $this->_resolveFormAssetSettings($form, $renderOptions);

            if ($assetSettings['outputCssLocation'] !== FormTemplate::MANUAL) {
                $css = $this->_renderResolvedFormAssets($form, self::RENDER_TYPE_CSS, false, $renderOptions);

                $output = TemplateHelper::raw($output . $css);
            }

            if ($assetSettings['outputJsLocation'] !== FormTemplate::MANUAL) {
                $js = $this->_renderResolvedFormAssets($form, self::RENDER_TYPE_JS, false, $renderOptions);

                $output = TemplateHelper::raw($output . $js);
            }

            return $output;
        } finally {
            // Drop the outer render context once this render call is complete.
            $this->popRenderFrame();
        }
    }

    public function renderPage(Form|string|null $form, FieldLayoutPage|null $page = null, array $renderOptions = []): ?Markup
    {
        // Allow an empty form to fail silently
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        // Reuse the parent form render context when page rendering happens inside `renderForm()`.
        $active = $this->getActiveRenderFrame();
        $pushedHere = false;

        if (!$active || (string)$active->getForm()->id !== (string)$form->id) {
            $this->_prepareFormForRender($form, $renderOptions);
            // Standalone page renders still need a temporary context for nested helpers.
            $this->pushRenderFrame($form, $renderOptions);
            $pushedHere = true;
        } else {
            // Nested page renders inherit the already-normalized parent render options.
            $this->_prepareFormForRender($form, $active->getRenderOptions());
        }

        try {
            $submission = $this->_hydrateSubmitFlowProgress($form);

            if (!$page) {
                $page = $form->getCurrentPage();
            }

            $html = $form->renderTemplate('page', [
                'form' => $form,
                'page' => $page,
                'submission' => $submission,
            ]);

            // Fire a 'modifyRenderPage' event
            $event = new ModifyRenderEvent([
                'html' => $html,
            ]);
            $this->trigger(self::EVENT_MODIFY_RENDER_PAGE, $event);

            return TemplateHelper::raw($event->html);
        } finally {
            if ($pushedHere) {
                $this->popRenderFrame();
            }
        }
    }

    /**
     * Render a single field. Nested fields should come from contextual `getRows()` / `getFields()` traversal, which already
     * applies parent and repeater-row scope on cloned field instances.
     *
     * Do not pass `value` unless you need an override: the field template falls back to
     * `field.getElementValue(submission)`, which uses `valueKey()` (full dotted path) and supports arbitrary depth.
     *
     * @param array<string, mixed> $fieldOptions Optional: `value`, `fieldLabelPrefix`, `fieldLabelSuffix`, `fieldNamespace`, `inputName`
     */
    public function renderField(Form|string|null $form, FieldInterface|string $field, array $fieldOptions = []): ?Markup
    {
        // Allow an empty form to fail silently
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        if (is_string($field)) {
            $field = $form->getFieldByHandle($field);

            if (!$field) {
                return null;
            }
        }

        if ($field->getIsBuilderField()) {
            return null;
        }

        // Reuse the parent form render context when a single field is rendered from inside a full form render.
        $active = $this->getActiveRenderFrame();
        $pushedHere = false;

        if (!$active || (string)$active->getForm()->id !== (string)$form->id) {
            $this->_prepareFormForRender($form, $fieldOptions);
            // Standalone field renders need their own context so field internals can inspect render options.
            $this->pushRenderFrame($form, $fieldOptions);
            $pushedHere = true;
            $baseOptions = $fieldOptions;
        } else {
            // Nested field renders inherit the parent form's render options by default.
            $baseOptions = $active->getRenderOptions();
        }

        $prepareOptions = $baseOptions;

        if (array_key_exists('fieldNamespace', $fieldOptions)) {
            $prepareOptions['fieldNamespace'] = $fieldOptions['fieldNamespace'];
        }

        $this->_prepareFormForRender($form, $prepareOptions);

        $callContext = array_intersect_key($fieldOptions, array_flip(['inputName']));
        FieldRenderCallContext::push($callContext);

        $originalNamespace = $field->getNamespace();

        if (array_key_exists('fieldNamespace', $fieldOptions)) {
            $field->setNamespace($fieldOptions['fieldNamespace']);
        }

        // Get the active submission and hydrate page continuity for field context.
        $element = $this->_hydrateSubmitFlowProgress($form);
        $value = $fieldOptions['value'] ?? null;

        $configValue = $value;

        if ($configValue === null && $element) {
            $configValue = $field->getElementValue($element);
        }

        /* @var Field $field */
        $config = $field->getInputTemplateVariables($form, $configValue);
        $fieldLabelPrefix = array_key_exists('fieldLabelPrefix', $fieldOptions)
            ? $fieldOptions['fieldLabelPrefix']
            : $config['fieldLabelPrefix'];
        $fieldLabelSuffix = array_key_exists('fieldLabelSuffix', $fieldOptions)
            ? $fieldOptions['fieldLabelSuffix']
            : $config['fieldLabelSuffix'];

        try {
            $html = $form->renderTemplate('field', [
                'form' => $form,
                'field' => $field,
                'handle' => $field->handle,
                'value' => $value,
                'element' => $element,
                'fieldLabelPrefix' => $fieldLabelPrefix,
                'fieldLabelSuffix' => $fieldLabelSuffix,
            ]);
        } finally {
            $field->setNamespace($originalNamespace);
            FieldRenderCallContext::pop();

            if ($pushedHere) {
                $this->popRenderFrame();
            }
        }

        // Fire a 'modifyRenderField' event
        $event = new ModifyRenderEvent([
            'html' => $html,
        ]);
        $this->trigger(self::EVENT_MODIFY_RENDER_FIELD, $event);

        return TemplateHelper::raw($event->html);
    }

    public function pushRenderFrame(Form $form, array $renderOptions, ?ResolvedTheme $resolvedTheme = null): void
    {
        $resolvedTheme ??= $renderOptions['_resolvedTheme'] ?? null;

        if (!$resolvedTheme instanceof ResolvedTheme) {
            $resolvedTheme = Formie::$plugin->getThemeConfigService()->resolve(
                $form,
                $renderOptions,
                (bool)($renderOptions['_transportedThemeConfig'] ?? false),
            );
        }

        $this->_renderFrames[] = new RenderFrame($form, $renderOptions, $resolvedTheme);
    }

    public function popRenderFrame(): void
    {
        array_pop($this->_renderFrames);
    }

    public function getActiveRenderFrame(): ?RenderFrame
    {
        if (!$this->_renderFrames) {
            return null;
        }

        // Consumers read the top-most frame to get the current form render context.
        return $this->_renderFrames[count($this->_renderFrames) - 1];
    }

    public function formAssets(Form|string|null $form, array $renderOptions = []): ?Markup
    {
        if (!($form = $this->_getFormFromTemplate($form))) {
            return null;
        }

        $renderOptions = $this->_normalizeRenderOptions($renderOptions);
        $this->pushRenderFrame($form, $renderOptions);
        $output = [];

        try {
            if ($renderOptions['includeCss'] ?? true) {
                $output[] = $this->_renderResolvedFormAssets($form, self::RENDER_TYPE_CSS, true, $renderOptions);
            }

            if ($renderOptions['includeJs'] ?? true) {
                $output[] = $this->_renderResolvedFormAssets($form, self::RENDER_TYPE_JS, true, $renderOptions);
            }
        } finally {
            $this->popRenderFrame();
        }

        $output = array_filter($output, static fn($value) => $value !== null && $value !== '');

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }

    public function getBrowserJsTranslations(): array
    {
        $strings = ValidationMessagesHelper::browserTranslationStringList();

        // Allow plugins to modify JS translation strings
        $event = new ModifyBrowserJsTranslationsEvent([
            'strings' => $strings,
        ]);
        $this->trigger(self::EVENT_MODIFY_BROWSER_JS_TRANSLATIONS, $event);

        return ValidationMessageCompatibility::applyTranslationAliases(
            ValidationMessagesHelper::applyPluginDefaultsToBrowserTranslations(
                $this->_getTranslatedStrings($event->strings),
            ),
        );
    }

    public function getFormComponentTemplatePath(Form $form, string $component): string
    {
        $view = Craft::$app->getView();
        $oldTemplatePath = $view->getTemplatesPath();
        $view->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

        $templatePath = Craft::getAlias('@verbb/formie/templates/_special/form-template');

        $useStockTemplates = (bool)($this->getActiveRenderFrame()?->getRenderOptions()['useStockTemplates'] ?? false);

        if (!$useStockTemplates && ($template = $form->getTemplate()) && $template->useCustomTemplates && $template->template) {
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
                $form = Formie::$plugin->getForms()->getFormByHandle($form);
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

        $runtime = new RuntimeConfiguration();
        $mapped = [];

        foreach ($values as $path => $value) {
            $field = $runtime->findField($form, (string)$path);
            $mapped[$field->uid] = $runtime->populationValue($field, $value, $submission);
        }
        $form->replaceInstanceConfig($form->getInstanceConfig()->with($force ? 'forced' : 'initial', $mapped));

        if ($submission) {
            $submission->snapshot = SubmissionConfig::capture($form->getInstanceConfig());
            $submission->setForm($form);
            $runtime->applyValues($submission);
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

    public function browserAssets(array $renderOptions = []): ?Markup
    {
        $renderOptions = $this->_normalizeRenderOptions($renderOptions);
        $inline = (bool)($renderOptions['inline'] ?? false);
        $output = [];

        if ($renderOptions['includeCss'] ?? true) {
            $output[] = $this->_renderBrowserCss($inline, $renderOptions);
        }

        if ($renderOptions['includeJs'] ?? true) {
            $output[] = $this->_renderBrowserJs($inline, $renderOptions);
        }

        $output = array_filter($output, static fn($value) => $value !== null && $value !== '');

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }

    public function setRenderVariables(array $variables = []): void
    {
        $this->_renderVariables = $variables;
    }

    public function getRenderVariables(string $key): mixed
    {
        return ArrayHelper::getValue($this->_renderVariables, $key);
    }

    public function registerScript(string $script, int $position = View::POS_END, array $options = [], ?string $key = null): void
    {
        // Merge in any render options for JS that have been defined
        $options = array_unique(array_merge($options, $this->_getScriptAttributes()));

        Craft::$app->getView()->registerScript($script, $position, $options, $key);
    }


    // Private Methods
    // =========================================================================

    private function _normalizeRenderOptions(array $renderOptions = []): array
    {
        if (!array_key_exists('includeCss', $renderOptions) && array_key_exists('renderCss', $renderOptions)) {
            $renderOptions['includeCss'] = $renderOptions['renderCss'];
        }

        if (!array_key_exists('includeJs', $renderOptions) && array_key_exists('renderJs', $renderOptions)) {
            $renderOptions['includeJs'] = $renderOptions['renderJs'];
        }

        if (!array_key_exists('outputCss', $renderOptions) && (array_key_exists('outputCssLayout', $renderOptions) || array_key_exists('outputCssTheme', $renderOptions))) {
            $renderOptions['outputCss'] = (bool)($renderOptions['outputCssLayout'] ?? false) || (bool)($renderOptions['outputCssTheme'] ?? false);
        }

        if (!array_key_exists('outputJs', $renderOptions) && (array_key_exists('outputJsBase', $renderOptions) || array_key_exists('outputJsTheme', $renderOptions))) {
            $renderOptions['outputJs'] = (bool)($renderOptions['outputJsBase'] ?? false) || (bool)($renderOptions['outputJsTheme'] ?? false);
        }

        return $renderOptions;
    }

    /**
     * Resolve Craft `csrfInput()` options for the hidden-inputs template.
     *
     * @return array<string, mixed>|null `null` omits the CSRF input entirely.
     */
    private function _resolveCsrfInputOptions(array $renderOptions): ?array
    {
        // Stamp a stable marker so front-end JS can find the CSRF input when
        // sites customise Craft's csrfTokenName away from CRAFT_CSRF_TOKEN.
        // Force sync CSRF by default: Craft's async <craft-csrf-input> omits
        // attribute options, so data-formie-csrf would never land on the field.
        $defaults = [
            'autocomplete' => 'off',
            'data-formie-csrf' => true,
            'async' => false,
        ];

        if (!array_key_exists('csrfInput', $renderOptions)) {
            return $defaults;
        }

        $csrfInput = $renderOptions['csrfInput'];

        if ($csrfInput === false) {
            return null;
        }

        if ($csrfInput === true) {
            return $defaults;
        }

        if (is_array($csrfInput)) {
            return array_merge($defaults, $csrfInput, [
                'data-formie-csrf' => true,
            ]);
        }

        return $defaults;
    }

    private function _resolveFormAssetSettings(Form $form, array $renderOptions = []): array
    {
        return [
            'outputCss' => array_key_exists('outputCss', $renderOptions) ? (bool)$renderOptions['outputCss'] : $form->getFrontendTemplateOption('outputCss'),
            'outputJs' => array_key_exists('outputJs', $renderOptions) ? (bool)$renderOptions['outputJs'] : $form->getFrontendTemplateOption('outputJs'),
            'outputCssLocation' => $renderOptions['outputCssLocation'] ?? $form->getFrontendTemplateLocation('outputCssLocation'),
            'outputJsLocation' => $renderOptions['outputJsLocation'] ?? $form->getFrontendTemplateLocation('outputJsLocation'),
        ];
    }

    private function _renderResolvedFormAssets(Form $form, ?string $type, bool $forceInline, array $renderOptions = []): Markup
    {
        $view = Craft::$app->getView();
        $output = [];
        $assetSettings = $this->_resolveFormAssetSettings($form, $renderOptions);

        if ($type !== self::RENDER_TYPE_JS && ($renderOptions['includeCss'] ?? true)) {
            $assetUrls = Formie::$plugin->getBrowserAssets()->getBrowserAssetUrls();
            $activeFrame = $this->getActiveRenderFrame();
            $resolvedTheme = $activeFrame && $activeFrame->getForm() === $form
                ? $activeFrame->getResolvedTheme()
                : Formie::$plugin->getThemeConfigService()->resolve(
                    $form,
                    $renderOptions,
                    (bool)($renderOptions['_transportedThemeConfig'] ?? false),
                );
            $cssFiles = array_filter([
                $assetUrls['baseStyles'] ?? null,
                $resolvedTheme->isNone() ? null : ($assetUrls['themeStyles'] ?? null),
            ]);
            $cssAttributes = $renderOptions['cssAttributes'] ?? [];
            $outputCssLocation = $assetSettings['outputCssLocation'];

            if ($assetSettings['outputCss']) {
                foreach ($cssFiles as $cssFile) {
                    if ($outputCssLocation === FormTemplate::PAGE_HEADER && !$forceInline) {
                        $view->registerCssFile($cssFile, $cssAttributes);
                    } else {
                        $output[] = Html::cssFile($cssFile, $cssAttributes);
                    }
                }
            }
        }

        if ($type !== self::RENDER_TYPE_CSS && ($renderOptions['includeJs'] ?? true)) {
            $outputJsLocation = $assetSettings['outputJsLocation'];

            if ($assetSettings['outputJs'] && !$this->_renderedJs) {
                $output[] = $this->_renderBrowserJs($forceInline || $outputJsLocation !== FormTemplate::PAGE_FOOTER, $renderOptions);

                if ($outputJsLocation === FormTemplate::PAGE_FOOTER && !$forceInline) {
                    $output = [];
                }

                $this->_renderedJs = true;
            }
        }

        $output = array_filter($output, static fn($value) => $value !== null && $value !== '');

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }

    private function _renderBrowserCss(bool $inline, array $renderOptions = []): Markup
    {
        $view = Craft::$app->getView();
        $assetUrls = Formie::$plugin->getBrowserAssets()->getBrowserAssetUrls();
        $cssFiles = array_filter([
            $assetUrls['baseStyles'] ?? null,
            ($renderOptions['theme'] ?? 'formie') === 'none' ? null : ($assetUrls['themeStyles'] ?? null),
        ]);
        $output = [];
        $cssAttributes = $renderOptions['cssAttributes'] ?? [];

        if (!$cssFiles) {
            return TemplateHelper::raw('');
        }

        foreach ($cssFiles as $cssFile) {
            if ($inline) {
                $output[] = Html::cssFile($cssFile, $cssAttributes);
            } else {
                $view->registerCssFile($cssFile, $cssAttributes);
            }
        }

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }

    private function _renderBrowserJs(bool $inline, array $renderOptions = []): Markup
    {
        $view = Craft::$app->getView();
        $assetUrls = Formie::$plugin->getBrowserAssets()->getBrowserAssetUrls();
        $jsFile = $assetUrls['js'];
        $viteClientFile = $assetUrls['viteClient'] ?? null;
        $output = [];

        $scriptAttributes = $this->_getScriptAttributes($renderOptions);
        $jsAttributes = $this->_getJsAttributes($renderOptions);
        $translationsTag = $this->_renderBrowserTranslationsTag($renderOptions);

        if ($inline) {
            $output[] = $translationsTag;

            if ($viteClientFile) {
                $output[] = Html::jsFile($viteClientFile, ['type' => 'module']);
            }

            if ($jsFile) {
                $output[] = Html::jsFile($jsFile, $jsAttributes);
            }
        } else {
            $view->registerHtml($translationsTag, View::POS_END);

            if ($viteClientFile) {
                $view->registerJsFile($viteClientFile, ['type' => 'module']);
            }

            if ($jsFile) {
                $view->registerJsFile($jsFile, $jsAttributes);
            }
        }

        return TemplateHelper::raw(implode(PHP_EOL, $output));
    }

    private function _renderBrowserTranslationsTag(array $renderOptions = []): string
    {
        $attributes = array_merge($this->_getScriptAttributes($renderOptions), [
            'type' => 'application/json',
            'data-formie-translations' => true,
        ]);

        $translationsJson = Json::encode(
            $this->getBrowserJsTranslations(),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return Html::tag('script', $translationsJson, $attributes);
    }

    private function _getTranslatedStrings(array $array): array
    {
        $strings = [];

        foreach ($array as $item) {
            $strings[$item] = $this->_translateBrowserJsString($item);
        }

        return $strings;
    }

    private function _translateBrowserJsString(string $message): string
    {
        // Dynamic key — English source string is the Craft message key (see browserTranslationStringList()).
        return ValidationMessageCompatibility::translate($message);
    }

    private function _hydrateSubmitFlowProgress(Form $form): ?Submission
    {
        return $form->getCurrentSubmission();
    }

    private function _getFormFromTemplate(Form|string|null $form): ?Form
    {
        if ($form instanceof Form) {
            if ($siteId = Craft::$app->getSites()->getCurrentSite()->id) {
                return Formie::$plugin->getFormSiteOverrides()->applyToForm($form, (int)$siteId, true);
            }

            return $form;
        }

        if ($form && is_string($form)) {
            if ($form = Formie::$plugin->getForms()->getFormByHandle($form)) {
                return clone $form;
            }
        }

        return null;
    }

    private function _prepareFormForRender(Form $form, array $renderOptions = []): void
    {
        (new RuntimeConfiguration())->establish($form);
        $sessionKey = $renderOptions['sessionKey'] ?? null;
        $form->setSessionKey(base64_encode((string)$sessionKey));
    }

    private function _getJsAttributes(array $renderOptions = []): array
    {
        // Some attributes are JS-render related
        $attributes = $this->_getScriptAttributes($renderOptions);
        $jsAttributes = $renderOptions['jsAttributes'] ?? [];
        $browserStartupAttributes = $this->_getBrowserStartupScriptAttributes($renderOptions);

        return array_merge($attributes, ['type' => 'module'], $jsAttributes, $browserStartupAttributes);
    }

    private function _getScriptAttributes(array $renderOptions = []): array
    {
        $attributes = $this->getRenderVariables('scriptAttributes') ?? [];
        $scriptAttributes = $renderOptions['scriptAttributes'] ?? [];

        return array_merge(['type' => 'text/javascript'], $attributes, $scriptAttributes);
    }

    private function _getBrowserStartupScriptAttributes(array $renderOptions = []): array
    {
        return [
            'data-formie-startup' => true,
            // Keep page-level browser-script behavior on the script itself rather
            // than a preload global. Per-form init opt-out lives on the form root.
            'data-formie-use-observer' => ($renderOptions['useObserver'] ?? true) ? false : 'false',
        ];
    }
}
