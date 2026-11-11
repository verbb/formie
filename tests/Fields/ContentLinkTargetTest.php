<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;

/**
 * Rich Text content with one link, as the form builder saves it.
 */
function contentLinkTargetSchema(string $target): array
{
    return [[
        'type' => 'paragraph',
        'attrs' => ['textAlign' => 'start'],
        'content' => [[
            'type' => 'text',
            'text' => 'Privacy Policy',
            'marks' => [['type' => 'link', 'attrs' => ['href' => '/privacy-policy', 'target' => $target]]],
        ]],
    ]];
}

function contentLinkTargetAnchor(string $html): ?DOMElement
{
    $document = new DOMDocument();
    $document->loadHTML('<?xml encoding="UTF-8"><root>' . $html . '</root>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);

    foreach ($document->getElementsByTagName('a') as $anchor) {
        if ($anchor->getAttribute('href') === '/privacy-policy') {
            return $anchor;
        }
    }

    return null;
}

it('opens Rich Text links set to open in a new tab in a new tab on the front end', function(): void {
    $form = formie()->form()->contentField('disclaimer', ['label' => 'Disclaimer', 'content' => contentLinkTargetSchema('_blank')])->create();

    $html = WebRequestTestHelper::withWebRequestContext(fn() => (string)Formie::$plugin->getRendering()->renderForm($form, ['renderCss' => false, 'renderJs' => false]));
    $anchor = contentLinkTargetAnchor($html);

    expect($anchor)->not->toBeNull()
        ->and($anchor->getAttribute('target'))->toBe('_blank')
        ->and(explode(' ', $anchor->getAttribute('rel')))->toContain('noopener');
});

it('keeps purifying link targets other than a new tab', function(): void {
    $form = formie()->form()->contentField('disclaimer', ['label' => 'Disclaimer', 'content' => contentLinkTargetSchema('someFrame')])->create();
    $field = $form->getFieldByHandle('disclaimer');

    $anchor = contentLinkTargetAnchor($field->getRenderedContentBlock($form, null));

    expect($anchor)->not->toBeNull()
        ->and($anchor->hasAttribute('target'))->toBeFalse();
});
