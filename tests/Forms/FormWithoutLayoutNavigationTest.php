<?php

declare(strict_types=1);

use craft\helpers\Db;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPage;

// A navigation loop would otherwise hang the suite, so end the attempt with a normal test failure.
function boundedPageNavigation(callable $callback): mixed
{
    if (!function_exists('pcntl_alarm')) {
        set_time_limit(30);

        try {
            return $callback();
        } finally {
            set_time_limit(0);
        }
    }

    $async = pcntl_async_signals(true);

    pcntl_signal(SIGALRM, static function(): void {
        throw new RuntimeException('Page navigation did not finish.');
    });
    pcntl_alarm(10);

    try {
        return $callback();
    } finally {
        pcntl_alarm(0);
        pcntl_signal(SIGALRM, SIG_DFL);
        pcntl_async_signals($async);
    }
}

function formWithoutSavedLayout(): Form
{
    $form = formie()->form(['title' => 'No Layout ' . uniqid()])->singleLineTextField('note')->create();

    Db::update(Table::FORMIE_FORMS, ['layoutId' => null], ['id' => $form->id]);
    Formie::$plugin->getForms()->invalidateFormCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($form->id)->one();
}

function renderFormWithoutSavedLayout(Form $form): string
{
    return WebRequestTestHelper::withWebRequestContext(fn() => (string)Formie::$plugin->getRendering()->renderForm($form));
}

it('finds no neighbouring pages for a form without a saved layout', function (): void {
    $form = formWithoutSavedLayout();
    $pages = $form->getPages();

    expect($form->layoutId)->toBeNull()
        ->and($pages)->toHaveCount(1)
        ->and($pages[0]->id)->toBeNull();

    boundedPageNavigation(function() use ($form, $pages): void {
        expect($form->getPreviousPage())->toBeNull()
            ->and($form->getPreviousPage($pages[0]))->toBeNull()
            ->and($form->getNextPage())->toBeNull()
            ->and($form->getNextPage($pages[0]))->toBeNull()
            ->and($form->getPreviousPage($pages[0], null, true))->toBe($pages[0])
            ->and($form->isFirstPage($pages[0]))->toBeTrue()
            ->and($form->isLastPage($pages[0]))->toBeTrue();
    });
});

it('finds no neighbouring pages when warnings are not reported', function (): void {
    $form = formWithoutSavedLayout();
    $page = $form->getPages()[0];

    // Craft hides warnings outside of devMode, which leaves nothing to interrupt a page search that runs off the list.
    $errorLevel = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

    try {
        boundedPageNavigation(function() use ($form, $page): void {
            expect($form->getPreviousPage($page))->toBeNull()
                ->and($form->getNextPage($page))->toBeNull();
        });
    } finally {
        error_reporting($errorLevel);
    }
});

it('renders a form without a saved layout', function (): void {
    $form = formWithoutSavedLayout();
    $html = boundedPageNavigation(fn() => renderFormWithoutSavedLayout($form));

    expect($html)->toContain('<form', 'type="submit"')
        ->and($html)->not->toContain('data-field-handle="note"');
});

it('can save a form without a saved layout again', function (): void {
    $form = formWithoutSavedLayout();
    $saved = boundedPageNavigation(fn() => Craft::$app->getElements()->saveElement($form));
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($form->getErrors())->toBe([])
        ->and($reloaded)->not->toBeNull();
});

it('renders an ordinary form with no fields', function (): void {
    $form = formie()->form(['title' => 'Empty ' . uniqid()])->create();
    $form = Form::find()->id($form->id)->one();
    $page = $form->getPages()[0];

    expect($form->layoutId)->not->toBeNull()
        ->and($page->id)->not->toBeNull();

    $html = boundedPageNavigation(function() use ($form, $page): string {
        expect($form->getPreviousPage($page))->toBeNull()
            ->and($form->getNextPage($page))->toBeNull();

        return renderFormWithoutSavedLayout($form);
    });

    expect($html)->toContain('<form', 'type="submit"');
});

it('navigates saved pages using another instance of a page', function (): void {
    $created = formie()->form(['title' => 'Pages ' . uniqid()])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('one')
        ->onPage(2)->singleLineTextField('two')
        ->onPage(3)->singleLineTextField('three')
        ->create();
    $form = Form::find()->id($created->id)->one();
    [$first, $second, $third] = $form->getPages();
    $otherSecond = new FieldLayoutPage(['id' => (string)$second->id, 'label' => 'Reloaded']);

    boundedPageNavigation(function() use ($form, $first, $second, $third, $otherSecond): void {
        expect($form->getPreviousPage($first))->toBeNull()
            ->and($form->getNextPage($first)?->id)->toBe($second->id)
            ->and($form->getNextPage($otherSecond)?->id)->toBe($third->id)
            ->and($form->getPreviousPage($otherSecond)?->id)->toBe($first->id)
            ->and($form->getPreviousPage($third)?->id)->toBe($second->id)
            ->and($form->getNextPage($third))->toBeNull();
    });
});

it('tells unsaved pages apart while navigating', function (): void {
    $form = new Form(['title' => 'Unsaved']);
    $form->setFormLayout(new FieldLayout(['pages' => [['label' => 'One'], ['label' => 'Two'], ['label' => 'Three']]]));
    [$first, $second, $third] = $form->getPages();

    expect($second->id)->toBeNull();

    boundedPageNavigation(function() use ($form, $first, $second, $third): void {
        expect($form->getPreviousPage($second))->toBe($first)
            ->and($form->getNextPage($second))->toBe($third)
            ->and($form->getPreviousPage($first))->toBeNull()
            ->and($form->getNextPage($third))->toBeNull();
    });
});

it('stops looking for a page that is not in the form', function (): void {
    $form = formie()->form(['title' => 'Pages ' . uniqid()])->multiPage(2)->create();
    $foreign = new FieldLayoutPage(['id' => 2147483647, 'label' => 'Elsewhere']);

    boundedPageNavigation(function() use ($form, $foreign): void {
        expect($form->getPreviousPage($foreign))->toBeNull()
            ->and($form->getNextPage($foreign))->toBeNull();
    });
});
