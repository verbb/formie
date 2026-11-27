<?php

declare(strict_types=1);

use verbb\formie\services\RuntimeConfiguration;

// A prefill query parameter can arrive as an array (`?message[]=…`). Rich text must purify it the same
// way as a string, because the browser editor writes the initial value as HTML.

it('purifies a rich text prefill value posted as an array', function (mixed $query): void {
    $form = formie()->form(['title' => 'Rich text prefill ' . uniqid()])
        ->multiLineTextField('message', ['useRichText' => true, 'prefillQueryParam' => 'message'])
        ->create();

    (new RuntimeConfiguration())->establish($form, ['message' => $query]);
    $value = (string)$form->getFieldByHandle('message')->getInitialValue($form);

    expect($value)->not->toContain('onerror')
        ->and($value)->not->toContain('<script');
})->with([
    'string' => ['<img src=x onerror=alert(1)><script>alert(2)</script>'],
    'list' => [['<img src=x onerror=alert(1)><script>alert(2)</script>']],
    'keyed' => [['a' => '<img src=x onerror=alert(1)>', 'b' => '<script>alert(2)</script>']],
]);

it('keeps allowed rich text from a string prefill value', function (): void {
    $form = formie()->form(['title' => 'Rich text prefill ' . uniqid()])
        ->multiLineTextField('message', ['useRichText' => true, 'prefillQueryParam' => 'message'])
        ->create();

    (new RuntimeConfiguration())->establish($form, ['message' => '<p><strong>Hello</strong></p>']);

    expect((string)$form->getFieldByHandle('message')->getInitialValue($form))->toContain('<strong>Hello</strong>');
});
