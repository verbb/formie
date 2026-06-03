<?php

declare(strict_types=1);

use craft\web\View;
use Tests\Support\Factories\ConditionFormFactory;
use verbb\formie\conditions\ConditionOperator;

it('renders submit button conditions for the front-end conditions module', function (): void {
    $form = ConditionFormFactory::make()->optionsValueVisibility();

    $page = $form->getPages()[0];
    $pageSettings = $page->getPageSettings();
    $pageSettings->enableNextButtonConditions = true;
    $pageSettings->nextButtonConditions = [
        'showRule' => 'show',
        'conditionRule' => 'all',
        'conditions' => [
            [
                'field' => 'enquiryType',
                'condition' => ConditionOperator::EQ,
                'value' => 'other',
            ],
        ],
    ];
    $page->setPageSettings($pageSettings->toArray());

    $layout = $form->getFormLayout();
    $layout->setPages([$page]);
    $form->setFormLayout($layout);

    expect($page->getSubmitButtonConditionsJson())->not->toBeNull()
        ->and($page->getSubmitButtonClientConditions()['conditions'][0]['source']['handle'] ?? null)->toBe('enquiryType');

    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $html = $view->renderTemplate('formie/_special/form-template/page/buttons', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => [],
        ]);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($html)->toContain('data-formie-conditions=')
        ->and($html)->toContain('data-formie-action="submit"')
        ->and($html)->toContain('&quot;handle&quot;:&quot;enquiryType&quot;');
});

it('omits submit button conditions when next button conditions are disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Conditions Disabled'])
        ->singleLineTextField('fullName')
        ->create();

    $page = $form->getPages()[0];

    expect($page->getSubmitButtonConditionsJson())->toBeNull();
});
