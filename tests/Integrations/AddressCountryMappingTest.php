<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationField;
use verbb\formie\references\ReferenceOutputContext;

/**
 * Integration mappings can send the Address country's label or its stored value, as Dropdown fields can.
 */
function addressCountryMappingRows(array $country = []): array
{
    $rows = (new Address())->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$child) {
            if ($child['handle'] === 'country') {
                $child = array_merge($child, ['enabled' => true], $country);
            }
        }
        unset($child);
    }
    unset($row);

    return $rows;
}

function addressCountryMappedValue(Submission $submission, string $reference, string $selector): mixed
{
    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);

    return $integration->getMappedFieldValue(References::field($reference, $selector), Submission::find()->id($submission->id)->status(null)->one(), new IntegrationField(['type' => IntegrationField::TYPE_STRING]));
}

it('offers the country label and value as server-only Address selectors', function (): void {
    $selectors = [];

    foreach ((new Address())->referenceValues() as $value) {
        $selectors[$value->selector ?? ''] = $value;
    }

    expect($selectors)->toHaveKeys(['country', 'country.label', 'country.value'])
        ->and($selectors['country.label']->supportsFieldSelect)->toBeTrue()
        ->and($selectors['country.label']->supportsBrowser)->toBeFalse()
        ->and($selectors['country.value']->supportsBrowser)->toBeFalse();
});

it('maps the Address country label or value', function (array $country, string $stored, string $selector, string $expected): void {
    $form = formie()->form()->addressField('address', ['rows' => addressCountryMappingRows($country)])->create();
    $submission = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => $stored]])->save();

    expect(addressCountryMappedValue($submission, $form->getFieldByHandle('address')->reference, $selector))->toBe($expected);
})->with([
    'ISO values, label' => [[], 'AU', 'country.label', 'Australia'],
    'ISO values, value' => [[], 'AU', 'country.value', 'AU'],
    'ISO values, existing country selector' => [[], 'AU', 'country', 'AU'],
    'full-name values, label' => [['optionValue' => 'full'], 'Australia', 'country.label', 'Australia'],
    'full-name values, value' => [['optionValue' => 'full'], 'Australia', 'country.value', 'Australia'],
    'ISO labels, label' => [['optionLabel' => 'short'], 'AU', 'country.label', 'AU'],
]);

it('maps an empty Address country label as empty', function (): void {
    $form = formie()->form()->addressField('address', ['rows' => addressCountryMappingRows()])->create();
    $submission = formie()->submission($form)->with(['address' => ['address1' => '1 Main St']])->save();

    expect(addressCountryMappedValue($submission, $form->getFieldByHandle('address')->reference, 'country.label'))->toBeIn(['', null]);
});

it('maps the country label of an Address inside a Group', function (): void {
    $form = formie()->form()->groupField('details', [
        'rows' => [['fields' => [
            ['type' => Address::class, 'handle' => 'address', 'label' => 'Postal address', 'rows' => addressCountryMappingRows()],
        ]]],
    ])->create();
    $submission = formie()->submission($form)->with(['details' => ['address' => ['address1' => '1 Main St', 'country' => 'NZ']]])->save();
    $address = $form->getFieldByHandle('details')->getFieldByHandle('address');

    expect(addressCountryMappedValue($submission, $address->reference, 'country.label'))->toBe('New Zealand');
});

it('outputs the Address country label as a variable in email content', function (): void {
    $form = formie()->form()->addressField('address', ['rows' => addressCountryMappingRows()])->create();
    $submission = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => 'AU']])->save();
    $reference = $form->getFieldByHandle('address')->reference;
    $content = 'Label: ' . References::field($reference, 'country.label') . ', value: ' . References::field($reference, 'country.value');

    expect((string)References::parseContent($content, Submission::find()->id($submission->id)->status(null)->one(), ['outputContext' => ReferenceOutputContext::Html]))->toBe('Label: Australia, value: AU');
});
