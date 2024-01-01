<?php
namespace verbb\formie\integrations\captchas;

use verbb\formie\base\Captcha;
use verbb\formie\elements\Form;

use Craft;

use putyourlightson\snaptcha\models\SnaptchaModel;
use putyourlightson\snaptcha\Snaptcha as SnaptchaPlugin;

class Snaptcha extends Captcha
{
    // Properties
    // =========================================================================

    public ?string $handle = 'snaptcha';


    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return Craft::t('formie', 'Snaptcha');
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'Snaptcha is an invisible CAPTCHA that automatically validates forms and prevents spam bots from submitting to your Craft CMS site. Find out more via [Snaptcha Plugin](https://plugins.craftcms.com/snaptcha).');
    }

    public function getFrontEndHtml(Form $form, FormPage $page = null): string
    {
        $model = new SnaptchaModel();
        $fieldName = SnaptchaPlugin::$plugin->settings->fieldName;
        $fieldValue = SnaptchaPlugin::$plugin->snaptcha->getFieldValue($model) ?? '';

        return '<input type="hidden" name="' . $fieldName . '" value="' . $fieldValue . '">';
    }

    public function getGqlVariables(Form $form, FormPage $page = null): array
    {
        $model = new SnaptchaModel();
        $fieldName = SnaptchaPlugin::$plugin->settings->fieldName;
        $fieldValue = SnaptchaPlugin::$plugin->snaptcha->getFieldValue($model) ?? '';

        return [
            'formId' => $form->getFormId(),
            'sessionKey' => $fieldName,
            'value' => $fieldValue,
        ];
    }

}
