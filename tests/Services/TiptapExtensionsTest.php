<?php

declare(strict_types=1);

use verbb\formie\events\RegisterTiptapExtensionsEvent;
use verbb\formie\Formie;
use verbb\formie\models\RichText;
use verbb\formie\services\TiptapExtensions;
use verbb\formie\tiptap\TextStyleDefinition;

use Tiptap\Core\Extension;
use yii\base\Event;

class FormieTestTiptapExtension extends Extension
{
    public static $name = 'formieTestAnnotation';
}

it('shares registered TipTap extensions and declarative styles with PHP and client bootstrap', function(): void {
    $handler = static function(RegisterTiptapExtensionsEvent $event): void {
        $event->registerExtension('tests/annotation', new FormieTestTiptapExtension());
        $event->registerTextStyle(
            new TextStyleDefinition(
                id: 'tests-uppercase',
                label: 'Uppercase',
                attribute: 'textTransform',
                cssProperty: 'text-transform',
                allowedValues: ['uppercase'],
                toolbarValue: 'uppercase',
            ),
        );
    };

    Event::on(TiptapExtensions::class, TiptapExtensions::EVENT_REGISTER_EXTENSIONS, $handler);

    try {
        $service = new TiptapExtensions();
        $config = $service->getClientConfig();

        expect($config['extensionIds'])->toBe(['tests/annotation'])
            ->and($config['textStyles'][0]['attribute'])->toBe('textTransform');

        Formie::$plugin->set('tiptapExtensions', $service);
        $richText = RichText::fromHtml('<p><span style="text-transform: uppercase">NASA</span></p>');

        expect($richText->getSchema()[0]['content'][0]['marks'][0]['attrs']['textTransform'])->toBe('uppercase')
            ->and($richText->toHtml())->toContain('text-transform: uppercase');
    } finally {
        Event::off(TiptapExtensions::class, TiptapExtensions::EVENT_REGISTER_EXTENSIONS, $handler);
    }
});

it('rejects unsafe declarative TextStyle definitions at construction', function(): void {
    new TextStyleDefinition(
        id: 'tests-unsafe-color',
        label: 'Unsafe color',
        attribute: 'color',
        cssProperty: 'color',
        allowedValues: ['red'],
        toolbarValue: 'red',
    );
})->throws(InvalidArgumentException::class);

it('does not allow declarative styles to replace built-in TextStyle attributes', function(): void {
    new TextStyleDefinition(
        id: 'tests-small-caps',
        label: 'Small caps',
        attribute: 'fontVariantCaps',
        cssProperty: 'font-variant-caps',
        allowedValues: ['small-caps'],
        toolbarValue: 'small-caps',
    );
})->throws(InvalidArgumentException::class);
