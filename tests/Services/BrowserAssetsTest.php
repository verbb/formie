<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\events\ModifyBrowserJsTranslationsEvent;
use verbb\formie\Formie;
use verbb\formie\services\Rendering;

it('renders shared browser assets without resolving a form', function (): void {
    $assets = WebRequestTestHelper::withWebRequestContext(static fn(): string => (string)Formie::$plugin->getRendering()->browserAssets([
        'inline' => true,
    ]));

    expect($assets)
        ->toContain('formie-base.css')
        ->toContain('formie-theme.css')
        ->toContain('data-formie-translations')
        ->toContain('formie.js');
});

it('retains Formie 3 shared asset helpers through the browser asset boundary', function (): void {
    [$css, $js] = WebRequestTestHelper::withWebRequestContext(static function (): array {
        $rendering = Formie::$plugin->getRendering();

        return [
            (string)$rendering->renderCss(true),
            (string)$rendering->renderJs(true),
        ];
    });

    expect($css)
        ->toContain('formie-base.css')
        ->toContain('formie-theme.css')
        ->and($js)
        ->toContain('data-formie-translations')
        ->toContain('formie.js');
});

it('allows extensions to add browser JavaScript translation strings', function (): void {
    $rendering = new Rendering();
    $eventHandled = false;
    $handler = static function(ModifyBrowserJsTranslationsEvent $event) use (&$eventHandled): void {
        $eventHandled = true;
        $event->strings[] = 'Custom browser message.';
    };
    $rendering->on(Rendering::EVENT_MODIFY_BROWSER_JS_TRANSLATIONS, $handler);

    try {
        $translations = $rendering->getBrowserJsTranslations();
    } finally {
        $rendering->off(Rendering::EVENT_MODIFY_BROWSER_JS_TRANSLATIONS, $handler);
    }

    expect($eventHandled)->toBeTrue()
        ->and($translations)->toHaveKey('Custom browser message.');
});
