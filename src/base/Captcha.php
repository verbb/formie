<?php
namespace verbb\formie\base;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\CaptchaValidateSubmissionEvent;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\Stencil;

use Craft;
use craft\helpers\UrlHelper;

use Closure;
use Throwable;

abstract class Captcha extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_VALIDATE_SUBMISSION = 'beforeValidateSubmission';
    public const EVENT_AFTER_VALIDATE_SUBMISSION = 'afterValidateSubmission';


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
    public bool $validationErrored = false;


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

        return Craft::$app->getAssetManager()->getPublishedUrl('@verbb/formie/web/assets/cp/dist/', true, "icons/captchas/{$handle}.svg");
    }

    public function getCpIconPath(): string
    {
        $handle = trim((string)StringHelper::toKebabCase($this->getHandle()));
        return $handle !== '' ? "icons/captchas/{$handle}.svg" : '';
    }

    public function renderHtml(Form $form, FieldLayoutPage $page = null): string
    {
        return '';
    }

    public function getClientModule(ClientModuleContext $context): ?ClientModule
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

    public function runValidation(Submission $submission): bool
    {
        $this->validationErrored = false;

        $beforeEvent = new CaptchaValidateSubmissionEvent([
            'submission' => $submission,
            'success' => true,
        ]);
        $this->trigger(self::EVENT_BEFORE_VALIDATE_SUBMISSION, $beforeEvent);

        if ($beforeEvent->isValid === false) {
            return false;
        }

        try {
            $success = $this->validateSubmission($submission);
        } catch (Throwable $e) {
            return $this->handleValidationException($submission, $e);
        }

        $afterEvent = new CaptchaValidateSubmissionEvent([
            'submission' => $submission,
            'success' => $success,
        ]);
        $this->trigger(self::EVENT_AFTER_VALIDATE_SUBMISSION, $afterEvent);

        return $afterEvent->success;
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

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);

        $schema[] = SchemaHelper::lightswitchField([
            'label' => Craft::t('formie', 'Show on All Pages'),
            'instructions' => Craft::t('formie', 'For multi-page forms, choose whether to show the captcha on all pages of the form, or only on the final page of the form.'),
            'name' => 'showAllPages',
        ]);
        
        return $schema;
    }

    protected function getMissingSettingsWarningSchema(string $providerName, string $settingsTab): array
    {
        $settingsUrl = UrlHelper::cpUrl("formie/settings/spam-protection#tab-{$settingsTab}");

        return [
            '$el' => 'p',
            'attrs' => [
                'class' => 'warning with-icon',
            ],
            'children' => [
                Craft::t('formie', 'Please provide the site and secret keys for {name} in ', ['name' => $providerName]),
                [
                    '$el' => 'a',
                    'attrs' => [
                        'href' => $settingsUrl,
                    ],
                    'children' => Craft::t('formie', 'Settings'),
                ],
                '.',
            ],
        ];
    }

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

    protected function getValidationErrorMessage(): string
    {
        return Craft::t('formie', 'We couldn’t verify the form security check. Please try again.');
    }

    protected function handleValidationException(Submission $submission, Throwable $e, ?string $message = null): bool
    {
        $this->validationErrored = true;
        $this->spamReason = $message ?: $this->getValidationErrorMessage();

        Formie::error('Captcha validation failed for {captcha}: {message}', [
            'captcha' => static::displayName(),
            'message' => self::getExceptionLogMessage($e),
            'exception' => $e,
        ]);

        $submission->addError('form', $this->spamReason);

        return false;
    }
}
