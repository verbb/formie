<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\fields\Table;
use Tests\Support\WebRequestTestHelper;

it('keeps static table rows fixed while allowing their cell values to be edited in the CP', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        $field = new Table([
            'handle' => 'items', 'static' => true,
            'columns' => ['col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']],
            'defaults' => [['col1' => 'First'], ['col1' => 'Second']],
        ]);
        $view = Craft::$app->getView();
        $mode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        try {
            $html = (string)$field->getSubmissionHtml($field->normalizeValue(null, null), null);
        } finally {
            $view->setTemplateMode($mode);
        }
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        expect($xpath->query('//button')->length)->toBe(0)
            ->and($xpath->query('//textarea[not(@disabled)]')->length)->toBe(2);
    });
});
