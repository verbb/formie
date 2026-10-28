<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\helpers\Html;
use verbb\formie\theme\context\RenderContext;

it('embeds resolved message attributes with the same values as server-rendered messages', function (string $theme, bool $flat): void {
    $form = formie()->form(['title' => 'Browser message attributes'])->singleLineTextField('name')->create();
    $attributes = [
        'class' => ['alert', 'alert--icon'],
        'title' => 'Quotes " & <tags> café',
        'data' => ['count' => 0, 'details' => ['label' => 'A & B']],
        'aria' => ['label' => 'Submission result'],
        'style' => ['border-color' => 'red'],
        'hidden' => false,
        'inert' => true,
    ];
    $themeConfig = [];

    foreach (['messageError' => 'error', 'messageSuccess' => 'success'] as $key => $state) {
        $values = $attributes + ['data-ui-theme' => $state];
        $themeConfig[$key] = $flat ? $values : ['attributes' => $values];
    }

    WebRequestTestHelper::withWebRequestContext(function () use ($form, $theme, $themeConfig): void {
        $rendering = Formie::$plugin->getRendering();
        $rendering->pushRenderFrame($form, ['theme' => $theme, 'themeConfig' => $themeConfig]);

        try {
            $context = RenderContext::from(['form' => $form]);
            $tag = $form->renderSlotTag('form', $context);
            $html = Html::tag('form', '', $tag->attributesForRender());
            $document = new DOMDocument();
            @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $encoded = $document->getElementsByTagName('form')->item(0)->getAttribute('data-formie-message-attributes');
            expect($encoded)->not->toBe('');
            $map = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

            foreach (['messageError' => 'error', 'messageSuccess' => 'success'] as $key => $state) {
                $message = $form->renderSlotTag($key, $context);
                @$document->loadHTML('<?xml encoding="utf-8" ?>' . Html::tag('div', '', $message->attributesForRender()));
                $node = $document->getElementsByTagName('div')->item(0);

                foreach ($map[$key] as $name => $value) {
                    expect($node->getAttribute($name))->toBe($value === true ? '' : $value);
                }

                expect($map[$key])->not->toHaveKeys(['class', 'hidden'])
                    ->and($map[$key]['data-ui-theme'])->toBe($state)
                    ->and($map[$key]['title'])->toBe('Quotes " & <tags> café')
                    ->and($map[$key]['data-count'])->toBe('0')
                    ->and(json_decode($map[$key]['data-details'], true))->toBe(['label' => 'A & B'])
                    ->and($map[$key]['aria-label'])->toBe('Submission result');
            }
        } finally {
            $rendering->popRenderFrame();
        }
    });
})->with(['formie', 'none'])->with([false, true]);
