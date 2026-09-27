<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\SlotTag;
use verbb\formie\models\SubmissionProgress as ProgressState;
use verbb\formie\services\Rendering;
use verbb\formie\services\SubmissionProgress;
use verbb\formie\theme\context\RenderContext;

use yii\base\InvalidArgumentException;

it('isolates nested renders of one Form with different immutable themes', function (): void {
    $form = formie()->form(['title' => 'Theme frame isolation'])->singleLineTextField('name')->create();
    [$first, $second, $restored] = WebRequestTestHelper::withWebRequestContext(function () use ($form): array {
        $rendering = Formie::$plugin->getRendering();
        $context = RenderContext::from(['form' => $form]);
        $rendering->pushRenderFrame($form, ['themeConfig' => ['form' => ['class' => 'theme-a']]]);

        try {
            $first = $form->renderSlotTag('form', $context);
            $rendering->pushRenderFrame($form, ['themeConfig' => ['form' => ['class' => 'theme-b']]]);

            try {
                $second = $form->renderSlotTag('form', $context);
            } finally {
                $rendering->popRenderFrame();
            }

            $restored = $form->renderSlotTag('form', $context);
        } finally {
            $rendering->popRenderFrame();
        }

        return [$first, $second, $restored];
    });

    expect($first?->attributes['class'] ?? [])->toContain('theme-a')
        ->and($first?->attributes['class'] ?? [])->not->toContain('theme-b')
        ->and($second?->attributes['class'] ?? [])->toContain('theme-b')
        ->and($restored?->attributes['class'] ?? [])->toContain('theme-a');
});

it('applies core attributes after theme and instance while retaining the trusted final override', function (): void {
    $tag = SlotTag::make('form')
        ->theme(['method' => 'delete', 'data' => ['formie' => false], 'class' => 'theme'])
        ->instanceAttributes(['method' => 'put', 'data' => ['formie' => false], 'class' => 'instance'])
        ->core(['method' => 'post', 'data' => ['formie' => true]]);

    expect($tag->attributesForRender(['method' => 'patch', 'data' => ['formie' => false]])['method'])->toBe('post')
        ->and($tag->attributesForRender()['data']['formie'])->toBeTrue();

    $before = $tag->attributes;
    $tag->attributes['method'] = 'get';
    unset($tag->attributes['data']);
    $tag->captureTrustedEventResult($before);

    expect($tag->attributesForRender(['method' => 'patch'])['method'])->toBe('get')
        ->and($tag->attributesForRender())->not->toHaveKey('data');
});

it('keeps Formie 3 reset and flat grammar and escapes injected text by default', function (): void {
    $form = formie()->form(['title' => 'Theme grammar'])->singleLineTextField('name')->create();
    $tag = WebRequestTestHelper::withWebRequestContext(function () use ($form): ?SlotTag {
        Formie::$plugin->getRendering()->pushRenderFrame($form, [
            'themeConfig' => [
                'form' => [
                    'resetClass' => true,
                    'class' => 'alpha beta',
                    'data-flat' => 'yes',
                    'prepend' => ['tag' => 'span', 'text' => '<icon>'],
                    'append' => ['tag' => 'span', 'text' => 'tail'],
                ],
            ],
        ]);

        try {
            return $form->renderSlotTag('form', RenderContext::from(['form' => $form]));
        } finally {
            Formie::$plugin->getRendering()->popRenderFrame();
        }
    });

    expect($tag?->attributes['class'] ?? [])->toBe(['alpha', 'beta'])
        ->and($tag?->attributes['data']['flat'] ?? null)->toBe('yes')
        ->and($tag?->composeContent('body'))->toContain('&lt;icon&gt;', 'body', 'tail')
        ->and($tag?->composeContent('body'))->not->toContain('<icon>');
});

it('allows trusted server-side raw HTML and the final PHP event escape hatch', function (): void {
    $form = formie()->form(['title' => 'Trusted theme'])->singleLineTextField('name')->create();
    $handler = static function ($event): void {
        if ($event->key === 'form' && $event->tag) {
            $event->tag->attributes['method'] = 'dialog';
            unset($event->tag->attributes['data']);
        }
    };
    $form->on(Form::EVENT_MODIFY_SLOT_TAG, $handler);

    try {
        $tag = WebRequestTestHelper::withWebRequestContext(function () use ($form): ?SlotTag {
            Formie::$plugin->getRendering()->pushRenderFrame($form, [
                'themeConfig' => [
                    'form' => [
                        'prepend' => ['tag' => 'span', 'html' => '<svg aria-hidden="true"></svg>'],
                    ],
                ],
            ]);

            try {
                return $form->renderSlotTag('form', RenderContext::from(['form' => $form]));
            } finally {
                Formie::$plugin->getRendering()->popRenderFrame();
            }
        });
    } finally {
        $form->off(Form::EVENT_MODIFY_SLOT_TAG, $handler);
    }

    expect($tag?->composeContent('body'))->toContain('<svg aria-hidden="true"></svg>')
        ->and($tag?->attributesForRender()['method'])->toBe('dialog')
        ->and($tag?->attributesForRender())->not->toHaveKey('data');
});

it('rejects executable and unbounded transported theme config', function (array $config, string $message): void {
    $form = formie()->form(['title' => 'Transported theme'])->singleLineTextField('name')->create();

    expect(fn() => Formie::$plugin->getThemeConfigService()->resolve($form, ['themeConfig' => $config], true))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'event handler' => [['form' => ['attributes' => ['onclick' => 'alert(1)']]], 'event-handler'],
    'flat event handler' => [['form' => ['onfocus' => 'alert(1)']], 'event-handler'],
    'transported tag' => [['form' => ['tag' => 'script']], 'Theme tags'],
    'raw html' => [['form' => ['append' => ['html' => '<b>unsafe</b>']]], 'Raw HTML'],
    'invalid css variable' => [['form' => ['cssVars' => ['color' => 'red']]], 'must begin'],
    'unknown condition path' => [['form' => ['class' => ['if' => ['path' => 'form.deleteEverything'], 'then' => 'x']]], 'Unknown theme condition'],
    'unknown condition property' => [['form' => ['class' => ['if' => ['context' => 'form.handle', 'execute' => 'delete'], 'then' => 'x']]], 'Unknown theme condition property'],
    'method expression' => [['form' => ['class' => 'form.deleteEverything()']], 'method expressions'],
    'Twig expression' => [['form' => ['class' => '{{ craft.app }}']], 'Twig'],
]);

it('keeps none functional while omitting visual classes and theme styles', function (): void {
    $form = formie()->form(['title' => 'None theme'])->singleLineTextField('name')->create();

    [$tag, $assets] = WebRequestTestHelper::withWebRequestContext(function () use ($form): array {
        Formie::$plugin->getRendering()->pushRenderFrame($form, ['theme' => 'none']);

        try {
            $tag = $form->renderSlotTag('form', RenderContext::from(['form' => $form]));
        } finally {
            Formie::$plugin->getRendering()->popRenderFrame();
        }

        return [$tag, (string)Formie::$plugin->getRendering()->formAssets($form, [
            'theme' => 'none',
            'includeJs' => false,
        ])];
    });

    expect($tag?->attributes['method'] ?? null)->toBe('post')
        ->and($tag?->attributes['data']['formie'] ?? null)->toBeTrue()
        ->and($tag?->attributes['class'] ?? [])->not->toContain('formie-form')
        ->and($assets)->toContain('formie-base.css')
        ->and($assets)->not->toContain('formie-theme.css');
});

it('keeps the PHP and generated TypeScript browser theme manifests in sync', function (): void {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2) . '/src/config/browser-theme-state.json'), true, 512, JSON_THROW_ON_ERROR);
    $typescript = file_get_contents(dirname(__DIR__, 2) . '/packages/formie-browser/src/js/theme/browser-theme-state.generated.ts');

    foreach (array_keys($manifest) as $key) {
        expect($typescript)->toContain("{$key}:");
    }
});

it('does not re-render form markup while resolving manual assets', function (): void {
    $form = formie()->form(['title' => 'Asset-only render'])->singleLineTextField('name')->create();
    $renderCount = 0;
    $rendering = Formie::$plugin->getRendering();
    $handler = static function () use (&$renderCount): void {
        $renderCount++;
    };
    $rendering->on(Rendering::EVENT_MODIFY_RENDER_FORM, $handler);

    try {
        WebRequestTestHelper::withWebRequestContext(static fn() => $rendering->formAssets($form));
    } finally {
        $rendering->off(Rendering::EVENT_MODIFY_RENDER_FORM, $handler);
    }

    expect($renderCount)->toBe(0);
});

it('hydrates a missing browser submission only once per Form instance', function (): void {
    $form = formie()
        ->form(['title' => 'Submission miss cache'])
        ->settings(['automaticSubmissionState' => true])
        ->singleLineTextField('name')
        ->create();

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $original = Formie::$plugin->getSubmissionProgress();
        $probe = new class extends SubmissionProgress {
            public int $lookups = 0;

            public function getProgressState(Form $form): ?ProgressState
            {
                $this->lookups++;

                return null;
            }
        };
        Formie::$plugin->set('submissionProgress', $probe);

        try {
            for ($index = 0; $index < 25; $index++) {
                $form->getCurrentSubmission();
                $form->getCurrentPage();
            }

            expect($probe->lookups)->toBe(1);
        } finally {
            Formie::$plugin->set('submissionProgress', $original);
        }
    });
});
