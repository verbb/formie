<?php
namespace verbb\formie\fields;

use verbb\formie\base\CosmeticField;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPurifierConfigEvent;
use verbb\formie\helpers\HtmlHelper;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html as CraftHtml;
use craft\helpers\HTMLPurifier;
use craft\helpers\Json;
use craft\helpers\Template;

use GraphQL\Type\Definition\Type;

use HTMLPurifier_Config;
use HTMLPurifier_AttrDef_HTML_Bool;

class Html extends CosmeticField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'HTML');
    }

    public static function translatableProperties(): array
    {
        return ['htmlContent'];
    }

    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/html/icon.svg';
    }

    public static function defineFieldType(): array
    {
        return array_merge(parent::defineFieldType(), [
            'hasLabel' => true,
        ]);
    }
    

    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PURIFIER_CONFIG = 'modifyPurifierConfig';


    // Properties
    // =========================================================================

    public ?string $htmlContent = null;
    public bool $purifyContent = true;
    public bool $allowTwig = false;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        $config['labelPosition'] = $config['labelPosition'] ?? HiddenPosition::class;

        parent::__construct($config);
    }

    public function hasLabel(): bool
    {
        return true;
    }

    public function getRenderedHtmlContent(array $variables = []): string
    {
        $htmlContent = trim((string)$this->htmlContent);

        if ($htmlContent && $this->allowTwig) {
            $htmlContent = Formie::$plugin->getTemplates()->renderString($htmlContent, $variables);
        }

        if ($this->purifyContent) {
            return HTMLPurifier::process($htmlContent, $this->_getPurifierConfig());
        }

        return $htmlContent;
    }

    /**
     * Rendered HTML block: merges {@see RenderFrame::getTemplateVars()} with `field` / `form` / `submission` / `value` for the inline Twig in `htmlContent`.
     */
    public function getRenderedHtmlBlock(Form $form, mixed $value, ?ElementInterface $submission = null): string
    {
        $templateVars = Formie::$plugin->getRendering()->getActiveRenderFrame()?->getTemplateVars() ?? [];
        $variables = array_merge($templateVars, [
            'field' => $this,
            'form' => $form,
            'submission' => $submission,
            'value' => $value,
        ]);

        return $this->getRenderedHtmlContent($variables);
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewHtml(),
        ];
    }

    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        $form = $submission->getForm();

        if (!$form) {
            return false;
        }

        $html = $this->getRenderedHtmlBlock($form, $value, $submission);

        if ($html === '') {
            return false;
        }

        return Template::raw($html);
    }

    public function isValueEmpty(mixed $value, ?ElementInterface $element): bool
    {
        return trim((string)$this->htmlContent) === '';
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'htmlContent' => [
                'name' => 'htmlContent',
                'type' => Type::string(),
            ],
            'purifyContent' => [
                'name' => 'purifyContent',
                'type' => Type::boolean(),
            ],
            'allowTwig' => [
                'name' => 'allowTwig',
                'type' => Type::boolean(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::htmlEditorField(array_merge([
                'label' => Craft::t('formie', 'HTML Content'),
                'instructions' => Craft::t('formie', 'Enter HTML or Twig content to be rendered for this field.'),
                'name' => 'htmlContent',
                'validation' => 'required',
                'required' => true,
            ], HtmlHelper::getHtmlEditorConfig('fields.html'))),
            SchemaHelper::includeInEmailFieldSummariesField(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Allow Twig'),
                'instructions' => Craft::t('formie', 'Whether to parse Twig in this field’s content when rendering the form. Twig is evaluated in Formie’s sandbox and cannot access Craft’s full template environment.'),
                'name' => 'allowTwig',
            ]),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Purify Content'),
                'instructions' => Craft::t('formie', 'Whether to run [HTML Purifier](http://htmlpurifier.org) over the content to prevent malicious or invalid code being included.'),
                'name' => 'purifyContent',
            ]),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function modifyFieldSettings(array $settings): array
    {
        $form = $this->getForm();

        if ($form) {
            $settings['_builderPreviewHtml'] = $this->getRenderedHtmlBlock($form, null, null);
        }

        return $settings;
    }


    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key === 'fieldLabel') {
            $labelPosition = $context->get('labelPosition');

            if ($labelPosition instanceof HiddenPosition) {
                return null;
            }

            return SlotTag::make('label')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-html-field-label' => true,
                ])
                ->theme([
                    'class' => [
                        'formie-label',
                        'formie-field-label',
                        'formie-html-field-label',
                    ],
                ]);
        }

        return parent::defineFieldSlotTag($key, $context);
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $submission = $element instanceof Submission ? $element : null;
        $form = $submission?->getForm();

        if (!$form) {
            return '';
        }

        $html = $this->getRenderedHtmlBlock($form, $value, $submission);

        if ($html === '') {
            return CraftHtml::tag('p', Craft::t('formie', 'No HTML content configured.'), [
                'class' => 'light',
            ]);
        }

        return CraftHtml::tag('div', $html, [
            'class' => ['formie-cp-cosmetic-field-preview'],
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _getPurifierConfig(): HTMLPurifier_Config
    {
        $purifierConfig = HTMLPurifier_Config::createDefault();
        $purifierConfig->autoFinalize = false;

        $config = $this->_getConfig('htmlpurifier', 'Default.json') ?: [
            'Attr.AllowedFrameTargets' => ['_blank'],
            'Attr.EnableID' => true,
            'HTML.AllowedComments' => ['pagebreak'],
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(https?:)?//(www.youtube.com/embed/|player.vimeo.com/video/)%',
        ];

        foreach ($config as $option => $configValue) {
            $purifierConfig->set($option, $configValue);
        }

        $def = $purifierConfig->getHTMLDefinition(true);
        $def->addElement('details', 'Block', 'Flow', 'Common', ['open' => new HTMLPurifier_AttrDef_HTML_Bool(true)]);
        $def->addElement('summary', 'Inline', 'Inline', 'Common');

        $event = new ModifyPurifierConfigEvent([
            'config' => $purifierConfig,
        ]);

        $this->trigger(self::EVENT_MODIFY_PURIFIER_CONFIG, $event);

        return $event->config;
    }

    private function _getConfig(string $dir, string $file = null): bool|array
    {
        if (!$file) {
            $file = 'Default.json';
        }

        $path = Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file;

        if (!is_file($path)) {
            if ($file !== 'Default.json') {
                return $this->_getConfig($dir);
            }

            return false;
        }

        return Json::decode(file_get_contents($path));
    }
}
