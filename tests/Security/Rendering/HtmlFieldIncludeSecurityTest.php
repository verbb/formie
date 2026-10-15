<?php

declare(strict_types=1);

use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\FileHelper;
use craft\web\View;
use verbb\formie\Formie;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Html;
use verbb\formie\models\PdfTemplate;
use verbb\formie\web\twig\HtmlTemplateLoader;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Sandbox\SecurityError;

beforeEach(function (): void {
    \verbb\formie\Formie::$plugin->getSettings()->allowHiddenFieldTemplates = true;
    \verbb\formie\Formie::$plugin->getSettings()->allowHtmlFieldTwig = true;
    \verbb\formie\Formie::$plugin->getSettings()->allowHtmlFieldTemplateIncludes = true;
    \verbb\formie\Formie::$plugin->getSettings()->requireHtmlPurification = false;
});

beforeEach(function (): void {
    $this->htmlIncludePrefix = 'formie-html-test-' . bin2hex(random_bytes(6));
    $this->htmlIncludeDirectory = Craft::$app->getPath()->getSiteTemplatesPath() . '/' . $this->htmlIncludePrefix;
    FileHelper::createDirectory($this->htmlIncludeDirectory);
    file_put_contents($this->htmlIncludeDirectory . '/fragment.twig', '<p>{{ label }}</p>');
});

afterEach(function (): void {
    FileHelper::removeDirectory($this->htmlIncludeDirectory);
});

it('loads nested site includes with context and escaping from a CP render', function (): void {
    $view = Craft::$app->getView();
    $originalMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);
    file_put_contents($this->htmlIncludeDirectory . '/outer.twig', '{% include "' . $this->htmlIncludePrefix . '/fragment" with {label: label} only %}');
    $field = new Html([
        'allowTwig' => true,
        'purifyContent' => false,
        'htmlContent' => '{% include "' . $this->htmlIncludePrefix . '/outer" %}',
    ]);

    try {
        expect($field->getRenderedHtmlContent(['label' => '<b>visitor</b>']))->toBe('<p>&lt;b&gt;visitor&lt;/b&gt;</p>')
            ->and($view->getTemplateMode())->toBe(View::TEMPLATE_MODE_CP);
        $field->htmlContent = '{{ include("' . $this->htmlIncludePrefix . '/fragment", {label: "function"}) }}';
        expect($field->getRenderedHtmlContent())->toBe('<p>function</p>');
    } finally {
        $view->setTemplateMode($originalMode);
    }
})->group('security');

it('does not evaluate includes without the HTML allow Twig opt-in', function (): void {
    $template = '{% include "' . $this->htmlIncludePrefix . '/fragment" %}';
    $field = new Html(['htmlContent' => $template, 'purifyContent' => false]);
    expect($field->getRenderedHtmlContent())->toBe($template);
})->group('security');

it('keeps included templates sandboxed and restores CP mode after failures', function (string $source): void {
    file_put_contents($this->htmlIncludeDirectory . '/unsafe.twig', $source);
    $view = Craft::$app->getView();
    $originalMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);
    $field = new Html(['allowTwig' => true, 'htmlContent' => '{% include "' . $this->htmlIncludePrefix . '/unsafe" %}']);

    try {
        expect(fn() => $field->getRenderedHtmlContent())->toThrow(Error::class);
        expect($view->getTemplateMode())->toBe(View::TEMPLATE_MODE_CP);
    } finally {
        $view->setTemplateMode($originalMode);
    }
})->with([
    'Craft globals' => '{{ craft.app.cache.cachePath }}',
    'source reads' => '{{ source("fragment.twig") }}',
    'callable gadget' => '{{ collect(["safe"]).map("strlen").first() }}',
])->group('security');

it('purifies included HTML after sandbox rendering', function (): void {
    file_put_contents($this->htmlIncludeDirectory . '/unsafe-markup.twig', '<script>alert(1)</script><p>Included</p>');
    $field = new Html(['allowTwig' => true, 'htmlContent' => '{% include "' . $this->htmlIncludePrefix . '/unsafe-markup" %}']);
    expect($field->getRenderedHtmlContent())->toContain('<p>Included</p>')->not->toContain('<script');
})->group('security');

it('does not grant includes to shared strings Hidden defaults or PDF filenames', function (): void {
    $templates = Formie::$plugin->getTemplates();
    $template = '{% include "' . $this->htmlIncludePrefix . '/fragment" with {label: "allowed"} %}';
    $field = new Html(['allowTwig' => true, 'htmlContent' => $template]);
    $form = formie()->form()->hiddenField('computed', [
        'valueSource' => Hidden::DEFAULT_OPTION_TEMPLATE,
        'defaultTemplate' => $template,
    ])->create();
    $submission = formie()->submission($form)->save();

    foreach ([false, true] as $renderHtml) {
        if ($renderHtml) {
            expect($field->getRenderedHtmlContent())->toBe('<p>allowed</p>');
        }
        expect(fn() => $templates->renderSandboxedString($template))->toThrow(SecurityError::class);
        expect(fn() => $templates->renderSandboxedString('{{ include("' . $this->htmlIncludePrefix . '/fragment") }}'))->toThrow(SecurityError::class);
        expect(fn() => $templates->renderSandboxedObjectTemplate($template, $submission, autoescape: false))->toThrow(SecurityError::class);
        expect($form->getFieldByHandle('computed')->getInitialValue($form))->toBe('');
        expect(fn() => Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfFilename($submission, new PdfTemplate(['filenameFormat' => $template])))
            ->toThrow(SecurityError::class);
    }
})->group('security');

it('rejects template traversal absolute paths and CP templates in HTML includes', function (string $name): void {
    $field = new Html(['allowTwig' => true, 'htmlContent' => '{% include name %}']);
    expect(fn() => $field->getRenderedHtmlContent(['name' => $name]))->toThrow(LoaderError::class);
})->with(['../.env', 'nested/../../.env', '/etc/passwd', 'php://filter/resource=.env', 'formie/settings/index'])->group('security');

it('supports registered site roots without exposing adjacent files or symlink targets', function (): void {
    $external = Craft::$app->getPath()->getTempPath() . '/' . $this->htmlIncludePrefix;
    FileHelper::createDirectory($external . '/root');
    file_put_contents($external . '/root/partial.twig', 'registered template');
    file_put_contents($external . '/outside.twig', 'outside secret');
    symlink($external . '/outside.twig', $this->htmlIncludeDirectory . '/outside.twig');
    $view = new View();
    $view->on(View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS, static function(RegisterTemplateRootsEvent $event) use ($external): void {
        $event->roots['html-test'] = $external . '/root';
    });
    $loader = new HtmlTemplateLoader($view);

    try {
        expect($loader->getSourceContext('html-test/partial')->getCode())->toBe('registered template');
        expect(fn() => $loader->getSourceContext('html-test/../outside.twig'))->toThrow(LoaderError::class);
        expect(fn() => $loader->getSourceContext($this->htmlIncludePrefix . '/outside.twig'))->toThrow(LoaderError::class);
    } finally {
        FileHelper::removeDirectory($external);
    }
})->group('security');
