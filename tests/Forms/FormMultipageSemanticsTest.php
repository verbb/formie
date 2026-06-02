<?php

declare(strict_types=1);

it('resolves current and next pages for multipage forms', function (): void {
    $form = formie()
        ->form(['title' => 'Multipage Semantics'])
        ->multiPage(3)
        ->onPage(1)->singleLineTextField('pageOne')
        ->onPage(2)->singleLineTextField('pageTwo')
        ->onPage(3)->singleLineTextField('pageThree')
        ->create();

    $pages = $form->getPages();
    $current = $form->getCurrentPage();
    $next = $form->getNextPage($current);
    $lastNext = $form->getNextPage($pages[2]);

    expect($current)->not->toBeNull()
        ->and($next)->not->toBeNull()
        ->and($next?->id)->toBe($pages[1]->id)
        ->and($lastNext)->toBeNull();
});

it('keeps page handles non-empty across multipage setups', function (): void {
    $form = formie()
        ->form(['title' => 'Page Handles'])
        ->multiPage(2)
        ->onPage(1)->singleLineTextField('alpha')
        ->onPage(2)->singleLineTextField('beta')
        ->create();

    $handles = array_map(static fn($page) => $page->getHandle(), $form->getPages());

    expect($handles[0])->not->toBeEmpty()
        ->and($handles[1])->not->toBeEmpty();
});
