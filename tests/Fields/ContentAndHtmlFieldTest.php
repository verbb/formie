<?php

declare(strict_types=1);

use verbb\formie\models\RichText;

it('renders rich text content field output on the front end', function (): void {
    $form = formie()
        ->form(['title' => 'Rich Text Content Field'])
        ->contentField('intro', [
            'content' => RichText::from('<p><strong>Hello</strong> world</p>'),
        ])
        ->create();

    $field = $form->getFieldByHandle('intro');
    $html = $field?->getRenderedContentBlock($form, null, null);

    expect($html)->toBeString()
        ->toContain('<strong>Hello</strong>')
        ->toContain('world')
        ->not->toContain('<script');
})->group('fields');

it('does not parse twig in html fields when allow twig is disabled', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Toggle'])
        ->htmlField('notice', [
            'htmlContent' => '{% set x = 1 %}<p>Static</p>',
            'allowTwig' => false,
            'purifyContent' => false,
        ])
        ->create();

    $field = $form->getFieldByHandle('notice');
    $html = $field?->getRenderedHtmlBlock($form, null, null);

    expect($html)->toBeString()
        ->toContain('{% set x = 1 %}')
        ->toContain('<p>Static</p>');
})->group('fields');

it('parses twig in html fields when allow twig is enabled', function (): void {
    $form = formie()
        ->form(['title' => 'HTML Field Twig Enabled'])
        ->htmlField('notice', [
            'htmlContent' => '<p>{{ "Hello"|t }}</p>',
            'allowTwig' => true,
            'purifyContent' => false,
        ])
        ->create();

    $field = $form->getFieldByHandle('notice');
    $html = $field?->getRenderedHtmlBlock($form, null, null);

    expect($html)->toBeString()
        ->toContain('<p>Hello</p>');
})->group('fields');

it('returns html editor config defaults and merges project config overrides', function (): void {
    $defaults = verbb\formie\helpers\HtmlHelper::getHtmlEditorConfig('fields.html');

    expect($defaults)->toMatchArray([
        'rows' => 12,
        'tabSize' => 4,
        'lineNumbers' => true,
        'language' => 'html',
    ]);
})->group('fields');

it('returns rich text config defaults for content fields', function (): void {
    $defaults = verbb\formie\helpers\RichTextHelper::getRichTextConfig('fields.content');

    expect($defaults)->toMatchArray([
        'rows' => 8,
    ])
        ->and($defaults['buttons'])->toContain('bold', 'italic', 'link');
})->group('fields');
