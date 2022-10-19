<?php
namespace verbb\formie\fields\formfields;

use verbb\formie\base\FormField;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyPurifierConfigEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\positions\Hidden as HiddenPosition;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\HTMLPurifier;
use craft\helpers\Json;

use yii\base\Exception;

use HTMLPurifier_Config;

use LitEmoji\LitEmoji;

class Html extends FormField
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_PURIFIER_CONFIG = 'modifyPurifierConfig';


    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('formie', 'HTML');
    }

    /**
     * @inheritDoc
     */
    public static function getSvgIconPath(): string
    {
        return 'formie/_formfields/html/icon.svg';
    }

    /**
     * @inheritDoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }


    // Properties
    // =========================================================================

    public ?string $htmlContent = null;


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
        $this->htmlContent = LitEmoji::shortcodeToUnicode($this->htmlContent);

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
        $htmlContent = LitEmoji::shortcodeToUnicode($htmlContent);

        // Ensure we run it all through purifier
        return HTMLPurifier::process($htmlContent, $this->_getPurifierConfig());
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefaults(): array
    {
        return [
            'labelPosition' => HiddenPosition::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Add emoji support to HTML content
        $this->htmlContent = LitEmoji::unicodeToShortcode($this->htmlContent);

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritDoc
     */
    public function getInputHtml(mixed $value, ?ElementInterface $element = null): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/html/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getPreviewInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('formie/_formfields/html/preview', [
            'field' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function defineAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
        ];
    }

    /**
     * @inheritDoc
     */
    public function defineAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
        ];
    }

    public function defineConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
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
        $def->addElement('details', 'Block', 'Flow', 'Common', [ 'open' => new \HTMLPurifier_AttrDef_HTML_Bool(true)]);
        $def->addElement('summary', 'Inline', 'Inline', 'Common');

        // Give plugins a chance to modify the HTML Purifier config, or add new ones
        $event = new ModifyPurifierConfigEvent([
            'config' => $purifierConfig,
        ]);

        $this->trigger(self::EVENT_MODIFY_PURIFIER_CONFIG, $event);

        return $event->config;
    }

    /**
     * Returns a JSON-decoded config, if it exists.
     *
     * @param string $dir The directory name within the config/ folder to look for the config file
     * @param string|null $file The filename to load.
     * @return array|false The config, or false if the file doesn't exist
     * @throws Exception
     */
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
