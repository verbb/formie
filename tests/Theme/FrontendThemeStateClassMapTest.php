<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\theme\context\RenderContext;

it('includes tab link state classes in the frontend theme class map', function (): void {
    $form = formie()
        ->form(['title' => 'Tab Link Theme Map'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $form->setThemeConfig([
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
    ]);

    $map = Formie::$plugin->getThemeConfigService()->buildFrontendClassMap($form);

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

    $form->setThemeConfig([
        'pageTabLinkActive' => [
            'attributes' => [
                'class' => ['alias-tab-link-active'],
            ],
        ],
    ]);

    $map = Formie::$plugin->getThemeConfigService()->buildFrontendClassMap($form);

    expect($map['tabLinkCurrent'] ?? [])->toContain('alias-tab-link-active');
});

it('applies tab link state classes during server render', function (): void {
    $form = formie()
        ->form(['title' => 'Tab Link Server Render'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $form->setThemeConfig([
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
    ]);

    $pages = $form->getPages();
    $currentPage = $pages[0];
    $otherPage = $pages[1];

    $currentTag = Formie::$plugin->getFormSlotRegistry()->resolve('pageTabLink', RenderContext::from([
        'form' => $form,
        'targetPage' => $currentPage,
        'currentPage' => $currentPage,
    ]));

    $inactiveTag = Formie::$plugin->getFormSlotRegistry()->resolve('pageTabLink', RenderContext::from([
        'form' => $form,
        'targetPage' => $otherPage,
        'currentPage' => $currentPage,
    ]));

    expect($currentTag?->attributes['class'] ?? [])->toContain('tab-link-active')
        ->and($currentTag?->attributes['class'] ?? [])->not->toContain('tab-link-inactive')
        ->and($inactiveTag?->attributes['class'] ?? [])->toContain('tab-link-inactive')
        ->and($inactiveTag?->attributes['class'] ?? [])->not->toContain('tab-link-active');
});

it('embeds tab link state classes on data-formie-theme', function (): void {
    $form = formie()
        ->form(['title' => 'Tab Link Theme Embed'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $form->setThemeConfig([
        'tabLinkCurrent' => [
            'attributes' => [
                'class' => ['tab-link-active'],
            ],
        ],
    ]);

    $tag = Formie::$plugin->getFormSlotRegistry()->resolve('form', RenderContext::from([
        'form' => $form,
    ]));

    $encodedTheme = $tag?->coreAttributes['data']['formie-theme'] ?? null;

    expect($encodedTheme)
        ->toBeString()
        ->and($encodedTheme)->toContain('tabLinkCurrent')
        ->and($encodedTheme)->toContain('tab-link-active');
});
