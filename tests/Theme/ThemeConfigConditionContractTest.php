<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

it('resolves form theme config conditions using the context key alias', function (): void {
    $form = formie()
        ->form(['title' => 'Theme Context Alias'])
        ->singleLineTextField('fullName')
        ->create();

    $page = $form->getPages()[0];
    $page->getPageSettings()->buttonsPosition = 'right-save-left';

    $form->setThemeConfig([
        'buttonWrapper' => [
            'attributes' => [
                'class' => [
                    'flex -mx-2',
                    [
                        'if' => ['context' => 'page.buttonsPosition', 'equals' => 'right-save-left'],
                        'then' => 'justify-start flex-row-reverse',
                    ],
                ],
            ],
        ],
    ]);

    $tag = Formie::$plugin->getThemeConfigService()->applyFormTagConfig(
        $form,
        'buttonWrapper',
        SlotTag::make('div'),
        RenderContext::from([
            'form' => $form,
            'page' => $page,
            'currentPage' => $page,
        ]),
    );

    expect($tag?->attributes['class'] ?? [])->toContain('flex -mx-2')
        ->and($tag?->attributes['class'] ?? [])->toContain('justify-start flex-row-reverse');
});

it('resolves field theme config conditions using field layout in context', function (): void {
    $form = formie()
        ->form(['title' => 'Theme Field Layout Context'])
        ->checkboxesField('choices', [
            'layout' => 'horizontal',
            'options' => [
                ['label' => 'One', 'value' => 'one'],
                ['label' => 'Two', 'value' => 'two'],
            ],
        ])
        ->create();

    $field = $form->getFieldByHandle('choices');
    $page = $form->getPages()[0];

    $form->setThemeConfig([
        'checkboxes' => [
            'fieldOption' => [
                'attributes' => [
                    'class' => [
                        [
                            'if' => ['context' => 'field.layout', 'equals' => 'horizontal'],
                            'then' => 'inline-block mr-4',
                            'else' => 'flex items-start mb-2',
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $tag = Formie::$plugin->getThemeConfigService()->applyFieldTagConfig(
        $field,
        $form,
        'fieldOption',
        SlotTag::make('div'),
        RenderContext::from([
            'form' => $form,
            'field' => $field,
            'page' => $page,
            'currentPage' => $page,
        ]),
    );

    expect($tag?->attributes['class'] ?? [])->toContain('inline-block mr-4')
        ->and($tag?->attributes['class'] ?? [])->not->toContain('flex items-start mb-2');
});
