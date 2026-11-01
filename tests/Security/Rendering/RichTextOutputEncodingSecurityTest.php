<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\integrations\messaging\Slack;
use yii\i18n\MessageSource;

class RichTextOutputEncodingMessageSource extends MessageSource
{
    public array $messages = [];

    protected function loadMessages($category, $language): array
    {
        return $this->messages;
    }
}

function renderRichTextEncodingTwig(string $template, array $variables): string
{
    return Tests\Support\WebRequestTestHelper::withWebRequestContext(function() use ($template, $variables): string {
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        try {
            return $view->renderString($template, $variables);
        } finally {
            $view->setTemplateMode($oldTemplateMode);
        }
    });
}

function richTextEncodingDocument(string $html): DOMDocument
{
    $document = new DOMDocument();
    $document->loadHTML('<?xml encoding="UTF-8"><root>' . $html . '</root>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);

    return $document;
}

function richTextEncodingElementNames(string $html): array
{
    $names = [];

    foreach (richTextEncodingDocument($html)->getElementsByTagName('*') as $element) {
        if ($element->nodeName !== 'root') {
            $names[] = $element->nodeName;
        }
    }

    return $names;
}

function richTextEncodingText(string $html): string
{
    return richTextEncodingDocument($html)->documentElement->textContent;
}

function richTextEncodingInstructions(string $html, string $handle): DOMElement
{
    $document = new DOMDocument();
    $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR);

    foreach ($document->getElementsByTagName('*') as $element) {
        if ($element->hasAttribute('data-formie-instructions') && str_ends_with($element->getAttribute('id'), '-fields-' . $handle . '-instructions')) {
            return $element;
        }
    }

    throw new RuntimeException('Instructions were not rendered for ' . $handle . '.');
}

function richTextEncodingInnerHtml(DOMElement $element): string
{
    $html = '';

    foreach ($element->childNodes as $child) {
        $html .= $element->ownerDocument->saveHTML($child);
    }

    return $html;
}

function richTextEncodingSuccessMessage(string $message, string $value): string
{
    $form = formie()
        ->form(['title' => 'Rich Text Success Message Encoding'])
        ->singleLineTextField('name')
        ->create();

    $token = References::field((string)$form->getFieldByHandle('name')?->reference);
    $form->setSettings(['successMessage' => str_replace('{name}', $token, $message)]);
    $submission = formie()->submission($form)->with(['name' => $value])->save();

    return $form->settings->getSuccessMessage($submission);
}

it('keeps the escaped Twig instructions example from the advisory as text', function (): void {
    $form = formie()
        ->form(['title' => 'Advisory Instructions'])
        ->singleLineTextField('name')
        ->create();

    $html = renderRichTextEncodingTwig(<<<'TWIG'
        {% do form.setFieldSettings('name', {
            instructions: '<img src=x onerror=alert(1)><script>alert(2)</script>'|e,
        }) %}
        {{ craft.formie.renderForm(form) }}
        TWIG, ['form' => $form]);

    $instructions = richTextEncodingInstructions($html, 'name');

    expect($instructions->getElementsByTagName('img')->length)->toBe(0)
        ->and($instructions->getElementsByTagName('script')->length)->toBe(0)
        ->and($instructions->textContent)->toBe('<img src=x onerror=alert(1)><script>alert(2)</script>')
        ->and($html)->not->toContain('<img src=x onerror=alert(1)>')
        ->and($html)->not->toContain('<script>alert(2)</script>');
})->group('security');

it('keeps entity text supplied to runtime instructions literal', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Entity Instructions'])
        ->singleLineTextField('name')
        ->create();

    // Content authors can type entities directly, without template escaping.
    $html = renderRichTextEncodingTwig(<<<'TWIG'
        {% do form.setFieldSettings('name', { instructions: listing }) %}
        {{ craft.formie.renderForm(form) }}
        TWIG, ['form' => $form, 'listing' => 'Call us &lt;img src=x onerror=alert(1)&gt; &lt;script&gt;alert(2)&lt;/script&gt;']);

    $instructions = richTextEncodingInstructions($html, 'name');

    expect($instructions->getElementsByTagName('img')->length)->toBe(0)
        ->and($instructions->getElementsByTagName('script')->length)->toBe(0)
        ->and($instructions->textContent)->toBe('Call us <img src=x onerror=alert(1)> <script>alert(2)</script>');
})->group('security');

it('keeps markup typed into saved instructions escaped', function (): void {
    $form = formie()
        ->form(['title' => 'Saved Instructions Encoding'])
        ->singleLineTextField('name', [
            'instructions' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Typed <img src=x onerror=alert(1)> & "quoted"']],
            ]],
        ])
        ->create();

    $reloaded = Form::find()->id($form->id)->one();
    $instructionsHtml = (string)$reloaded?->getFieldByHandle('name')?->getInstructionsHtml();

    expect(richTextEncodingElementNames($instructionsHtml))->toBe([])
        ->and(richTextEncodingText($instructionsHtml))->toBe('Typed <img src=x onerror=alert(1)> & "quoted"');
})->group('security');

it('preserves formatting and special characters in runtime instructions', function (): void {
    $form = formie()
        ->form(['title' => 'Runtime Instructions Formatting'])
        ->singleLineTextField('name')
        ->create();

    $html = renderRichTextEncodingTwig(<<<'TWIG'
        {% do form.setFieldSettings('name', { instructions: "Fish & chips — **today** it's \"fresh\" café" }) %}
        {{ craft.formie.renderForm(form) }}
        TWIG, ['form' => $form]);

    $instructions = richTextEncodingInstructions($html, 'name');

    expect($instructions->getElementsByTagName('strong')->item(0)?->textContent)->toBe('today')
        ->and($instructions->textContent)->toBe("Fish & chips — today it's \"fresh\" café")
        ->and(richTextEncodingInnerHtml($instructions))->not->toContain('&amp;amp;');
})->group('security');

it('keeps submitted values in success messages as text', function (): void {
    $value = 'X<img src=x onerror="document.body.dataset.xss=1"><a href="https://example.org/phish">Click</a>';
    $message = richTextEncodingSuccessMessage('Thanks {name}!', $value);
    $sanitized = StringHelper::sanitizeMessageHtml($message);

    expect(richTextEncodingElementNames($message))->toBe(['p'])
        ->and(richTextEncodingText($message))->toBe('Thanks ' . $value . '!')
        ->and(richTextEncodingElementNames($sanitized))->toBe(['p'])
        ->and(richTextEncodingText($sanitized))->toBe('Thanks ' . $value . '!');
})->group('security');

it('preserves special characters in submitted success message values', function (): void {
    $value = "Tom & Jerry's \"café\" — 3 < 5";
    $message = richTextEncodingSuccessMessage('<p>Thanks <strong>{name}</strong> & welcome!</p>', $value);

    expect(richTextEncodingElementNames($message))->toBe(['p', 'strong'])
        ->and(richTextEncodingText($message))->toBe('Thanks ' . $value . ' & welcome!')
        ->and($message)->not->toContain('&amp;amp;');
})->group('security');

it('keeps submitted values in content blocks as text', function (): void {
    $form = formie()
        ->form(['title' => 'Content Block Encoding'])
        ->singleLineTextField('name')
        ->contentField('intro')
        ->create();

    $token = References::field((string)$form->getFieldByHandle('name')?->reference);
    $content = $form->getFieldByHandle('intro');
    $content->content = verbb\formie\models\RichText::from('<p>Hello ' . $token . '</p>');
    $submission = formie()->submission($form)->with(['name' => '<img src=x onerror=alert(1)> & co'])->save();
    $html = $content->getRenderedContentBlock($form, null, $submission);

    expect(richTextEncodingElementNames($html))->toBe(['p'])
        ->and(richTextEncodingText($html))->toBe('Hello <img src=x onerror=alert(1)> & co');
})->group('security');

it('matches existing Agree description translations without decoding untranslated output', function (): void {
    $i18n = Craft::$app->getI18n();
    $originalSource = $i18n->translations['formie'] ?? null;
    $originalLanguage = Craft::$app->language;
    $source = new RichTextOutputEncodingMessageSource(['forceTranslation' => true, 'sourceLanguage' => 'en-US']);
    $source->messages = ["I agree to Tom & Jerry's <strong>terms</strong>" => "J'accepte les <strong>conditions</strong> de Tom & Jerry"];
    $i18n->translations['formie'] = $source;
    Craft::$app->language = 'fr-FR';

    try {
        $agree = formie()
            ->form(['title' => 'Agree Translation Encoding'])
            ->agreeField('terms', [
                'description' => [[
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => "I agree to Tom & Jerry's "],
                        ['type' => 'text', 'text' => 'terms', 'marks' => [['type' => 'bold']]],
                    ],
                ]],
            ])
            ->agreeField('consent', [
                'description' => [[
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'Typed <script>alert(1)</script> & more']],
                ]],
            ])
            ->create();

        $translated = (string)$agree->getFieldByHandle('terms')?->getDescriptionHtml();
        $untranslated = (string)$agree->getFieldByHandle('consent')?->getDescriptionHtml();
    } finally {
        $i18n->translations['formie'] = $originalSource;
        Craft::$app->language = $originalLanguage;
    }

    expect($translated)->toBe("J'accepte les <strong>conditions</strong> de Tom & Jerry")
        ->and(richTextEncodingElementNames($untranslated))->toBe([])
        ->and(richTextEncodingText($untranslated))->toBe('Typed <script>alert(1)</script> & more');
})->group('security');

it('keeps submitted markup out of integration message formatting', function (): void {
    $form = formie()
        ->form(['title' => 'Integration Message Encoding'])
        ->singleLineTextField('name')
        ->create();

    $token = References::field((string)$form->getFieldByHandle('name')?->reference);
    $submission = formie()->submission($form)->with(['name' => '<b>Ann</b> & "co"'])->save();
    $integration = new Slack(['message' => '<p>Tom & Jerry: <strong>' . $token . '</strong></p>']);
    $render = new ReflectionMethod($integration, '_renderMessage');
    $message = (string)$render->invoke($integration, $submission);

    // The Markdown converter escapes text control characters, as Slack's mrkdwn requires.
    expect($message)->toBe('Tom &amp; Jerry: *&lt;b&gt;Ann&lt;/b&gt; &amp; "co"*');
})->group('security');
