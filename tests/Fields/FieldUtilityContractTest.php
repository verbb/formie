<?php

declare(strict_types=1);

it('allows utility and non-input fields without blocking submission', function (): void {
    $form = formie()
        ->form(['title' => 'Utility Fields'])
        ->headingField('heading')
        ->htmlField('content')
        ->sectionField('section')
        ->summaryField('summary')
        ->hiddenField('hiddenToken')
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with([
            'hiddenToken' => 'abc123',
            'fullName' => 'Utility Test',
        ])
        ->save();

    expect($submission->id)->not->toBeNull()
        ->and($submission->getFieldValue('fullName'))->not->toBeNull();
});
