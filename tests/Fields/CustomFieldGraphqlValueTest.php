<?php

declare(strict_types=1);

use craft\errors\GqlException;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use verbb\formie\elements\Form;
use verbb\formie\fields\CustomField;
use verbb\formie\fields\custom\adapters\GoogleMapsCustomFieldAdapter;
use verbb\formie\fields\custom\adapters\LinkCustomFieldAdapter;
use verbb\formie\fields\custom\adapters\MapsCustomFieldAdapter;
use verbb\formie\events\RegisterCustomFieldAdaptersEvent;
use verbb\formie\Formie;
use verbb\formie\services\CustomFields;
use yii\base\Event;

// The map plugins aren't installed in the test runtime, so these keep each adapter's real
// GraphQL and value methods while skipping the plugin availability check.
class CustomFieldGraphqlGoogleMapsAdapter extends GoogleMapsCustomFieldAdapter
{
    public static function craftFieldClasses(): array
    {
        return [];
    }
}

class CustomFieldGraphqlMapsAdapter extends MapsCustomFieldAdapter
{
    public static function craftFieldClasses(): array
    {
        return [];
    }
}

function withCustomFieldGraphqlAdapters(callable $callback): mixed
{
    $service = Formie::$plugin->getCustomFields();
    $reset = fn() => (fn() => $this->_adapterTypes = null)->call($service);
    $handler = static function(RegisterCustomFieldAdaptersEvent $event): void {
        $event->adapters[] = CustomFieldGraphqlGoogleMapsAdapter::class;
        $event->adapters[] = CustomFieldGraphqlMapsAdapter::class;
    };

    Event::on(CustomFields::class, CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS, $handler);
    $reset();

    try {
        return $callback();
    } finally {
        Event::off(CustomFields::class, CustomFields::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS, $handler);
        $reset();
    }
}

function customFieldGraphqlForm(array $adapters, array $values): Form
{
    return withCustomFieldGraphqlAdapters(function() use ($adapters, $values): Form {
        $factory = formie()->form();

        foreach ($adapters as $handle => $adapter) {
            $factory->addField(CustomField::class, $handle, ['customFieldAdapter' => $adapter]);
        }

        $form = Form::find()->id($factory->create()->id)->one();
        formie()->submission($form)->with($values)->save();

        return $form;
    });
}

function customFieldGraphqlRow(Form $form, string $selection): array
{
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $schema = new GqlSchema([
        'name' => 'Custom field values',
        'uid' => StringHelper::UUID(),
        'scope' => ["formieForms.{$form->uid}:read", "formieSubmissions.{$form->uid}:read"],
    ]);

    $gql->flushCaches();
    $gql->setActiveSchema($schema);

    try {
        $result = withCustomFieldGraphqlAdapters(fn() => $gql->executeQuery($schema, '{ formieSubmissions(form: "' . $form->handle . '") { ... on ' . $form->handle . '_Submission { ' . $selection . ' } } }'));
    } finally {
        $gql->flushCaches();
        $gql->setActiveSchema($previous);
    }

    expect($result['errors'] ?? [])->toBe([]);

    return json_decode(json_encode($result['data']['formieSubmissions'][0]), true);
}

it('resolves Google Maps custom field subfields', function (): void {
    $form = customFieldGraphqlForm(['place' => CustomFieldGraphqlGoogleMapsAdapter::class], ['place' => [
        'formatted' => '1 Main St, Melbourne VIC 3000',
        'name' => 'Office',
        'street1' => '1 Main St',
        'city' => 'Melbourne',
        'state' => 'VIC',
        'zip' => '3000',
        'country' => 'Australia',
        'countryCode' => 'AU',
        'placeId' => 'abc123',
        'lat' => '-37.8',
        'lng' => '144.9',
        'zoom' => '12',
    ]]);

    $row = customFieldGraphqlRow($form, 'place { __typename formatted name street1 street2 city state zip countryCode placeId lat lng zoom }');

    expect($row['place'])->toBe([
        '__typename' => 'FormieCustomGoogleMapField',
        'formatted' => '1 Main St, Melbourne VIC 3000',
        'name' => 'Office',
        'street1' => '1 Main St',
        'street2' => null,
        'city' => 'Melbourne',
        'state' => 'VIC',
        'zip' => '3000',
        'countryCode' => 'AU',
        'placeId' => 'abc123',
        'lat' => -37.8,
        'lng' => 144.9,
        'zoom' => 12,
    ]);
});

it('resolves Maps custom field subfields and parts', function (): void {
    $form = customFieldGraphqlForm(['location' => CustomFieldGraphqlMapsAdapter::class], ['location' => [
        'address' => 'Melbourne VIC, Australia',
        'lat' => '-37.8',
        'lng' => '144.9',
        'zoom' => '10',
        'parts' => ['city' => 'Melbourne', 'state' => 'VIC', 'country' => 'Australia'],
        'what3words' => 'index.home.raft',
    ]]);

    $row = customFieldGraphqlRow($form, 'location { __typename address lat lng zoom what3words parts { __typename city state country postcode } }');

    expect($row['location'])->toBe([
        '__typename' => 'FormieCustomMapsField',
        'address' => 'Melbourne VIC, Australia',
        'lat' => -37.8,
        'lng' => 144.9,
        'zoom' => 10,
        'what3words' => 'index.home.raft',
        'parts' => [
            '__typename' => 'FormieCustomMapsFieldParts',
            'city' => 'Melbourne',
            'state' => 'VIC',
            'country' => 'Australia',
            'postcode' => null,
        ],
    ]);
});

it('builds one schema for a form using every built-in adapter', function (): void {
    $form = customFieldGraphqlForm([
        'place' => CustomFieldGraphqlGoogleMapsAdapter::class,
        'location' => CustomFieldGraphqlMapsAdapter::class,
        'website' => LinkCustomFieldAdapter::class,
    ], [
        'place' => ['formatted' => 'Sydney NSW', 'lat' => '-33.9', 'lng' => '151.2'],
        'location' => ['address' => 'Perth WA'],
        'website' => ['type' => 'url', 'value' => 'https://example.com'],
    ]);

    $row = customFieldGraphqlRow($form, 'place { formatted lat } location { address } website');

    expect($row)->toBe([
        'place' => ['formatted' => 'Sydney NSW', 'lat' => -33.9],
        'location' => ['address' => 'Perth WA'],
        'website' => 'https://example.com',
    ]);
});

it('keeps Link custom field values as URL strings', function (): void {
    $form = customFieldGraphqlForm(['website' => LinkCustomFieldAdapter::class], ['website' => ['type' => 'url', 'value' => 'https://example.com/about']]);

    expect(customFieldGraphqlRow($form, 'website'))->toBe(['website' => 'https://example.com/about']);
});
