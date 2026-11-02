<?php

declare(strict_types=1);

use craft\helpers\HtmlPurifier;
use verbb\formie\helpers\StringHelper;

dataset('purifierValues', [
    'empty' => [''],
    'zero' => ['0'],
    'plain text' => ['Jane Citizen'],
    'ampersand' => ['Tom & Jerry'],
    'angle brackets' => ['a < b > c'],
    'unicode' => ['Ünïcödé 🎉 text'],
    'unclosed markup' => ['<p>Unclosed'],
    'misnested markup' => ['<b>bold <i>nested</b></i>'],
    'script' => ['<script>alert(1)</script>after'],
    'javascript link' => ['<a href="javascript:alert(1)">x</a>'],
    'link attributes' => ['<a href="https://example.com" rel="nofollow noopener" download>x</a>'],
    'event handler' => ['<img src="https://example.com/a.png" onerror="alert(1)">'],
    'html5 elements' => ['<figure><img src="a.png"><figcaption>cap</figcaption></figure><mark>m</mark><s>s</s>'],
    'allowed embed' => ['<oembed url="https://youtube.com/watch?v=abc"></oembed>'],
    'rejected embed' => ['<oembed url="https://evil.test/x"></oembed>'],
    'ids' => ['<div id="same">one</div><div id="same">two</div>'],
    'encoded markup' => ['&lt;script&gt;encoded&lt;/script&gt;'],
    'invalid utf-8' => ["bad\xC3\x28utf8"],
]);

it('cleans values the same as the Craft purifier helper', function(string $value): void {
    // Run twice so that any state carried over from a previous call would show up.
    expect(StringHelper::cleanString($value))->toBe(HtmlPurifier::process($value))
        ->and(StringHelper::cleanString($value))->toBe(HtmlPurifier::process($value));
})->with('purifierValues');

it('sanitizes plain text the same as a tag-free purifier config', function(string $value): void {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', '');

    $expected = html_entity_decode(HtmlPurifier::process(StringHelper::normalizePlainText($value), $config), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect(StringHelper::sanitizePlainTextInput($value))->toBe($expected)
        ->and(StringHelper::sanitizePlainTextInput($value))->toBe($expected);
})->with('purifierValues');

it('keeps the rich text and plain text purifier settings separate', function(): void {
    $value = '<p><strong>Hello</strong></p>';

    expect(StringHelper::cleanString($value))->toBe($value)
        ->and(StringHelper::sanitizePlainTextInput($value))->toBe('Hello')
        ->and(StringHelper::cleanString($value))->toBe($value)
        ->and(StringHelper::sanitizePlainTextInput($value))->toBe('Hello');
});

it('reuses one purifier for each set of settings', function(): void {
    $getPurifier = new ReflectionMethod(StringHelper::class, '_getPurifier');
    $plainText = ['HTML.Allowed' => ''];

    expect($getPurifier->invoke(null))->toBe($getPurifier->invoke(null))
        ->and($getPurifier->invoke(null, $plainText))->toBe($getPurifier->invoke(null, $plainText))
        ->and($getPurifier->invoke(null, $plainText))->not->toBe($getPurifier->invoke(null));
});
