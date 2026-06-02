<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;

it('keeps handle criteria scoped by form context', function (): void {
    $formA = formie()
        ->form(['title' => 'Scope A'])
        ->singleLineTextField('fullName')
        ->create();

    $formB = formie()
        ->form(['title' => 'Scope B'])
        ->singleLineTextField('fullName')
        ->create();

    $submissionA = formie()->submission($formA)->with(['fullName' => 'Scoped Value'])->save();
    formie()->submission($formB)->with(['fullName' => 'Scoped Value'])->save();

    $scoped = Submission::find()
        ->form($formA->handle)
        ->fullName('Scoped Value')
        ->all();

    $ids = array_map(static fn($submission) => $submission->id, $scoped);

    expect(in_array($submissionA->id, $ids, true))->toBeTrue();
    expect($scoped)->toHaveCount(1);
});
