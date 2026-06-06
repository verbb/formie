<?php
namespace verbb\formie\fields;

use verbb\formie\Formie;
use verbb\formie\base\CosmeticField;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPurifierConfigEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\HTMLPurifier;
use craft\helpers\Json;

use yii\base\Exception;

use GraphQL\Type\Definition\Type;

use HTMLPurifier_Config;
use HTMLPurifier_AttrDef_HTML_Bool;

class Html extends CosmeticField
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PURIFIER_CONFIG = 'modifyPurifierConfig';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'HTML');
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


    // Properties
    // =========================================================================

    public ?string $htmlContent = null;
    public bool $purifyContent = true;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Setuo defaults for some values which can't in in the property definition
        $config['labelPosition'] = $config['labelPosition'] ?? HiddenPosition::class;

        parent::__construct($config);
    }

    public function hasLabel(): bool
    {
        return true;
    }

    public function getRenderedHtmlContent(array $variables = []): string
    {
        $htmlContent = trim($this->htmlContent);

        // Render Twig content first
        if ($htmlContent) {
            $htmlContent = Craft::$app->getView()->renderString($this->htmlContent, $variables);
        }

        if ($this->purifyContent) {
            // Ensure we run it all through purifier
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
        return false;
    }

    public function getSettingGqlTypes(): array
    {
        return array_merge(parent::getSettingGqlTypes(), [
            'htmlContent' => [
                'name' => 'htmlContent',
                'type' => Type::string(),
            ],
        ]);
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textareaField([
                'label' => Craft::t('formie', 'HTML Content'),
                'instructions' => Craft::t('formie', 'Enter HTML or Twig content to be rendered for this field.'),
                'name' => 'htmlContent',
                'rows' => '10',
            ]),
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

    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key === 'fieldLabel') {
            $labelPosition = $context->get('labelPosition');

            // In this case, we don't need the label hidden from screen readers as there's no control to be accessible for
            if ($labelPosition instanceof HiddenPosition) {
                return null;
            }

            return SlotTag::make('label')
                ->core([
                    'data-formie-label' => true,
                    'data-formie-field-label' => true,
                    'data-formie-html-field-label' => true,
                    // Exclude the `for` attribute, as it's invalid (there's no form control to refer to),,,,,
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
        return Craft::$app->getView()->renderTemplate('formie/_formfields/html/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
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

        foreach ($config as $option => $value) {
            $purifierConfig->set($option, $value);
        }

        // Add some extra, modern elements to be supported `<details>`, `<summary>`
        $def = $purifierConfig->getHTMLDefinition(true);
        $def->addElement('details', 'Block', 'Flow', 'Common', [ 'open' => new HTMLPurifier_AttrDef_HTML_Bool(true)]);
        $def->addElement('summary', 'Inline', 'Inline', 'Common');

        // Give plugins a chance to modify the HTML Purifier config, or add new ones
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
                // Try again with Default
                return $this->_getConfig($dir);
            }

            return false;
        }

        return Json::decode(file_get_contents($path));
    }
}
