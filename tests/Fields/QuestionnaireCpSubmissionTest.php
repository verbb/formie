<?php

declare(strict_types=1);

use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\fields\Survey;

it('renders nested Likert input names exactly once inside the CP namespace', function (): void {
    $form = formie()->form()->addField(Survey::class, 'matrix', [
        'displayType' => 'likert', 'multipleRowsEnabled' => true,
        'likertRows' => [['label' => 'Clarity', 'value' => 'clarity'], ['label' => 'Support', 'value' => 'support']],
        'options' => [['label' => 'Poor', 'value' => 'poor'], ['label' => 'Good', 'value' => 'good']],
    ])->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $field = $form->getFieldByHandle('matrix');
        $html = $view->namespaceInputs((string)$field->getSubmissionHtml(null, null), 'fields');
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $input = $xpath->query('//input[@type="radio"]')->item(0);
        expect($input->getAttribute('name'))->toBe('fields[matrix][clarity]');
        expect($xpath->query('//input[@type="radio"]')->item(2)->getAttribute('name'))->toBe('fields[matrix][support]');
    });
});

it('labels every rank selector in the CP', function (): void {
    $form = formie()->form()->addField(Survey::class, 'ranked', [
        'displayType' => 'rank',
        'options' => [['label' => 'Speed', 'value' => 'speed'], ['label' => 'Quality', 'value' => 'quality']],
    ])->create();
    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = $view->namespaceInputs((string)$form->getFieldByHandle('ranked')->getSubmissionHtml(null, null), 'fields');
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        expect($xpath->query('//select')->length)->toBe(2);
        foreach ($xpath->query('//select') as $index => $select) {
            $label = $xpath->query('//label[@for="' . $select->getAttribute('id') . '"]')->item(0);
            expect($label)->not->toBeNull();
            expect(trim($label->textContent))->toBe('Rank #' . ($index + 1));
        }
    });
});
