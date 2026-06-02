<?php

declare(strict_types=1);

it('handles larger mixed-field forms as a field performance smoke contract', function (): void {
    $factory = formie()
        ->form(['title' => 'Field Performance Smoke'])
        ->multiPage(4);

    for ($page = 1; $page <= 4; $page++) {
        $factory->onPage($page)
            ->singleLineTextField("text{$page}")
            ->emailField("email{$page}")
            ->numberField("number{$page}")
            ->dateField("date{$page}")
            ->multiLineTextField("notes{$page}");
    }

    $form = $factory->create();

    $values = [];

    for ($page = 1; $page <= 4; $page++) {
        $values["text{$page}"] = "Name {$page}";
        $values["email{$page}"] = "email{$page}@example.test";
        $values["number{$page}"] = (string)(20 + $page);
        $values["date{$page}"] = '2024-01-0' . $page;
        $values["notes{$page}"] = "Notes {$page}";
    }

    $submission = formie()->submission($form)->with($values)->save();

    expect(count($form->getFields()))->toBe(20)
        ->and($submission->id)->not->toBeNull();
});
