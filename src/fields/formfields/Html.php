<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPurifierConfigEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\HTMLPurifier;
use craft\helpers\Json;

use yii\base\Exception;

use HTMLPurifier_Config;
use HTMLPurifier_AttrDef_HTML_Bool;

class Html extends FormField
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


    // Properties
    // =========================================================================

    public ?string $htmlContent = null;
    public bool $purifyContent = true;


    // Public Methods
    // =========================================================================

    public function getIsCosmetic(): bool
    {
        return true;
    }

    public function getSavedFieldConfig(): array
    {
        // Add emoji support when generating the field config for the form builder.
        // Otherwise, the shortcodes will be shown after saving and refreshing the form builder.
        $this->htmlContent = StringHelper::shortcodesToEmoji((string)$this->htmlContent);

        return parent::getSavedFieldConfig();
    }

    public function getRenderedHtmlContent(): string
    {
        $htmlContent = trim($this->htmlContent);

        // Render Twig content first
        if ($htmlContent) {
            $htmlContent = Craft::$app->getView()->renderString($this->htmlContent);
        }

        // Add emoji support
        $htmlContent = StringHelper::shortcodesToEmoji($htmlContent);

        if ($this->purifyContent) {
            // Ensure we run it all through purifier
            return HTMLPurifier::process($htmlContent, $this->_getPurifierConfig());
        }

        return $htmlContent;
    }

    public function getFieldTypeConfigDefaults(): array
    {
        return [
            'labelPosition' => HiddenPosition::class,
        ];
    }

    public function beforeSave(bool $isNew): bool
    {
        // Add emoji support to HTML content
        $this->htmlContent = StringHelper::emojiToShortcodes((string)$this->htmlContent);

        return parent::beforeSave($isNew);
    }

    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/html/preview', [
            'field' => $this,
        ]);
    }

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }

    public function defineGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textareaField([
                'label' => Craft::t('formie', 'HTML Content'),
                'help' => Craft::t('formie', 'Enter HTML or Twig content to be rendered for this field.'),
                'name' => 'htmlContent',
                'rows' => '10',
            ]),
        ];
    }

    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Purify Content'),
                'help' => Craft::t('formie', 'Whether to run [HTML Purifier](http://htmlpurifier.org) over the content to prevent malicious or invalid code being included.'),
                'name' => 'purifyContent',
            ]),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
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
