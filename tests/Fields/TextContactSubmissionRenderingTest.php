<?php

declare(strict_types=1);

use craft\web\View;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\fields\Email;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Name;
use verbb\formie\fields\subfields\NamePrefix;
use verbb\formie\fields\Number;
use verbb\formie\fields\Phone;
use verbb\formie\fields\SingleLineText;

it('associates CP text and contact labels with their namespaced inputs', function (string $class): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($class): void {
        $field = new $class(['handle' => 'answer', 'label' => 'Answer', 'required' => true]);
        $view = Craft::$app->getView();
        $mode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        try {
            $html = $view->namespaceInputs((string)$field->getSubmissionHtml(null, null), 'fields[details][row1]');
        } finally {
            $view->setTemplateMode($mode);
        }
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $label = $xpath->query('//label[@for]')->item(0);
        $input = $xpath->query('//input[not(@type="hidden")] | //textarea | //select')->item(0);
        expect($label)->not->toBeNull()
            ->and($input)->not->toBeNull()
            ->and($input->getAttribute('id'))->toBe($label->getAttribute('for'))
            ->and($input->getAttribute('id'))->not->toBe('');
        if ($class === Phone::class) {
            expect($input->getAttribute('name'))->toBe('fields[details][row1][answer][number]');
        }
    });
})->with([SingleLineText::class, MultiLineText::class, Email::class, Number::class, Phone::class, Hidden::class, Name::class, NamePrefix::class]);

it('renders independent minimum and maximum text units in the CP', function (string $class): void {
    WebRequestTestHelper::withWebRequestContext(function () use ($class): void {
        $field = new $class([
            'handle' => 'answer', 'label' => 'Answer', 'limit' => true,
            'min' => 2, 'minType' => 'words', 'max' => 40, 'maxType' => 'characters',
        ]);
        $view = Craft::$app->getView();
        $mode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        try {
            $html = (string)$field->getSubmissionHtml(null, null);
        } finally {
            $view->setTemplateMode($mode);
        }
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $input = $xpath->query('//input | //textarea')->item(0);
        expect($input->getAttribute('data-formie-min-words'))->toBe('2')
            ->and($input->hasAttribute('data-formie-min-chars'))->toBeFalse()
            ->and($input->getAttribute('data-formie-max-chars'))->toBe('40');
    });
})->with([SingleLineText::class, MultiLineText::class]);
