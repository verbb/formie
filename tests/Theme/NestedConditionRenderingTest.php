<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;

function nestedRenderCondition(string $handle): array
{
    return ['showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [
        ['field' => $handle, 'condition' => '=', 'value' => 'yes'],
    ]];
}

it('renders independent field conditions when continuing a submission', function (string $method, int $pageCount): void {
    $builder = formie()->form(['title' => 'Nested Condition Rendering'])->multiPage($pageCount)
        ->onPage(1)->singleLineTextField('first');
    $form = $builder->onPage($pageCount)->singleLineTextField('allow')->singleLineTextField('allowChild')
        ->{$method}('details', [
            'enableConditions' => true,
            'conditions' => nestedRenderCondition('allow'),
            'rows' => [['fields' => [
                ['type' => SingleLineText::class, 'handle' => 'ordinary', 'label' => 'Ordinary child'],
                ['type' => SingleLineText::class, 'handle' => 'conditional', 'label' => 'Conditional child', 'enableConditions' => true, 'conditions' => nestedRenderCondition('allowChild')],
            ]]],
        ])->create();
    $submission = new Submission(['title' => 'Continuing submission']);
    $submission->setForm($form);
    $submission->setFieldValues([
        'first' => 'Saved first page',
        'allow' => 'no',
        'allowChild' => 'no',
        'details' => $method === 'repeaterField' ? [['ordinary' => '', 'conditional' => '']] : ['ordinary' => '', 'conditional' => ''],
    ]);
    $form->setCurrentSubmission($submission);

    $parent = $form->getFieldByHandle('details');
    $ordinary = $parent->getFields()[0];
    // Submission validation must still inherit the parent's unavailable state.
    expect($ordinary->isConditionallyHidden($submission))->toBeTrue();

    foreach ([['no', 'no', true, true], ['yes', 'no', false, true], ['yes', 'yes', false, false]] as [$allow, $allowChild, $parentHidden, $childHidden]) {
        $submission->setFieldValues(['allow' => $allow, 'allowChild' => $allowChild]);
        $html = (string)Formie::$plugin->getRendering()->renderField($form, $parent);
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($document);
        $wrappers = $xpath->query('//*[@data-formie-field]');

        expect($wrappers->length)->toBe(3);
        $root = $wrappers->item(0);
        $plain = $wrappers->item(1);
        $conditional = $wrappers->item(2);

        expect($root->hasAttribute('data-formie-conditionally-hidden'))->toBe($parentHidden)
            ->and($plain->hasAttribute('data-formie-conditionally-hidden'))->toBeFalse()
            ->and($plain->getAttribute('class'))->not->toContain('formie-conditionally-hidden')
            ->and($conditional->hasAttribute('data-formie-conditionally-hidden'))->toBe($childHidden)
            ->and($conditional->hasAttribute('data-formie-conditions'))->toBeTrue();
    }
})->with([
    'single-page group' => ['groupField', 1],
    'multi-page group' => ['groupField', 2],
    'single-page repeater' => ['repeaterField', 1],
    'multi-page repeater' => ['repeaterField', 2],
]);

it('leaves page visibility on the page wrapper when rendering saved field values', function (): void {
    $form = formie()->form(['title' => 'Conditional Page Rendering'])->multiPage(2)
        ->onPage(1)->singleLineTextField('allow')
        ->onPage(2)->singleLineTextField('details')->create();
    $field = $form->getFieldByHandle('details');
    $page = $field->getPage();
    $settings = $page->getPageSettings();
    $settings->enablePageConditions = true;
    $settings->pageConditions = nestedRenderCondition('allow');
    $page->setPageSettings($settings->toArray());
    $submission = new Submission(['title' => 'Continuing conditional page']);
    $submission->setForm($form);
    $submission->setFieldValue('allow', 'no');
    $form->setCurrentSubmission($submission);
    expect($field->isConditionallyHidden($submission))->toBeTrue();
    $html = (string)Formie::$plugin->getRendering()->renderField($form, $field);
    expect($html)->not->toContain('data-formie-conditionally-hidden')
        ->and($html)->not->toContain('formie-conditionally-hidden');
});
