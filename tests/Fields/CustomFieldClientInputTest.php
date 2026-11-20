<?php

declare(strict_types=1);

use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\adapters\LinkCustomFieldAdapter;

// Client-rendered forms show a Custom link or map field with the markup server-rendered forms show, which its browser
// module works in, and read the answer back from the inputs named under the field.
it('sends the link markup and its input name to client-rendered forms', function(): void {
    $form = formie()->form()->addField(CustomField::class, 'website', [
        'label' => 'Website',
        'customFieldAdapter' => LinkCustomFieldAdapter::class,
        'customFieldAdapterSettings' => ['allowedTypes' => ['url', 'email'], 'showLabelField' => true],
    ])->create();

    try {
        $input = $form->getFieldByHandle('website')->getClientRenderedInput();

        expect($input['inputType'])->toBe('link')
            ->and($input['htmlName'])->toBe('fields[website]')
            ->and($input['html'])->toContain('name="fields[website][type]"')
            ->and($input['html'])->toContain('name="fields[website][value]"')
            ->and($input['html'])->toContain('name="fields[website][label]"')
            ->and($input['html'])->toContain('data-formie-custom-link');
    } finally {
        Craft::$app->getElements()->deleteElement($form, true);
    }
});
