<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\events\PdfEvent;
use verbb\formie\fields\MultiLineText;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\integrations\messaging\Slack;
use verbb\formie\models\Notification;
use verbb\formie\models\RichText;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceOutputContext;
use verbb\formie\references\ReferenceResolver;
use verbb\formie\services\PdfTemplates;
use verbb\formie\variables\Formie as FormieVariable;

use yii\base\Event;

const RICH_TEXT_REFERENCE_VALUE = '<p><strong>Bold</strong> &amp; more</p><p>Second</p>';
const RICH_TEXT_REFERENCE_QUOTES = '<p>" onmouseover="alert(1)" data-x="</p><p>\' onfocus=\'alert(2)\' data-y=\'</p>';

function richTextReferenceDocument(string $html): DOMDocument
{
    $document = new DOMDocument();
    $document->loadHTML('<?xml encoding="UTF-8"><root>' . $html . '</root>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);

    return $document;
}

function richTextReferenceElements(string $html): array
{
    $names = [];

    foreach (richTextReferenceDocument($html)->getElementsByTagName('*') as $element) {
        if ($element->nodeName !== 'root') {
            $names[] = $element->nodeName;
        }
    }

    return $names;
}

function richTextReferenceText(string $html): string
{
    return richTextReferenceDocument($html)->documentElement->textContent;
}

function richTextReferenceForm(array $bioConfig = ['useRichText' => true]): array
{
    $form = formie()
        ->form(['title' => 'Rich Text Reference HTML'])
        ->multiLineTextField('bio', $bioConfig)
        ->multiLineTextField('notes')
        ->contentField('intro')
        ->create();

    $submission = formie()->submission($form)->with([
        'bio' => RICH_TEXT_REFERENCE_VALUE,
        'notes' => 'Plain <strong>notes</strong> & co',
    ])->save();

    return [$form, $submission, References::field((string)$form->getFieldByHandle('bio')?->reference)];
}

function richTextReferenceHtml(string $template, Submission $submission): string
{
    return References::interpolateText($template, ReferenceContext::forSubmission($submission), ReferenceOutputContext::Html, true);
}

function richTextReferenceEventAttributes(string $html): array
{
    $attributes = [];

    foreach (richTextReferenceDocument($html)->getElementsByTagName('*') as $element) {
        foreach ($element->attributes as $attribute) {
            if (str_starts_with($attribute->name, 'on')) {
                $attributes[] = $attribute->name;
            }
        }
    }

    return $attributes;
}

function withRichTextReferenceValue(string $value, callable $callback): mixed
{
    $handler = static function(ModifyFieldValueEvent $event) use ($value): void {
        if ($event->field->handle === 'bio') {
            $event->value = $value;
        }
    };

    Event::on(MultiLineText::class, Field::EVENT_MODIFY_VALUE_FOR_REFERENCE, $handler);

    try {
        return $callback();
    } finally {
        Event::off(MultiLineText::class, Field::EVENT_MODIFY_VALUE_FOR_REFERENCE, $handler);
    }
}

it('renders rich-text answers as formatted HTML in success messages', function (): void {
    [$form, $submission, $token] = richTextReferenceForm();
    $form->setSettings(['successMessage' => '<p>Thanks:</p>' . $token]);
    $message = $form->settings->getSuccessMessage($submission);

    expect(richTextReferenceElements($message))->toBe(['p', 'p', 'strong', 'p'])
        ->and(richTextReferenceText($message))->toBe('Thanks:Bold & moreSecond')
        ->and($message)->not->toContain('&lt;strong&gt;')
        ->and($message)->not->toContain('&amp;amp;');
})->group('security');

it('renders rich-text answers as formatted HTML in content blocks', function (): void {
    [$form, $submission, $token] = richTextReferenceForm();
    $content = $form->getFieldByHandle('intro');
    $content->content = RichText::from('<p>Intro:</p>' . $token);
    $html = $content->getRenderedContentBlock($form, null, $submission);

    expect(richTextReferenceElements($html))->toContain('strong')
        ->and(richTextReferenceText($html))->toBe('Intro:Bold & moreSecond');
})->group('security');

it('renders rich-text answers as formatted HTML in notification emails', function (): void {
    [, $submission, $token] = richTextReferenceForm();
    $notification = new Notification([
        'name' => 'Rich Text Reference', 'handle' => 'richTextReference' . uniqid(), 'to' => 'recipient@example.test', 'from' => 'sender@example.test', 'subject' => 'Rich text',
        'content' => '<p>Email:</p>' . $token,
    ]);

    $result = Formie::$plugin->getEmails()->renderEmail($notification, $submission);
    $body = (string)$result['email']->getSymfonyEmail()->getHtmlBody();

    expect($result)->not->toHaveKey('error')
        ->and($body)->toContain('<strong>Bold</strong> &amp; more')
        ->and($body)->not->toContain('&lt;strong&gt;');
})->group('security');

it('renders rich-text answers in Group fields as formatted HTML', function (): void {
    $form = formie()
        ->form(['title' => 'Rich Text Group Reference'])
        ->groupField('profile', ['rows' => [['fields' => [[
            'type' => MultiLineText::class, 'handle' => 'bio', 'label' => 'Bio', 'useRichText' => true,
        ]]]]])
        ->create();
    $submission = formie()->submission($form)->with(['profile' => ['bio' => RICH_TEXT_REFERENCE_VALUE]])->save();
    $bio = $form->getFieldByHandle('profile')?->getFieldByHandle('bio');

    $html = richTextReferenceHtml('<div>' . References::field((string)$bio?->reference) . '</div>', $submission);

    expect(richTextReferenceElements($html))->toBe(['div', 'p', 'strong', 'p'])
        ->and(richTextReferenceText($html))->toBe('Bold & moreSecond');
})->group('security');

it('sanitizes rich-text answers changed by reference listeners', function (): void {
    [, $submission, $token] = richTextReferenceForm();
    $html = withRichTextReferenceValue(
        '<p onclick="alert(1)">Hi<script>alert(2)</script><img src=x onerror=alert(3)><a href="javascript:alert(4)">link</a><iframe src="https://example.test"></iframe></p>',
        fn() => richTextReferenceHtml('<div>' . $token . '</div>', $submission),
    );

    expect(richTextReferenceElements($html))->not->toContain('script')
        ->and(richTextReferenceElements($html))->not->toContain('img')
        ->and(richTextReferenceElements($html))->not->toContain('iframe')
        ->and($html)->not->toContain('onclick')
        ->and($html)->not->toContain('onerror')
        ->and($html)->not->toContain('javascript:')
        ->and(richTextReferenceText($html))->toContain('Hi');
})->group('security');

it('applies the rich-text image setting to referenced answers', function (): void {
    $image = '<p>Photo <img src="https://example.test/photo.png" alt="Photo"></p>';

    [, $withoutImages, $token] = richTextReferenceForm(['useRichText' => true, 'richTextButtons' => ['bold']]);
    $stripped = withRichTextReferenceValue($image, fn() => richTextReferenceHtml('<div>' . $token . '</div>', $withoutImages));

    [, $withImages, $imageToken] = richTextReferenceForm(['useRichText' => true, 'richTextButtons' => ['bold', 'image']]);
    $kept = withRichTextReferenceValue($image, fn() => richTextReferenceHtml('<div>' . $imageToken . '</div>', $withImages));

    expect(richTextReferenceElements($stripped))->not->toContain('img')
        ->and(richTextReferenceElements($kept))->toContain('img');
})->group('security');

it('keeps rich-text answers encoded inside tags, attributes, comments and raw-text elements', function (string $template): void {
    [, $submission, $token] = richTextReferenceForm();
    $html = withRichTextReferenceValue(RICH_TEXT_REFERENCE_QUOTES, fn() => richTextReferenceHtml(str_replace('{T}', $token, $template), $submission));

    expect($html)->toBe(str_replace('{T}', htmlspecialchars(RICH_TEXT_REFERENCE_QUOTES, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $template));
})->with([
    'double-quoted attribute' => ['<a href="https://example.test/" title="{T}">x</a>'],
    'single-quoted attribute' => ["<a title='{T}'>x</a>"],
    'unquoted attribute' => ['<a href={T}>x</a>'],
    'after ">" in a double-quoted value' => ['<a title="a>b" href="{T}">x</a>'],
    'after ">" in a single-quoted value' => ["<a title='a>b' href='{T}'>x</a>"],
    'after a quote in an attribute name' => ['<a b"c d="x>y" href="{T}">x</a>'],
    'after a "<" in text' => ['a <= "b <a title="x>y" href="{T}">x</a>'],
    'open comment' => ['<!-- a > b {T} -->'],
    'after a closed comment' => ['<!-- note --><div>{T}</div>'],
    'script' => ["<script>var a = '{T}';</script>"],
    'style' => ['<style>.a { content: "{T}" }</style>'],
    'textarea' => ['<textarea>{T}</textarea>'],
    'title' => ['<title>{T}</title>'],
])->group('security');

it('renders rich-text answers as HTML in element text', function (string $template): void {
    [, $submission, $token] = richTextReferenceForm();
    $html = withRichTextReferenceValue(RICH_TEXT_REFERENCE_QUOTES, fn() => richTextReferenceHtml(str_replace('{T}', $token, $template), $submission));

    expect($html)->toBe(str_replace('{T}', StringHelper::cleanRichTextRequest(RICH_TEXT_REFERENCE_QUOTES, false), $template))
        ->and(richTextReferenceEventAttributes($html))->toBe([]);
})->with([
    'element' => ['<div>{T}</div>'],
    'after encoded markup' => ['<p>a &gt; b</p><div>{T}</div>'],
    'after a ">" in an attribute value' => ['<a title="a>b">x</a><div>{T}</div>'],
    'after quotes in text' => ['<p>it\'s "q"</p>{T}'],
])->group('security');

it('keeps public HTML interpolation encoded unless field HTML is requested', function (): void {
    [, $submission, $token] = richTextReferenceForm();
    $template = '<div>' . $token . '</div>';
    $encoded = '<div>' . htmlspecialchars(RICH_TEXT_REFERENCE_VALUE, ENT_QUOTES) . '</div>';
    $html = ['outputContext' => ReferenceOutputContext::Html];

    expect((new ReferenceResolver())->interpolateText($template, ReferenceContext::forSubmission($submission), ReferenceOutputContext::Html))->toBe($encoded)
        ->and(References::interpolateText($template, ReferenceContext::forSubmission($submission), ReferenceOutputContext::Html))->toBe($encoded)
        ->and(References::parseContent($template, $submission, $html))->toBe($encoded)
        ->and((new FormieVariable())->parseContent($template, $submission, $html))->toBe($encoded)
        ->and(richTextReferenceElements(References::parseContent($template, $submission, $html + ['allowFieldHtml' => true])))->toBe(['div', 'p', 'strong', 'p']);
})->group('security');

it('renders rich-text answers as formatted HTML in PDF content', function (): void {
    [, $submission, $token] = richTextReferenceForm();
    $notification = new Notification([
        'name' => 'Rich Text Reference PDF', 'handle' => 'richTextReferencePdf' . uniqid(), 'to' => 'recipient@example.test', 'from' => 'sender@example.test', 'subject' => 'Rich text',
        'content' => '<p>PDF:</p>' . $token,
    ]);
    $contentHtml = null;
    $handler = static function(PdfEvent $event) use (&$contentHtml): void {
        $contentHtml = (string)$event->variables['contentHtml'];
        $event->pdf = 'pdf';
    };

    Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);

    try {
        Formie::$plugin->getPdfTemplates()->renderSubmissionPdf($submission, notification: $notification);
    } finally {
        Event::off(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);
    }

    expect($contentHtml)->toContain('<strong>Bold</strong> &amp; more')
        ->and($contentHtml)->not->toContain('&lt;strong&gt;');
})->group('security');

it('keeps transformed, plain-text and non-HTML output unchanged', function (): void {
    [$form, $submission, $token] = richTextReferenceForm();
    $notes = References::field((string)$form->getFieldByHandle('notes')?->reference);
    $upper = References::field((string)$form->getFieldByHandle('bio')?->reference, metadata: ['transform' => 'upper']);
    $context = ReferenceContext::forSubmission($submission);

    expect(richTextReferenceHtml('<div>' . $upper . '</div>', $submission))->toBe('<div>' . htmlspecialchars(mb_strtoupper(RICH_TEXT_REFERENCE_VALUE), ENT_QUOTES) . '</div>')
        ->and(richTextReferenceHtml('<div>' . $notes . '</div>', $submission))->toBe('<div>Plain &lt;strong&gt;notes&lt;/strong&gt; &amp; co</div>')
        ->and(References::interpolateText($token, $context, ReferenceOutputContext::PlainText))->toBe(RICH_TEXT_REFERENCE_VALUE)
        ->and(References::interpolateText($token, $context, ReferenceOutputContext::UrlComponent))->toBe(rawurlencode(RICH_TEXT_REFERENCE_VALUE));
})->group('security');

it('keeps rich-text answers encoded in autoescaped object templates', function (): void {
    [, $submission] = richTextReferenceForm();

    $rendered = Formie::$plugin->getTemplates()->renderSandboxedObjectTemplate('<a title="{bio}">{bio}</a>', $submission);

    expect(richTextReferenceElements($rendered))->toBe(['a'])
        ->and(richTextReferenceDocument($rendered)->getElementsByTagName('a')->item(0)?->attributes->length)->toBe(1)
        ->and(richTextReferenceDocument($rendered)->getElementsByTagName('a')->item(0)?->getAttribute('title'))->toBe(RICH_TEXT_REFERENCE_VALUE)
        ->and(richTextReferenceText($rendered))->toBe(RICH_TEXT_REFERENCE_VALUE);
})->group('security');

it('keeps rich-text Repeater collections as text', function (): void {
    $form = formie()
        ->form(['title' => 'Rich Text Repeater Reference'])
        ->repeaterField('people', ['rows' => [['fields' => [[
            'type' => MultiLineText::class, 'handle' => 'bio', 'label' => 'Bio', 'useRichText' => true,
        ]]]]])
        ->create();
    $submission = formie()->submission($form)->with(['people' => [['bio' => '<p><b>One</b></p>'], ['bio' => '<p>Two</p>']]])->save();
    $bio = $form->getFieldByHandle('people')?->getFieldByHandle('bio');

    $all = richTextReferenceHtml('<div>' . References::field((string)$bio?->reference, metadata: ['scope' => 'all']) . '</div>', $submission);
    $first = richTextReferenceHtml('<div>' . References::field((string)$bio?->reference, metadata: ['scope' => 'first']) . '</div>', $submission);

    expect(richTextReferenceElements($all))->toBe(['div'])
        ->and(richTextReferenceText($all))->toBe('<p><b>One</b></p>, <p>Two</p>')
        ->and(richTextReferenceElements($first))->toBe(['div', 'p', 'b']);
})->group('security');

it('formats rich-text answers in integration messages', function (): void {
    [, $submission, $token] = richTextReferenceForm();
    $integration = new Slack(['message' => '<p>Bio:</p>' . $token]);
    $render = new ReflectionMethod($integration, '_renderMessage');

    expect((string)$render->invoke($integration, $submission))->toBe("Bio:\n\n*Bold* &amp; more\n\nSecond");
})->group('security');
