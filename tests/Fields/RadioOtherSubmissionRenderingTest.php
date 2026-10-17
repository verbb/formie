<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\fields\Radio;
use Tests\Support\WebRequestTestHelper;

it('renders custom radio answers as editable canonical values in the submission editor', function (): void {
    WebRequestTestHelper::withWebRequestContext(function (): void {
        $field = new Radio([
            'handle' => 'priority', 'label' => 'Priority', 'enableOtherOption' => true,
            'options' => [['label' => 'Standard', 'value' => 'standard']],
        ]);
        $view = Craft::$app->getView();
        $mode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        try {
            $html = (string)$field->getSubmissionHtml($field->normalizeValue('After hours <collection>'), null);
        } finally {
            $view->setTemplateMode($mode);
        }
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $selected = $xpath->query('//input[@type="radio" and @checked]')->item(0);
        $other = $xpath->query('//input[@name="priority[other]"]')->item(0);
        expect($selected)->not->toBeNull()
            ->and($selected?->getAttribute('value'))->toBe(Radio::OTHER_OPTION_VALUE)
            ->and($selected?->getAttribute('name'))->toBe('priority[value]')
            ->and($other?->getAttribute('value'))->toBe('After hours <collection>')
            ->and($other?->getAttribute('aria-label'))->toBe('Other, please specify');
    });
});
