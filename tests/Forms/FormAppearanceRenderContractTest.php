<?php

declare(strict_types=1);

use craft\web\View;

it('renders page tabs and progress only when their display settings are enabled', function (): void {
    $form = formie()
        ->form(['title' => 'Appearance Render Tabs Progress'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->create();

    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $form->settings->setAttributes([
            'displayPageTabs' => false,
            'displayPageProgress' => false,
        ], false);
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

        $hiddenTabs = $view->renderTemplate('formie/_special/form-template/form/tabs', ['form' => $form]);
        $hiddenProgress = $view->renderTemplate('formie/_special/form-template/form/progress', ['form' => $form]);

        $form->settings->setAttributes([
            'displayPageTabs' => true,
            'displayPageProgress' => true,
        ], false);
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

        $shownTabs = $view->renderTemplate('formie/_special/form-template/form/tabs', ['form' => $form]);
        $shownProgress = $view->renderTemplate('formie/_special/form-template/form/progress', ['form' => $form]);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect(trim($hiddenTabs))->toBe('')
        ->and(trim($hiddenProgress))->toBe('')
        ->and($shownTabs)->toContain('Page 1')
        ->and($shownTabs)->toContain('Page 2')
        ->and($shownProgress)->toContain('%');
});

it('renders current page title only when enabled', function (): void {
    $form = formie()
        ->form(['title' => 'Appearance Render Current Page Title'])
        ->multiPage(1)
        ->onPage(1)->singleLineTextField('fullName')
        ->create();

    $page = $form->getPages()[0];
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $form->settings->setAttributes(['displayCurrentPageTitle' => false], false);
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

        $hidden = $view->renderTemplate('formie/_special/form-template/page', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => [],
        ]);

        $form->settings->setAttributes(['displayCurrentPageTitle' => true], false);
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

        $shown = $view->renderTemplate('formie/_special/form-template/page', [
            'form' => $form,
            'page' => $page,
            'renderOptions' => [],
        ]);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($hidden)->not->toContain((string)$page->label)
        ->and($shown)->toContain((string)$page->label);
});
