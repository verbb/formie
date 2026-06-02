<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\models\FieldLayout;

function formLayoutStructureHandle(): string
{
    static $counter = 5000;
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';

    do {
        $first = intdiv($counter, 26) % 26;
        $second = $counter % 26;
        $handle = $alphabet[$first] . $alphabet[$second];
        $counter++;
    } while (Form::find()->handle($handle)->status(null)->one() !== null);

    return $handle;
}

it('supports single and multipage layout structures', function (): void {
    $single = formie()
        ->form(['title' => 'Single Page'])
        ->singleLineTextField('singleValue')
        ->create();

    $multi = formie()
        ->form(['title' => 'Multi Page'])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->onPage(3)->singleLineTextField('pageThree')
        ->create();

    expect($single->hasMultiplePages())->toBeFalse()
        ->and(count($single->getPages()))->toBe(1)
        ->and($multi->hasMultiplePages())->toBeTrue()
        ->and(count($multi->getPages()))->toBe(3);
});

it('persists page order changes after save and reload', function (): void {
    $form = formie()
        ->form(['title' => 'Page Order'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('alpha')
        ->onPage(2)->singleLineTextField('beta')
        ->create();

    $layout = $form->getFormLayout();
    $pages = $layout->getPages();
    $pages[0]->label = 'First';
    $pages[1]->label = 'Second';
    $layout->setPages([$pages[1], $pages[0]]);
    $form->setFormLayout($layout);

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();
    $labels = array_map(static fn($page) => $page->label, $reloaded?->getPages() ?? []);

    expect($saved)->toBeTrue()
        ->and($labels)->toBe(['Second', 'First']);
});

it('accepts explicit multi-row layout configs and keeps row/field structure', function (): void {
    $form = new Form([
        'title' => 'Rows Contract',
        'handle' => formLayoutStructureHandle(),
    ]);

    $form->setFormLayout(new FieldLayout([
        'pages' => [
            [
                'label' => 'Page 1',
                'settings' => [],
                'rows' => [
                    [
                        'fields' => [
                            [
                                'type' => verbb\formie\fields\SingleLineText::class,
                                'handle' => 'rowOneField',
                                'label' => 'Row One',
                            ],
                        ],
                    ],
                    [
                        'fields' => [
                            [
                                'type' => verbb\formie\fields\Email::class,
                                'handle' => 'rowTwoField',
                                'label' => 'Row Two',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]));

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();
    $rows = $reloaded?->getRows() ?? [];

    expect($saved)->toBeTrue()
        ->and(count($rows))->toBe(2)
        ->and($reloaded?->getFieldByHandle('rowOneField'))->not->toBeNull()
        ->and($reloaded?->getFieldByHandle('rowTwoField'))->not->toBeNull();
});
