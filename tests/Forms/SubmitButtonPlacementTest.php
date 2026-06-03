<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\Formie;
use verbb\formie\theme\context\RenderContext;

it('renders the submit button on the last row when placement is end-of-last-row', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Last Row'])
        ->singleLineTextField('email')
        ->create();

    $page = $form->getPages()[0];
    $pageSettings = $page->getPageSettings();
    $pageSettings->submitButtonPlacement = 'end-of-last-row';
    $page->setPageSettings($pageSettings->toArray());

    $layout = $form->getFormLayout();
    $layout->setPages([$page]);
    $form->setFormLayout($layout);

    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $bodyHtml = $view->renderTemplate('formie/_special/form-template/page/body', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => [],
        ]);

        $buttonsHtml = $view->renderTemplate('formie/_special/form-template/page/buttons', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => [],
        ]);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($bodyHtml)->toContain('data-formie-row-submit-inline')
        ->and($bodyHtml)->toContain('data-formie-row-submit')
        ->and($bodyHtml)->toContain('data-formie-action="submit"')
        ->and($buttonsHtml)->not->toContain('data-formie-action="submit"');
});

it('renders the submit button in the page footer by default', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Footer Default'])
        ->singleLineTextField('email')
        ->create();

    $page = $form->getPages()[0];

    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $bodyHtml = $view->renderTemplate('formie/_special/form-template/page/body', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => [],
        ]);

        $buttonsHtml = $view->renderTemplate('formie/_special/form-template/page/buttons', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => [],
        ]);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($bodyHtml)->not->toContain('data-formie-row-submit-inline')
        ->and($buttonsHtml)->toContain('data-formie-action="submit"');
});

it('falls back to the page footer when end-of-last-row is enabled but the page has no rows', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Last Row Empty Page'])
        ->create();

    $page = $form->getPages()[0];
    $pageSettings = $page->getPageSettings();
    $pageSettings->submitButtonPlacement = 'end-of-last-row';
    $page->setPageSettings($pageSettings->toArray());

    expect($pageSettings->shouldRenderSubmitOnLastRow(false))->toBeFalse()
        ->and($page->shouldRenderSubmitOnLastRow(false))->toBeFalse();

    $rowTag = Formie::$plugin->getFormSlotRegistry()->resolve('row', RenderContext::from([
        'form' => $form,
        'page' => $page,
        'row' => null,
    ]));

    expect($rowTag?->coreAttributes['data']['formie-row-submit-inline'] ?? null)->toBeNull();
});

it('marks the last row for inline submit when end-of-last-row is enabled', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Last Row Slot'])
        ->singleLineTextField('email')
        ->create();

    $page = $form->getPages()[0];
    $pageSettings = $page->getPageSettings();
    $pageSettings->submitButtonPlacement = 'end-of-last-row';
    $page->setPageSettings($pageSettings->toArray());

    $row = $page->getRows(false)[0];

    $rowTag = Formie::$plugin->getFormSlotRegistry()->resolve('row', RenderContext::from([
        'form' => $form,
        'page' => $page,
        'row' => $row,
    ]));

    expect($rowTag?->coreAttributes['data']['formie-row-submit-inline'] ?? null)->toBeTrue()
        ->and($rowTag?->coreAttributes['data']['formie-field-count'] ?? null)->toBe(2);
});
