<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

function formRoundtripHandle(): string
{
    static $counter = 6000;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}

it('rebuilds a form from roundtripped builder-like payloads', function (): void {
    $source = formie()
        ->form(['title' => 'Roundtrip Source'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('first')
        ->onPage(2)->emailField('second')
        ->submitAction('message', ['message' => 'Saved'])
        ->create();

    $payload = [
        'pages' => array_map(static function($page) {
            return [
                'label' => $page->label,
                'settings' => $page->getSettings(),
                'rows' => array_map(static function($row) {
                    return [
                        'fields' => array_map(static function($field) {
                            return [
                                'type' => get_class($field),
                                'handle' => $field->handle,
                                'label' => $field->label,
                                'required' => (bool)($field->required ?? false),
                            ];
                        }, $row->getFields()),
                    ];
                }, $page->getRows()),
            ];
        }, $source->getPages()),
        'settings' => $source->settings->toArray(),
    ];

    $clone = new Form([
        'title' => 'Roundtrip Clone',
        'handle' => formRoundtripHandle(),
    ]);
    $clone->getFormLayout()->setPages($payload['pages']);
    $clone->settings->setAttributes($payload['settings'], false);

    $saved = \Craft::$app->elements->saveElement($clone);
    $reloaded = Form::find()->id($clone->id)->one();

    expect($saved)->toBeTrue()
        ->and(count($reloaded?->getPages() ?? []))->toBe(2)
        ->and($reloaded?->getFieldByHandle('first'))->not->toBeNull()
        ->and($reloaded?->getFieldByHandle('second'))->not->toBeNull()
        ->and($reloaded?->settings->submitAction)->toBe('message');
});
