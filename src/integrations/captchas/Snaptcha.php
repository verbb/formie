<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleContext;
use verbb\formie\models\FieldLayoutPage;

use Craft;
use craft\helpers\Html;

use putyourlightson\snaptcha\models\SnaptchaModel;
use putyourlightson\snaptcha\Snaptcha as SnaptchaPlugin;

class Snaptcha extends Captcha
{
    // Static Methods
    // =========================================================================

    public static function getRequiredPlugins(): array
    {
        return ['snaptcha'];
    }


    // Properties
    // =========================================================================

    public ?string $handle = 'snaptcha';


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Snaptcha';
    }

    public function getDescription(): string
    {
        return 'Snaptcha is an invisible CAPTCHA that automatically validates forms and prevents spam bots from submitting to your Craft CMS site. Find out more via [Snaptcha Plugin](https://plugins.craftcms.com/snaptcha).';
    }

    public function renderHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return Html::tag('div', null, [
            'class' => 'formie-snaptcha-captcha-placeholder',
            'data-snaptcha-captcha-placeholder' => true,
        ]);
    }

    public function getRefreshJsVariables(Form $form, FieldLayoutPage $page = null): array
    {
        $model = new SnaptchaModel();
        $fieldName = SnaptchaPlugin::$plugin->settings->fieldName;
        $fieldValue = SnaptchaPlugin::$plugin->snaptcha->getFieldValue($model) ?? '';

        return [
            'formId' => $form->getRenderId(),
            'sessionKey' => $fieldName,
            'value' => $fieldValue,
        ];
    }

    public function getBrowserModule(BrowserModuleContext $context): ?BrowserModuleEntry
    {
        if (!$context->form) {
            return null;
        }

        $refresh = $this->getRefreshJsVariables($context->form, $context->page);

        return new BrowserModuleEntry([
            'moduleId' => 'formie:snaptcha',
            'surfaces' => [BrowserModuleEntry::SURFACE_SERVER_RENDERED, BrowserModuleEntry::SURFACE_CLIENT_RENDERED],
            'config' => [
                'handle' => $this->handle,
                'placeholderSelector' => '[data-snaptcha-captcha-placeholder]',
                'sessionKey' => $refresh['sessionKey'] ?? null,
                'value' => $refresh['value'] ?? null,
                'formId' => $refresh['formId'] ?? null,
            ],
        ]);
    }
    
    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return $this->getRefreshJsVariables($form, $page);
    }

}
