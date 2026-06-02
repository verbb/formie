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

        ];
    }

    private static function _registerAlias(string $class, string $alias): void
    {
        if (class_exists($alias, false) || interface_exists($alias, false)) {
            return;
        }

        class_alias($class, $alias);
    }
}
