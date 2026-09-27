<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\theme\context\RenderContext;

function withBrowserThemeFrame(Form $form, array $themeConfig, callable $callback): mixed
{
    Formie::$plugin->getRendering()->pushRenderFrame($form, ['themeConfig' => $themeConfig]);

    try {
        return $callback();
    } finally {
        Formie::$plugin->getRendering()->popRenderFrame();
    }
}

it('includes tab link state classes in the frontend theme class map', function (): void {
    $form = formie()
        ->form(['title' => 'Tab Link Theme Map'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $map = withBrowserThemeFrame($form, [
        'tabLinkCurrent' => [
            'attributes' => [
                'class' => ['tab-link-active'],
            ],
        ],
        'tabLinkInactive' => [
            'attributes' => [
                'class' => ['tab-link-inactive'],
            ],
        ],
    ], fn(): array => Formie::$plugin->getThemeConfigService()->buildBrowserClassMap($form));

    expect($map['tabLinkCurrent'] ?? [])->toContain('tab-link-active')
        ->and($map['tabLinkInactive'] ?? [])->toContain('tab-link-inactive');
});

it('resolves pageTabLinkActive as an alias for tabLinkCurrent', function (): void {
    $form = formie()
        ->form(['title' => 'Tab Link Alias Theme Map'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $map = withBrowserThemeFrame($form, [
        'pageTabLinkActive' => [
            'attributes' => [
                'class' => ['alias-tab-link-active'],
            ],
        ],
    ], fn(): array => Formie::$plugin->getThemeConfigService()->buildBrowserClassMap($form));

    expect($map['tabLinkCurrent'] ?? [])->toContain('alias-tab-link-active');
});

it('applies tab link state classes during server render', function (): void {
    $form = formie()
        ->form(['title' => 'Tab Link Server Render'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $themeConfig = [
        'tabLinkCurrent' => [
            'attributes' => [
                'class' => ['tab-link-active'],
            ],
        ],
        'tabLinkInactive' => [
            'attributes' => [
                'class' => ['tab-link-inactive'],
            ],
        ],
    ];

    $pages = $form->getPages();
    $currentPage = $pages[0];
    $otherPage = $pages[1];

    [$currentTag, $inactiveTag] = withBrowserThemeFrame($form, $themeConfig, function() use ($form, $currentPage, $otherPage): array {
        return [
            Formie::$plugin->getFormSlotRegistry()->resolve('pageTabLink', RenderContext::from([
                'form' => $form,
                'targetPage' => $currentPage,
                'currentPage' => $currentPage,
            ])),
            Formie::$plugin->getFormSlotRegistry()->resolve('pageTabLink', RenderContext::from([
                'form' => $form,
                'targetPage' => $otherPage,
                'currentPage' => $currentPage,
            ])),
        ];
    });

    expect($currentTag?->attributes['class'] ?? [])->toContain('tab-link-active')
        ->and($currentTag?->attributes['class'] ?? [])->not->toContain('tab-link-inactive')
        ->and($inactiveTag?->attributes['class'] ?? [])->toContain('tab-link-inactive')
        ->and($inactiveTag?->attributes['class'] ?? [])->not->toContain('tab-link-active');
});

it('embeds tab link state classes on data-formie-theme-classes', function (): void {
    $form = formie()
        ->form(['title' => 'Tab Link Theme Embed'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $tag = withBrowserThemeFrame($form, [
        'tabLinkCurrent' => [
            'attributes' => [
                'class' => ['tab-link-active'],
            ],
        ],
    ], fn() => \Tests\Support\WebRequestTestHelper::withWebRequestContext(fn() => Formie::$plugin->getFormSlotRegistry()->resolve('form', RenderContext::from([
            'form' => $form,
        ]))));

    $encodedTheme = $tag?->coreAttributes['data']['formie-theme-classes'] ?? null;

    expect($encodedTheme)
        ->toBeString()
        ->and($encodedTheme)->toContain('tabLinkCurrent')
        ->and($encodedTheme)->toContain('tab-link-active');
});
