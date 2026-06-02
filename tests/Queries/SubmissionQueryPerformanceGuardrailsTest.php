<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('supports repeated query evaluation without runtime drift', function (): void {
    $form = formie()
        ->form(['title' => 'Query Performance Guardrail'])
        ->singleLineTextField('fullName')
        ->create();

    $expected = formie()->submission($form)->with(['fullName' => 'Perf 1'])->save();
    formie()->submission($form)->with(['fullName' => 'Perf 2'])->save();

    for ($i = 0; $i < 20; $i++) {
        $results = Submission::find()
            ->formId($form->id)
            ->fullName('Perf 1')
            ->limit(5)
            ->all();

        expect($results)->toHaveCount(1)
            ->and($results[0]->id)->toBe($expected->id);
    }
});
