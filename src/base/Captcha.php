<?php
namespace verbb\formie\base;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Stencil;

use Craft;

use Closure;

abstract class Captcha extends Integration
{
    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Captchas');
    }

    public static function supportsConnection(): bool
    {
        return false;
    }

    public static function supportsPayloadSending(): bool
    {
        return false;
    }


    // Properties
    // =========================================================================

    public bool $showAllPages = false;
    public ?string $spamReason = null;
    public ?bool $saveSpam = null;


    // Public Methods
    // =========================================================================

    public function getType(): string
    {
        return self::TYPE_CAPTCHA;
    }

    public function getCategory(): string
    {
        return self::CATEGORY_CAPTCHAS;
    }

    public function hasStrictValidation(): bool
    {
        return false;
    }

    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase($this->getHandle());

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "img/captchas/{$handle}.svg");
    }

    public function getFormSettingsHtml(Form|Stencil $form): string
    {
        return Craft::$app->getView()->renderTemplate('formie/integrations/captchas/_form-settings', [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function getFrontEndHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return '';
    }

    public function getFrontEndJsVariables(Form $form, FieldLayoutPage $page = null): ?array
    {
        return null;
    }

    public function getRefreshJsVariables(Form $form, FieldLayoutPage $page = null): array
    {
        return [];
    }

    public function getGqlVariables(Form $form, FieldLayoutPage $page = null): ?array
    {
        return null;
    }

    public function validateSubmission(Submission $submission): bool
    {
        return true;
    }

    public function getGqlHandle(): string
    {
        return StringHelper::toCamelCase($this->handle . 'Captcha');
    }


    // Protected Methods
    // =========================================================================

    protected function getOrSet(string $key, Closure $callable)
    {
        if ($value = Craft::$app->getSession()->get($key)) {
            return $value;
        }

        $value = $callable($this);

        Craft::$app->getSession()->set($key, $value);

        return $value;
    }

    protected function getCaptchaValue(Submission $submission, string $name): mixed
    {
        // For GQL requests, we set the data on the submission
        $gqlHandle = $this->getGqlHandle();
        $captchaValue = $submission->getCaptchaData($gqlHandle);

        if (is_array($captchaValue)) {
            return $captchaValue['value'] ?? null;
        }

        // Handle the traditional param, as a POST param
        return Craft::$app->getRequest()->getParam($name);
    }
}
