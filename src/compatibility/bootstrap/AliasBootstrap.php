<?php
namespace verbb\formie\compatibility\bootstrap;

class AliasBootstrap
{
    // Properties
    // =========================================================================

    private static bool $registered = false;


    // Static Methods
    // =========================================================================

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        foreach (self::_aliases() as [$class, $alias]) {
            self::_registerAlias($class, $alias);
        }
    }

    private static function _aliases(): array
    {
        return [
            // Added in 3.1.3
            ['verbb\\formie\\services\\Countries', 'verbb\\formie\\services\\Phone'],

            // Added in 3.0.0
            ['verbb\\formie\\base\\Field', 'verbb\\formie\\base\\FormField'],
            ['verbb\\formie\\base\\FieldInterface', 'verbb\\formie\\base\\FormFieldInterface'],

            // Do not alias Formie 2 Craft field classes to Formie 4 layout field classes.
            // Craft core migrations should see unresolved `fields\formfields` rows as missing
            // fields until Formie's own migrations convert their raw database records.

            // Added in 4.0.0
            ['verbb\\formie\\client\\models\\PageTransitionRequest', 'verbb\\formie\\runtime\\models\\PageTransitionRequest'],
            ['verbb\\formie\\client\\models\\SessionRefreshRequest', 'verbb\\formie\\runtime\\models\\SessionRefreshRequest'],
            ['verbb\\formie\\client\\models\\SubmitRequest', 'verbb\\formie\\runtime\\models\\SubmitRequest'],
            ['verbb\\formie\\events\\ModifyFrontendJsTranslationsEvent', 'verbb\\formie\\events\\ModifyRuntimeJsTranslationsEvent'],
            ['verbb\\formie\\models\\ClientModule', 'verbb\\formie\\models\\RuntimeModule'],
            ['verbb\\formie\\models\\ClientModuleContext', 'verbb\\formie\\models\\RuntimeModuleContext'],
            ['verbb\\formie\\models\\RenderFrame', 'verbb\\formie\\models\\RuntimeRenderFrame'],
            ['verbb\\formie\\services\\FrontendAssets', 'verbb\\formie\\services\\RuntimeAssets'],
            ['verbb\\formie\\compatibility\\client\\ClientCompatibility', 'verbb\\formie\\compatibility\\runtime\\RuntimeCompatibility'],
            ['verbb\\formie\\compatibility\\client\\RefreshTokensCompatibility', 'verbb\\formie\\compatibility\\runtime\\RefreshTokensCompatibility'],

            // Option sources replaced the standalone predefined options service in 4.0.0.
            ['verbb\\formie\\services\\OptionSources', 'verbb\\formie\\services\\PredefinedOptions'],

            ['verbb\\formie\\options\\predefined\\Acceptability', 'verbb\\formie\\options\\Acceptability'],
            ['verbb\\formie\\options\\predefined\\Age', 'verbb\\formie\\options\\Age'],
            ['verbb\\formie\\options\\predefined\\Agreement', 'verbb\\formie\\options\\Agreement'],
            ['verbb\\formie\\options\\predefined\\Comparison', 'verbb\\formie\\options\\Comparison'],
            ['verbb\\formie\\options\\predefined\\Continents', 'verbb\\formie\\options\\Continents'],
            ['verbb\\formie\\options\\predefined\\Countries', 'verbb\\formie\\options\\Countries'],
            ['verbb\\formie\\options\\predefined\\Currencies', 'verbb\\formie\\options\\Currencies'],
            ['verbb\\formie\\options\\predefined\\Days', 'verbb\\formie\\options\\Days'],
            ['verbb\\formie\\options\\predefined\\Difficulty', 'verbb\\formie\\options\\Difficulty'],
            ['verbb\\formie\\options\\predefined\\Education', 'verbb\\formie\\options\\Education'],
            ['verbb\\formie\\options\\predefined\\Employment', 'verbb\\formie\\options\\Employment'],
            ['verbb\\formie\\options\\predefined\\Gender', 'verbb\\formie\\options\\Gender'],
            ['verbb\\formie\\options\\predefined\\HowLong', 'verbb\\formie\\options\\HowLong'],
            ['verbb\\formie\\options\\predefined\\HowOften', 'verbb\\formie\\options\\HowOften'],
            ['verbb\\formie\\options\\predefined\\Importance', 'verbb\\formie\\options\\Importance'],
            ['verbb\\formie\\options\\predefined\\Industry', 'verbb\\formie\\options\\Industry'],
            ['verbb\\formie\\options\\predefined\\Languages', 'verbb\\formie\\options\\Languages'],
            ['verbb\\formie\\options\\predefined\\MaritalStatus', 'verbb\\formie\\options\\MaritalStatus'],
            ['verbb\\formie\\options\\predefined\\Months', 'verbb\\formie\\options\\Months'],
            ['verbb\\formie\\options\\predefined\\Satisfaction', 'verbb\\formie\\options\\Satisfaction'],
            ['verbb\\formie\\options\\predefined\\Size', 'verbb\\formie\\options\\Size'],
            ['verbb\\formie\\options\\predefined\\StatesAustralia', 'verbb\\formie\\options\\StatesAustralia'],
            ['verbb\\formie\\options\\predefined\\StatesCanada', 'verbb\\formie\\options\\StatesCanada'],
            ['verbb\\formie\\options\\predefined\\StatesUsa', 'verbb\\formie\\options\\StatesUsa'],
            ['verbb\\formie\\options\\predefined\\WouldYou', 'verbb\\formie\\options\\WouldYou'],

            ['verbb\\formie\\options\\ElementOptionSourceHelper', 'verbb\\formie\\options\\sources\\ElementOptionSourceHelper'],
            ['verbb\\formie\\options\\IntegrationOptionSourceHelper', 'verbb\\formie\\options\\sources\\IntegrationOptionSourceHelper'],
            ['verbb\\formie\\options\\OptionList', 'verbb\\formie\\options\\sources\\OptionList'],
            ['verbb\\formie\\options\\OptionResolvableInterface', 'verbb\\formie\\options\\sources\\OptionResolvableInterface'],
            ['verbb\\formie\\options\\OptionSourceConfigHelper', 'verbb\\formie\\options\\sources\\OptionSourceConfigHelper'],
            ['verbb\\formie\\options\\OptionSourceContext', 'verbb\\formie\\options\\sources\\OptionSourceContext'],
            ['verbb\\formie\\options\\OptionSourceFieldInterface', 'verbb\\formie\\options\\sources\\OptionSourceFieldInterface'],
            ['verbb\\formie\\options\\OptionSourceResolverInterface', 'verbb\\formie\\options\\sources\\OptionSourceResolverInterface'],
            ['verbb\\formie\\options\\OptionSourceResolverInterface', 'verbb\\formie\\options\\sources\\OptionSourceProviderInterface'],
            ['verbb\\formie\\options\\OptionSourceValidationMode', 'verbb\\formie\\options\\sources\\OptionSourceValidationMode'],
            ['verbb\\formie\\options\\resolvers\\PredefinedOptionSourceResolver', 'verbb\\formie\\options\\sources\\resolvers\\PredefinedOptionSourceResolver'],
            ['verbb\\formie\\options\\resolvers\\ElementOptionSourceResolver', 'verbb\\formie\\options\\sources\\resolvers\\ElementOptionSourceResolver'],
            ['verbb\\formie\\options\\resolvers\\IntegrationOptionSourceResolver', 'verbb\\formie\\options\\sources\\resolvers\\IntegrationOptionSourceResolver'],
            ['verbb\\formie\\options\\resolvers\\PredefinedOptionSourceResolver', 'verbb\\formie\\options\\sources\\providers\\BuiltinOptionSourceProvider'],
            ['verbb\\formie\\options\\resolvers\\PredefinedOptionSourceResolver', 'verbb\\formie\\options\\sources\\providers\\PredefinedOptionSourceProvider'],
            ['verbb\\formie\\options\\resolvers\\ElementOptionSourceResolver', 'verbb\\formie\\options\\sources\\providers\\ElementOptionSourceProvider'],
            ['verbb\\formie\\options\\resolvers\\IntegrationOptionSourceResolver', 'verbb\\formie\\options\\sources\\providers\\IntegrationOptionSourceProvider'],

            ['verbb\\formie\\options\\predefined\\Acceptability', 'verbb\\formie\\options\\sources\\predefined\\Acceptability'],
            ['verbb\\formie\\options\\predefined\\Age', 'verbb\\formie\\options\\sources\\predefined\\Age'],
            ['verbb\\formie\\options\\predefined\\Agreement', 'verbb\\formie\\options\\sources\\predefined\\Agreement'],
            ['verbb\\formie\\options\\predefined\\Comparison', 'verbb\\formie\\options\\sources\\predefined\\Comparison'],
            ['verbb\\formie\\options\\predefined\\Continents', 'verbb\\formie\\options\\sources\\predefined\\Continents'],
            ['verbb\\formie\\options\\predefined\\Countries', 'verbb\\formie\\options\\sources\\predefined\\Countries'],
            ['verbb\\formie\\options\\predefined\\Currencies', 'verbb\\formie\\options\\sources\\predefined\\Currencies'],
            ['verbb\\formie\\options\\predefined\\Days', 'verbb\\formie\\options\\sources\\predefined\\Days'],
            ['verbb\\formie\\options\\predefined\\Difficulty', 'verbb\\formie\\options\\sources\\predefined\\Difficulty'],
            ['verbb\\formie\\options\\predefined\\Education', 'verbb\\formie\\options\\sources\\predefined\\Education'],
            ['verbb\\formie\\options\\predefined\\Employment', 'verbb\\formie\\options\\sources\\predefined\\Employment'],
            ['verbb\\formie\\options\\predefined\\Gender', 'verbb\\formie\\options\\sources\\predefined\\Gender'],
            ['verbb\\formie\\options\\predefined\\HowLong', 'verbb\\formie\\options\\sources\\predefined\\HowLong'],
            ['verbb\\formie\\options\\predefined\\HowOften', 'verbb\\formie\\options\\sources\\predefined\\HowOften'],
            ['verbb\\formie\\options\\predefined\\Importance', 'verbb\\formie\\options\\sources\\predefined\\Importance'],
            ['verbb\\formie\\options\\predefined\\Industry', 'verbb\\formie\\options\\sources\\predefined\\Industry'],
            ['verbb\\formie\\options\\predefined\\Languages', 'verbb\\formie\\options\\sources\\predefined\\Languages'],
            ['verbb\\formie\\options\\predefined\\MaritalStatus', 'verbb\\formie\\options\\sources\\predefined\\MaritalStatus'],
            ['verbb\\formie\\options\\predefined\\Months', 'verbb\\formie\\options\\sources\\predefined\\Months'],
            ['verbb\\formie\\options\\predefined\\Satisfaction', 'verbb\\formie\\options\\sources\\predefined\\Satisfaction'],
            ['verbb\\formie\\options\\predefined\\Size', 'verbb\\formie\\options\\sources\\predefined\\Size'],
            ['verbb\\formie\\options\\predefined\\StatesAustralia', 'verbb\\formie\\options\\sources\\predefined\\StatesAustralia'],
            ['verbb\\formie\\options\\predefined\\StatesCanada', 'verbb\\formie\\options\\sources\\predefined\\StatesCanada'],
            ['verbb\\formie\\options\\predefined\\StatesUsa', 'verbb\\formie\\options\\sources\\predefined\\StatesUsa'],
            ['verbb\\formie\\options\\predefined\\WouldYou', 'verbb\\formie\\options\\sources\\predefined\\WouldYou'],

            // Added in 4.0.0 — form and submission status type renames
            ['verbb\\formie\\services\\SubmissionStatuses', 'verbb\\formie\\services\\Statuses'],
            ['verbb\\formie\\models\\SubmissionStatus', 'verbb\\formie\\models\\Status'],
            ['verbb\\formie\\records\\SubmissionStatus', 'verbb\\formie\\records\\Status'],
            ['verbb\\formie\\events\\SubmissionStatusEvent', 'verbb\\formie\\events\\StatusEvent'],
            ['verbb\\formie\\controllers\\SubmissionStatusesController', 'verbb\\formie\\controllers\\StatusesController'],
        ];
    }

    private static function _registerAlias(string $class, string $alias): void
    {
        if (class_exists($alias, false) || interface_exists($alias, false)) {
            return;
        }

        class_exists($class);
        class_alias($class, $alias);
    }
}
