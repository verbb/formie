<?php

declare(strict_types=1);

use verbb\formie\models\RichText;

function renderRichTextLink(string $href, ?string $target = '_blank'): string
{
    return RichText::from([
        [
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'text' => 'Privacy policy',
                'marks' => [[
                    'type' => 'link',
                    'attrs' => array_filter([
                        'href' => $href,
                        'target' => $target,
                    ], static fn(mixed $value): bool => $value !== null),
                ]],
            ]],
        ],
    ])->toHtml(null, false);
}

it('omits rel on internal links opened in a new tab', function (): void {
    $html = renderRichTextLink('/privacy-policy');

    expect($html)
        ->toContain('target="_blank"')
        ->toContain('href="/privacy-policy"')
        ->not->toContain('rel=');
});

it('omits rel on craft ref tag links opened in a new tab', function (): void {
    $html = renderRichTextLink('{entry:100:url}');

    expect($html)
        ->toContain('target="_blank"')
        ->not->toContain('rel=');
});

it('adds rel on external links opened in a new tab', function (): void {
    $html = renderRichTextLink('https://example.com/privacy');

    expect($html)
        ->toContain('target="_blank"')
        ->toContain('rel="noopener noreferrer nofollow"')
        ->toContain('href="https://example.com/privacy"');
});

it('does not add rel when the link opens in the same tab', function (): void {
    $html = renderRichTextLink('https://example.com/privacy', null);

    expect($html)
        ->not->toContain('rel=')
        ->not->toContain('target=');
});
