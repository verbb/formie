<?php

declare(strict_types=1);

use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFieldValueEvent;
use verbb\formie\fields\Address;
use verbb\formie\fields\subfields\AddressCountry;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationField;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceOutputContext;
use verbb\formie\references\ReferenceUsage;
use yii\base\Event;

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

it('names a country that is no longer in the Allowed Countries list', function (array $country, string $stored, string $expected): void {
    // Allowed Countries can be narrowed after submissions exist, which drops their country from the dropdown options.
    $form = formie()->form()->addressField('address', ['rows' => addressCountryMappingRows($country)])->create();
    $submission = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => $stored]])->save();

    $form = Form::find()->id($form->id)->one();
    $form->getFieldByHandle('address')->allowedCountryCodes = ['NZ'];
    expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $reference = Form::find()->id($form->id)->one()->getFieldByHandle('address')->reference;

    expect(Form::find()->id($form->id)->one()->getFieldByHandle('address')->allowedCountryCodes)->toBe(['NZ'])
        ->and(addressCountryMappedValue($submission, $reference, 'country.label'))->toBe($expected)
        ->and(addressCountryMappedValue($submission, $reference, 'country.value'))->toBe($stored);
})->with([
    'ISO values' => [[], 'AU', 'Australia'],
    'full-name values' => [['optionValue' => 'full'], 'Australia', 'Australia'],
    'ISO labels' => [['optionLabel' => 'short'], 'AU', 'AU'],
]);

it('maps a stored country that matches no country as stored', function (): void {
    // Imported or legacy answers can hold text that isn't a country, which the form would now reject.
    $form = formie()->form()->addressField('address', ['rows' => addressCountryMappingRows()])->create();
    $saved = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => 'AU']])->save();
    $saved->setFieldValue('address', ['address1' => '1 Main St', 'country' => 'Atlantis']);
    expect(Craft::$app->getElements()->saveElement($saved, false))->toBeTrue();
    $reference = $form->getFieldByHandle('address')->reference;

    expect(addressCountryMappedValue($saved, $reference, 'country.label'))->toBe('Atlantis')
        ->and(addressCountryMappedValue($saved, $reference, 'country.value'))->toBe('Atlantis');
});

it('maps an Address country in a Repeater for every row preset', function (): void {
    $form = formie()->form()->repeaterField('stops', ['rows' => [['fields' => [
        ['type' => Address::class, 'handle' => 'address', 'label' => 'Address', 'rows' => addressCountryMappingRows()],
    ]]]])->create();
    $saved = formie()->submission($form)->with(['stops' => [['address' => ['city' => 'Sydney', 'country' => 'AU']], ['address' => ['city' => 'Auckland', 'country' => 'NZ']]]])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $reference = $form->getFieldByHandle('stops')->getFieldByHandle('address')->reference;
    $resolve = fn(string $selector, string $scope) => References::resolveValue('{field:' . $reference . ':' . $selector . ';scope=' . $scope . '}', ReferenceContext::forSubmission($submission, usage: ReferenceUsage::Integration))->requireValue();

    expect($resolve('country.label', 'first'))->toBe('Australia')
        ->and($resolve('country.label', 'last'))->toBe('New Zealand')
        ->and($resolve('country.label', 'all'))->toBe(['Australia', 'New Zealand'])
        ->and($resolve('country.value', 'all'))->toBe(['AU', 'NZ'])
        ->and($resolve('country', 'count'))->toBe(2)
        ->and($resolve('country.label', 'count'))->toBe(2)
        ->and($resolve('country.value', 'count'))->toBe(2);
});

it('does not run value-as-string listeners when mapping the country label', function (): void {
    // Dropdown's Label selector reads the option directly, so site listeners for summaries never see mappings.
    $form = formie()->form()->addressField('address', ['rows' => addressCountryMappingRows()])->create();
    $submission = formie()->submission($form)->with(['address' => ['address1' => '1 Main St', 'country' => 'AU']])->save();
    $reference = $form->getFieldByHandle('address')->reference;
    $calls = 0;
    $handler = function(ModifyFieldValueEvent $event) use (&$calls): void {
        $calls++;
        $event->value = 'Changed by listener';
    };
    Event::on(AddressCountry::class, Field::EVENT_MODIFY_VALUE_AS_STRING, $handler);

    try {
        expect(addressCountryMappedValue($submission, $reference, 'country.label'))->toBe('Australia')
            ->and($calls)->toBe(0);
    } finally {
        Event::off(AddressCountry::class, Field::EVENT_MODIFY_VALUE_AS_STRING, $handler);
    }
});
