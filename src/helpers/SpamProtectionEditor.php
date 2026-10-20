<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\FormInterface;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\models\MissingIntegration;

use Craft;
use craft\db\Query;
use craft\helpers\App;

use yii\web\BadRequestHttpException;

class SpamProtectionEditor
{
    // Static Methods
    // =========================================================================

    public static function provider(string $handle): IntegrationInterface
    {
        $provider = Formie::$plugin->getIntegrations()->getCaptchaByHandle($handle);

        if (!$provider || $provider instanceof MissingIntegration) {
            throw new BadRequestHttpException('This CAPTCHA provider is unavailable. Its saved settings have been preserved.');
        }

        return clone $provider;
    }

    public static function mask(IntegrationInterface $provider): array
    {
        $secrets = [];

        foreach (IntegrationSecrets::sensitiveAttributes($provider) as $attribute) {
            if (!empty($provider->$attribute) && !(is_string($provider->$attribute) && str_starts_with($provider->$attribute, '$'))) {
                $secrets[] = $attribute;
                $provider->$attribute = self::SECRET_PLACEHOLDER;
            }
        }

        return $secrets;
    }

    public static function postedProvider(string $handle, array $config, bool $clearEmptySecrets = false): IntegrationInterface
    {
        $provider = self::provider($handle);
        $settings = $config['settings'] ?? [];

        if (!is_array($settings) || array_diff(array_keys($settings), array_keys($provider->getSettings()))) {
            throw new BadRequestHttpException('Invalid CAPTCHA settings.');
        }

        foreach (IntegrationSecrets::sensitiveAttributes($provider) as $attribute) {
            if (!empty($config['clearSecrets'][$attribute]) || ($clearEmptySecrets && array_key_exists($attribute, $settings) && $settings[$attribute] === '')) {
                $settings[$attribute] = null;
            } elseif (($settings[$attribute] ?? '') === '' || ($settings[$attribute] ?? null) === self::SECRET_PLACEHOLDER) {
                unset($settings[$attribute]);
            }
        }

        $config['settings'] = $settings;
        unset($config['type'], $config['scope'], $config['handle']);
        $provider = Formie::$plugin->getIntegrations()->buildCaptchaFromPostedConfig($handle, $config);

        if (!$provider->validate(array_keys($provider->getSettings())) || ($provider->getEnabled() && !$provider->hasValidSettings())) {
            throw new BadRequestHttpException(Craft::t('formie', 'Complete the required CAPTCHA settings before enabling this provider.'));
        }

        return $provider;
    }

    public static function availability(IntegrationInterface $provider): string
    {
        if (!$provider->getEnabled()) {
            return Craft::t('formie', 'Disabled');
        }

        if (!$provider->hasValidSettings()) {
            return Craft::t('formie', 'Settings required');
        }

        if (self::hasMissingEnvironmentVariable($provider)) {
            return Craft::t('formie', 'A configured environment variable is unavailable on this site.');
        }

        return Craft::t('formie', 'Enabled (credentials have not been verified)');
    }

    public static function hasMissingEnvironmentVariable(IntegrationInterface $provider): bool
    {
        foreach (array_unique([...IntegrationSecrets::sensitiveAttributes($provider), 'siteKey']) as $attribute) {
            $value = $provider->$attribute ?? null;

            if (is_string($value) && str_starts_with($value, '$') && !App::parseEnv($value)) {
                return true;
            }
        }

        return false;
    }

    public static function formWarnings(FormInterface $form): array
    {
        $warnings = [];
        $handles = (new Query())->select('handle')->from(Table::FORMIE_CAPTCHA_PROVIDERS)->column();

        foreach ($form->getSettings()->integrations as $handle => $binding) {
            if (!in_array($handle, $handles, true) || !filter_var($binding['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $provider = Formie::$plugin->getIntegrations()->getCaptchaByHandle($handle);

            if (!$provider || $provider instanceof MissingIntegration) {
                $reason = Craft::t('formie', 'Provider unavailable');
            } elseif (!$provider->getEnabled() || !$provider->hasValidSettings() || self::hasMissingEnvironmentVariable($provider)) {
                $reason = self::availability($provider);
            } else {
                continue;
            }

            $warnings[] = [
                '$el' => 'p',
                'attrs' => ['class' => 'p-4 text-warning'],
                'children' => Craft::t('formie', '{name} is selected for this form but is not ready to protect it: {reason}. Ask an administrator to review it in Spam Protection.', ['name' => $provider?->getName() ?: $handle, 'reason' => $reason]),
            ];
        }

        return $warnings;
    }


    // Constants
    // =========================================================================

    public const SECRET_PLACEHOLDER = '••••••••';
}
