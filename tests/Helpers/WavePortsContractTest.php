<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Calculations;
use verbb\formie\helpers\Variables;

it('includes boolean types in calculations variable picker config', function (): void {
    $field = new Calculations();
    $types = null;

    foreach ($field->defineFormBuilderGeneralSchema() as $component) {
        if (($component['name'] ?? null) === 'formula' || isset($component['variableConfig']['types'])) {
            $types = $component['variableConfig']['types'] ?? null;
            break;
        }
    }

    expect($types)->toBeArray()
        ->and($types)->toContain(Variables::TYPE_BOOLEAN)
        ->and($types)->toContain(Variables::TYPE_NUMBER);
});

it('resolves submission form ids from not id filters without formId', function (): void {
    $form = formie()
        ->form(['title' => 'Submission ID Filter'])
        ->singleLineTextField('fullName')
        ->create();

    $a = formie()->submission($form)->with(['fullName' => 'A'])->save();
    $b = formie()->submission($form)->with(['fullName' => 'B'])->save();

    // All-forms index criteria pass operators through id=; _resolveFormIds must use parseNumericParam.
    $matched = Submission::find()
        ->id(['not', (int)$b->id])
        ->ids();

    expect($matched)->toContain((int)$a->id)
        ->and($matched)->not->toContain((int)$b->id);
});
