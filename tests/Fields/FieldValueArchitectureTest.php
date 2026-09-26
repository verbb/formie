<?php

use verbb\formie\base\Field;
use verbb\formie\content\FieldStorageCodec;
use verbb\formie\fields;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\fields\values\NameFieldValue;
use verbb\formie\fields\values\DateFieldValue;
use verbb\formie\fields\values\PaymentFieldValue;

it('declares and enforces each registered core empty runtime value', function () {
    foreach (\verbb\formie\Formie::$plugin->getFields()->getRegisteredFields() as $type) {
        $field = is_string($type) ? new $type(['handle' => 'subject']) : $type;
        if (!$field instanceof Field) {
            continue;
        }
        $value = $field->normalizeValue(null, null);
        expect($field->valueType()->accepts($value), get_class($field))->toBeTrue();
        $again = $field->normalizeValue($value, null);
        expect($field->valueType()->accepts($again), get_class($field))->toBeTrue();
        FieldStorageCodec::assertSafe($field->getValueAsData($value));
        FieldStorageCodec::assertSafe($field->serializeValueForClientInput($value));
    }
});

it('round trips every value family through whole-value encryption without exposing plaintext', function ($class, $config, $input) {
    $field = new $class(['handle' => 'subject', 'enableContentEncryption' => true] + $config);
    $value = $field->normalizeFieldValue($input);
    $before = $field->getValueAsData($value);
    $stored = $field->serializeValueForDb($value);
    expect(array_keys($stored))->toBe(['__formie_encrypted', 'ciphertext']);
    expect($stored['__formie_encrypted'])->toBe(1);
    $restored = $field->normalizeValueFromStorage($stored);
    expect(get_debug_type($restored))->toBe(get_debug_type($value));
    expect($field->getValueAsData($restored))->toEqual($before);
    expect($field->getValueAsData($field->normalizeValue($value, null)))->toEqual($before);
    $field->getValueAsString($value);
    $field->getValueForReference($value);
    $field->getValueForExport($value);
    $field->serializeValueForClientInput($value);
    expect($field->getValueAsData($value))->toEqual($before);
})->with([
    [fields\SingleLineText::class, [], 'secret text'],
    [fields\Email::class, [], 'secret@example.test'],
    [fields\Phone::class, ['countryDefaultValue' => 'AU'], '+61400000000'],
    [fields\Number::class, [], '9007199254740993.12345678901234567890'],
    [fields\Agree::class, [], true],
    [fields\Name::class, ['useMultipleFields' => false], 'Ada Lovelace'],
    [fields\Date::class, [], '2026-09-26'],
    [fields\Date::class, [], 'not a date'],
    [fields\Dropdown::class, ['options' => [['label' => 'A', 'value' => 'a']]], 'a'],
    [fields\Checkboxes::class, ['options' => [['label' => 'A', 'value' => 'a']]], ['a', 'invalid']],
    [fields\Payment::class, [], ['amount' => '123.45', 'token' => 'private-token']],
    [fields\Table::class, ['columns' => ['col1' => ['type' => 'color', 'handle' => 'colour', 'heading' => 'Colour']]], [['col1' => '#ff0000']]],
]);

it('preserves decimal precision and malformed numeric input without float parsing', function () {
    $field = new fields\Number(['handle' => 'amount']);
    expect($field->normalizeFieldValue(['locale' => 'de', 'value' => '9.007.199.254.740.993,1234567890123456789']))->toBe('9007199254740993.1234567890123456789');
    expect($field->normalizeFieldValue('not numeric'))->toBe('not numeric');
    expect($field->normalizeFieldValue(['unexpected' => 'invalid']))->toBe('{"unexpected":"invalid"}');
});

it('never decrypts untrusted strings and rejects tampered trusted envelopes', function () {
    $field = new fields\SingleLineText(['handle' => 'text', 'enableContentEncryption' => true]);
    $legacy = \verbb\formie\helpers\StringHelper::encenc('private');
    expect($field->normalizeValueFromRequest($legacy, null))->toBe($legacy);
    expect($field->normalizeValueFromStorage($legacy))->toBe('private');
    expect(fn() => $field->normalizeValueFromStorage(['__formie_encrypted' => 1, 'ciphertext' => 'broken']))->toThrow(RuntimeException::class);
    expect(fn() => $field->normalizeValueFromStorage('base64:' . base64_encode('crypt:broken')))->toThrow(RuntimeException::class);
    expect(fn() => $field->normalizeValueFromStorage(['__formie_encrypted' => 2, 'ciphertext' => 'future']))->toThrow(RuntimeException::class);
    expect(fn() => $field->normalizeValueFromStorage(['__formie_encrypted' => 1, 'ciphertext' => null]))->toThrow(RuntimeException::class);
});

it('uses immutable names in both modes and immutable payment parts without service state', function () {
    foreach ([false, true] as $multiple) {
        $field = new fields\Name(['handle' => 'person', 'useMultipleFields' => $multiple]);
        $value = $field->normalizeFieldValue(null);
        expect($value)->toBeInstanceOf(NameFieldValue::class);
        expect(fn() => $value->firstName = 'mutated')->toThrow(LogicException::class);
        expect($value->canResolvePath('isMultiple'))->toBeFalse();
    }
    $value = new PaymentFieldValue(['token' => 'original']);
    expect(fn() => $value->token = 'mutated')->toThrow(LogicException::class);
    expect(method_exists($value, 'getPayment'))->toBeFalse();
});

it('dispatches one event per projection and keeps data distinct from browser options', function () {
    $field = new fields\Dropdown(['handle' => 'choice', 'options' => [['value' => 'a', 'label' => 'Alpha']]]);
    $value = $field->normalizeFieldValue('a');
    $events = [];
    foreach ([Field::EVENT_MODIFY_VALUE_AS_STRING, Field::EVENT_MODIFY_VALUE_AS_DATA, Field::EVENT_MODIFY_VALUE_FOR_REFERENCE] as $event) {
        $field->on($event, function ($e) use (&$events) { $events[] = $e->name; });
    }
    $field->getValueForReference($value);
    expect($events)->toBe([Field::EVENT_MODIFY_VALUE_FOR_REFERENCE]);
    expect($field->serializeValueForClientInput($value))->toBe('a');
    expect($field->getValueAsData($value))->toBe(['label' => 'Alpha', 'value' => 'a', 'selected' => true, 'valid' => true]);
    expect(fn() => $value->value = 'b')->toThrow(Error::class);
});

it('rejects arbitrary object serialization and actionable extension type mismatches', function () {
    $field = new fields\SingleLineText(['handle' => 'subject']);
    expect(fn() => $field->serializeValueForDb((object)['secret' => 'hidden']))->toThrow(LogicException::class);
    $wrong = new class(['handle' => 'wrong']) extends fields\SingleLineText {
        public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element): mixed { return new stdClass(); }
    };
    expect(fn() => $wrong->normalizeFieldValue(null))->toThrow(LogicException::class, 'expected FieldValueType::string');
});

it('adapts Formie 3 protected JSON and serialization overrides', function () {
    $field = new class(['handle' => 'legacy']) extends Field {
        protected function defineValueAsJson(mixed $value, \craft\base\ElementInterface $element = null): mixed { return ['legacy' => $value]; }
        public function serializeValue(mixed $value, ?\craft\base\ElementInterface $element): mixed { return ['stored' => $value]; }
    };
    expect($field->getValueAsData('value'))->toBe(['legacy' => 'value']);
    expect($field->getValueAsJson('value'))->toBe(['legacy' => 'value']);
    expect($field->serializeValueForDb('value'))->toBe(['stored' => 'value']);
});

it('round trips each core empty value through storage and projects without changing it', function () {
    foreach (\verbb\formie\Formie::$plugin->getFields()->getRegisteredFields() as $type) {
        $field = is_string($type) ? new $type(['handle' => 'subject']) : $type;
        if (!$field instanceof Field) {
            continue;
        }
        $value = $field->normalizeFieldValue(null);
        $data = $field->getValueAsData($value);
        $roundTrip = $field->normalizeValueFromStorage($field->serializeValueForDb($value));
        expect($field->getValueAsData($roundTrip), get_class($field))->toEqual($data);
        expect($field->getValueAsData($field->normalizeFieldValue($value)), get_class($field))->toEqual($data);
    }
});

it('retains malformed calendar inputs and timezone through encrypted storage and browser redisplay', function ($input) {
    $field = new fields\Date(['handle' => 'when', 'displayType' => 'datePicker', 'enableContentEncryption' => true]);
    $value = $field->normalizeValueFromRequest($input, null);
    expect($value)->toBeInstanceOf(DateFieldValue::class);
    expect($value->isValid())->toBeFalse();
    $restored = $field->normalizeValueFromStorage($field->serializeValueForDb($value));
    expect($field->getValueAsData($restored))->toBe($field->getValueAsData($value));
    expect($field->serializeValueForClientInput($restored))->toBe($input);
    expect($field->resolveNormalizedValuePath($restored, 'date'))->toBe($input['date'] ?? '');
})->with([
    [['date' => 'not-a-date', 'time' => '13:45']],
    [['date' => '2026-02-31', 'time' => '25:90']],
]);

it('keeps a timezone-bearing date and both range endpoints immutable and lossless', function () {
    $field = new fields\Date(['handle' => 'when', 'enableContentEncryption' => true]);
    $date = new DateTimeImmutable('2026-09-26 12:34:56', new DateTimeZone('Australia/Melbourne'));
    $value = $field->normalizeFieldValue($date);
    expect($value->getPart('timezone'))->toBe('Australia/Melbourne');
    $restored = $field->normalizeValueFromStorage($field->serializeValueForDb($value));
    expect(DateFieldValue::partsToDateTime($restored->getParts())->format('c'))->toBe($date->format('c'));
    $parts = $restored->getParts();
    $parts['day'] = '1';
    expect($restored->getPart('day'))->toBe('26');
    $range = new fields\Date(['handle' => 'range', 'displayType' => 'datePicker', 'collectMode' => fields\Date::COLLECT_RANGE, 'enableContentEncryption' => true]);
    $value = $range->normalizeFieldValue(['start' => $date, 'end' => 'invalid end']);
    expect($value->start->getPart('timezone'))->toBe('Australia/Melbourne');
    expect($value->end->isValid())->toBeFalse();
    expect($range->getSubFieldPartValue($value, 'endDate'))->toBe('invalid end');
    $restored = $range->normalizeValueFromStorage($range->serializeValueForDb($value));
    expect($range->getValueAsData($restored))->toBe($range->getValueAsData($value));
    expect(fn() => $restored->start = new DateFieldValue())->toThrow(Error::class);
});

it('composes encrypted child values by their exact nested identities and survives database reload', function (string $placement) {
    $method = $placement === 'group' ? 'groupField' : 'repeaterField';
    $form = formie()->form()->$method('details', ['enableContentEncryption' => true, 'rows' => [['fields' => [
        ['type' => fields\Name::class, 'handle' => 'person', 'label' => 'Person', 'useMultipleFields' => false, 'enableContentEncryption' => true],
        ['type' => fields\Number::class, 'handle' => 'amount', 'label' => 'Amount', 'enableContentEncryption' => true],
        ['type' => fields\Checkboxes::class, 'handle' => 'options', 'label' => 'Options', 'options' => [['label' => 'One', 'value' => 'one']]],
    ]]]])->create();
    $row = ['person' => 'Private Name', 'amount' => '9007199254740993.123456789', 'options' => ['one']];
    $rows = $placement === 'group' ? $row : [$row, ['person' => 'Second Person', 'amount' => '0', 'options' => []]];
    $submission = formie()->submission($form)->with(['details' => $rows])->save();
    $field = $form->getFieldByHandle('details');
    $value = $submission->getFieldValue('details');
    $data = $field->getValueAsData($value);
    $stored = $submission->serializeFieldValues()[$field->uid];
    expect(json_encode($stored))->not->toContain('Private Name', 'Second Person', '9007199254740993');
    $decoded = FieldStorageCodec::decode($stored);
    $first = $placement === 'group' ? $decoded : $decoded[0];
    $child = $field->getFields()[0];
    expect($first)->toHaveKey($child->uid);
    expect($first[$child->uid]['__formie_encrypted'])->toBe(1);
    $saved = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($saved->getFieldValueAsData('details'))->toEqual($data);
    expect($field->getValueAsData($field->normalizeValueFromStorage($stored)))->toEqual($data);
    $field->getValueAsString($value);
    $field->getValueForExport($value);
    expect($field->getValueAsData($value))->toEqual($data);
    $path = $placement === 'group' ? 'details.person' : 'details.0.person';
    expect($saved->getFieldValue($path))->toBeInstanceOf(NameFieldValue::class);
    expect($saved->getFieldValueAsString($path))->toBe('Private Name');
})->with(['group', 'repeater']);

it('matches recipient browser tokens against configured rows without decrypting input', function () {
    $field = new fields\Recipients(['handle' => 'route', 'displayType' => 'dropdown', 'options' => [
        ['uid' => 'first-row', 'label' => 'First', 'value' => 'same@example.test'],
        ['uid' => 'second-row', 'label' => 'Second', 'value' => 'same@example.test'],
    ]]);
    $token = $field->getFieldOptions()[1]['value'];
    $value = $field->normalizeValueFromRequest($token, null);
    expect($value->label())->toBe('Second');
    expect($field->serializeValueForClientInput($value))->toBe($token);
    $legacy = \verbb\formie\helpers\StringHelper::encenc('same@example.test');
    $invalid = $field->normalizeValueFromRequest($legacy, null);
    expect($invalid->valid())->toBeFalse();
    expect($invalid->rawValue())->toBe($legacy);
    expect($field->normalizeValueFromStorage($legacy)->rawValue())->toBe('same@example.test');
});

it('adapts public Formie 3 JSON overrides once even when they call the parent', function () {
    $field = new class(['handle' => 'legacy']) extends Field {
        public function getValueAsJson(mixed $value, ?\craft\base\ElementInterface $element = null): mixed
        {
            return ['old' => parent::getValueAsJson($value, $element)];
        }
    };
    $events = 0;
    $field->on(Field::EVENT_MODIFY_VALUE_AS_DATA, function () use (&$events) { $events++; });
    expect($field->getValueAsData('text'))->toBe(['old' => 'text']);
    expect($events)->toBe(1);
});

it('merges partial encrypted parent requests before missing children normalize to empty', function () {
    \verbb\formie\Formie::$plugin->getSettings()->setOnlyCurrentPagePayload = true;
    $form = formie()->form()->groupField('details', ['enableContentEncryption' => true, 'rows' => [['fields' => [
        ['type' => fields\SingleLineText::class, 'handle' => 'first', 'label' => 'First', 'enableContentEncryption' => true],
        ['type' => fields\Name::class, 'handle' => 'second', 'label' => 'Second', 'useMultipleFields' => false, 'enableContentEncryption' => true],
    ]]]])->create();
    $submission = formie()->submission($form)->with(['details' => ['first' => 'Before', 'second' => 'Keep This']])->save();
    $loaded = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    $loaded->setFieldValueFromRequest('details', ['first' => 'After']);
    expect($loaded->getFieldValueAsString('details.second'))->toBe('Keep This');
    expect(Craft::$app->getElements()->saveElement($loaded))->toBeTrue();
    $again = \verbb\formie\elements\Submission::find()->id($submission->id)->status(null)->one();
    expect($again->getFieldValueAsData('details'))->toEqual($loaded->getFieldValueAsData('details'));
    expect($again->getFieldValue('details.first'))->toBe('After');
    $again->setFieldValueFromRequest('details', ['first' => null]);
    expect($again->getFieldValue('details.first'))->toBe('');
    expect($again->getFieldValueAsString('details.second'))->toBe('Keep This');
});

it('adapts legacy declared rich types without exposing arbitrary object properties', function () {
    $field = new class(['handle' => 'legacy']) extends Field {
        public static function phpType(): string { return '?'.NameFieldValue::class; }
        public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element): mixed
        {
            return $value instanceof NameFieldValue ? $value : new NameFieldValue(['name' => (string)$value]);
        }
        public function serializeValue(mixed $value, ?\craft\base\ElementInterface $element): mixed { return (string)$value; }
        protected function defineValueAsJson(mixed $value, ?\craft\base\ElementInterface $element = null): mixed { return ['name' => (string)$value]; }
    };
    foreach ([null, 'Legacy Name'] as $input) {
        $value = $field->normalizeFieldValue($input);
        expect($value)->toBeInstanceOf(NameFieldValue::class);
        expect($field->getValueAsData($field->normalizeValueFromStorage($field->serializeValueForDb($value))))->toBe(['name' => (string)$input]);
    }
});

it('keeps built-in custom link snapshots immutable across native Craft objects and projections', function () {
    $field = new fields\CustomField(['handle' => 'link', 'customFieldAdapter' => \verbb\formie\fields\custom\adapters\LinkCustomFieldAdapter::class, 'enableContentEncryption' => true]);
    $source = new \craft\fields\data\LinkData('https://example.test/path', new \craft\fields\linktypes\Url(), ['label' => 'Original']);
    $value = $field->normalizeFieldValue($source);
    $source->setLabel('Changed');
    expect($value->getLabel())->toBe('Original');
    expect(fn() => $value->label = 'Changed')->toThrow(LogicException::class);
    expect($value->canResolvePath('linkType'))->toBeFalse();
    $data = $field->getValueAsData($value);
    expect($field->getValueAsData($field->normalizeValueFromStorage($field->serializeValueForDb($value))))->toEqual($data);
    expect($field->getValueAsString($value))->toBe('https://example.test/path');
    expect($field->serializeValueForClientInput($value))->toEqual($data);
});

it('declares and preserves every survey presentation runtime shape', function (string $display) {
    $field = new fields\Survey(['handle' => 'answer', 'displayType' => $display, 'enableContentEncryption' => true, 'options' => [['label' => 'One', 'value' => 'one']]]);
    foreach ([null, 'one'] as $input) {
        $value = $field->normalizeFieldValue($input);
        $data = $field->getValueAsData($value);
        expect($field->getValueAsData($field->normalizeValueFromStorage($field->serializeValueForDb($value))))->toEqual($data);
        FieldStorageCodec::assertSafe($field->serializeValueForClientInput($value));
    }
})->with(['likert', 'rank', 'rating', 'dropdown', 'radio', 'checkboxes', 'singleLineText', 'multiLineText']);

it('normalizes configured multi-part names and Likert rows without mutable collections', function () {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'enableContentEncryption' => true])->create();
    $field = $form->getFieldByHandle('person');
    $value = $field->normalizeFieldValue(['firstName' => 'Ada', 'lastName' => 'Lovelace']);
    expect($value)->toBeInstanceOf(NameFieldValue::class);
    expect($field->getValueAsData($field->normalizeValueFromStorage($field->serializeValueForDb($value))))->toEqual($field->getValueAsData($value));
    $survey = new fields\Survey(['handle' => 'rating', 'displayType' => 'likert', 'likertRows' => [['label' => 'First', 'value' => 'first'], ['label' => 'Second', 'value' => 'second']], 'options' => [['label' => 'Yes', 'value' => 'yes']], 'enableContentEncryption' => true]);
    $value = $survey->normalizeFieldValue(['first' => 'yes', 'second' => 'invalid']);
    $data = $survey->getValueAsData($value);
    expect($survey->getValueAsData($survey->normalizeValueFromStorage($survey->serializeValueForDb($value))))->toEqual($data);
    expect(method_exists($value, 'setSelection'))->toBeFalse();
    expect($value->canResolvePath('unlisted'))->toBeFalse();
});

it('separates date display and integration formats without mutating canonical parts', function () {
    $field = new fields\Date(['handle' => 'when', 'dateFormat' => 'd/m/Y', 'timeFormat' => 'H:i']);
    $value = $field->normalizeFieldValue(['year' => '2026', 'month' => '9', 'day' => '26', 'hour' => '1', 'minute' => '30', 'ampm' => 'PM']);
    $before = $value->toValueArray();
    $integration = new class extends \verbb\formie\base\Integration {};
    $target = new \verbb\formie\models\IntegrationField(['type' => \verbb\formie\models\IntegrationField::TYPE_DATETIME]);
    expect($field->getValueForIntegration($value, $target, $integration))->toBe('2026-09-26 13:30:00');
    expect((string)$value)->toBe('2026-09-26 13:30:00');
    expect($value->toValueArray())->toBe($before);
});

it('preserves map adapter parts and isolates them from mutable public projections', function (string $adapter, array $input) {
    $field = new fields\CustomField(['handle' => 'map', 'customFieldAdapter' => $adapter, 'enableContentEncryption' => true]);
    foreach ([null, $input] as $source) {
        $value = $field->normalizeFieldValue($source);
        $data = $field->getValueAsData($value);
        expect($field->getValueAsData($field->normalizeValueFromStorage($field->serializeValueForDb($value))))->toEqual($data);
        if ($value !== null) {
            expect(fn() => $value->lat = 1)->toThrow(LogicException::class);
        }
        FieldStorageCodec::assertSafe($field->serializeValueForClientInput($value));
    }
})->with([
    [\verbb\formie\fields\custom\adapters\MapsCustomFieldAdapter::class, ['address' => 'Melbourne', 'lat' => '-37.8', 'lng' => '144.9', 'parts' => ['city' => 'Melbourne']]],
    [\verbb\formie\fields\custom\adapters\GoogleMapsCustomFieldAdapter::class, ['formatted' => 'Melbourne', 'lat' => '-37.8', 'lng' => '144.9', 'raw' => ['city' => 'Melbourne']]],
]);

it('keeps populated relation queries and their criteria intact through encryption and projections', function (string $class, string $elementClass) {
    $element = $elementClass::find()->status(null)->one();
    expect($element)->not->toBeNull();
    $field = new $class(['handle' => 'related', 'enableContentEncryption' => true]);
    $value = $field->normalizeFieldValue([$element->id]);
    $value->status('enabled');
    $status = $value->status;
    $data = $field->getValueAsData($value);
    $stored = $field->serializeValueForDb($value);
    expect($field->normalizeValueFromStorage($stored)->ids())->toBe((clone $value)->ids());
    $integration = new class extends \verbb\formie\base\Integration {};
    $target = new \verbb\formie\models\IntegrationField(['type' => \verbb\formie\models\IntegrationField::TYPE_ARRAY]);
    $field->getValueForIntegration($value, $target, $integration);
    expect($value->status)->toBe($status);
    expect($field->getValueAsData($value))->toBe($data);
})->with([
    [fields\Entries::class, \craft\elements\Entry::class],
    [fields\Categories::class, \craft\elements\Category::class],
    [fields\Users::class, \craft\elements\User::class],
    [fields\Tags::class, \craft\elements\Tag::class],
]);

it('retains stored password hashes across encrypted metadata-only resaves', function () {
    $field = new fields\Password(['handle' => 'password', 'enableContentEncryption' => true]);
    $stored = $field->serializeValueForDb($field->normalizeFieldValue('Secret!password123'));
    $hash = $field->normalizeValueFromStorage($stored);
    expect(Craft::$app->getSecurity()->validatePassword('Secret!password123', $hash))->toBeTrue();
    expect($field->normalizeValueFromStorage($field->serializeValueForDb($hash)))->toBe($hash);
});


it('keeps every stored decimal digit in control-panel input markup', function () {
    $field = new fields\Number(['handle' => 'amount', 'decimals' => 2]);
    $value = '9007199254740993.12345678901234567890';
    expect((string)$field->getSubmissionHtml($value, null))->toContain('value="' . $value . '"');
    expect((string)$field->getSubmissionHtml('123.4', null))->toContain('value="123.40"');
});

it('retains malformed phone request shapes for validation and redisplay', function () {
    $field = new fields\Phone(['handle' => 'phone']);
    $value = $field->normalizeFieldValue(['number' => ['invalid']]);
    expect($value)->toBe('["invalid"]');
    expect($field->serializeValueForClientInput($value)['number'])->toBe($value);
});


it('preserves decimal literals and string variables through GraphQL number fields and table cells', function () {
    $field = new fields\Number(['handle' => 'amount']);
    $scalar = $field->getContentGqlType();
    $schema = new \GraphQL\Type\Schema(['query' => new \GraphQL\Type\Definition\ObjectType([
        'name' => 'DecimalContractQuery',
        'fields' => ['amount' => [
            'type' => $scalar,
            'args' => ['input' => $field->getContentGqlMutationArgumentType()['type']],
            'resolve' => fn($root, $args) => $field->normalizeValueFromStorage($field->serializeValueForDb($field->normalizeFieldValue($args['input']))),
        ]],
    ])]);
    $precise = '9007199254740993.12345678901234567890';
    foreach (['{ amount(input: ' . $precise . ') }', 'query($input: FormieDecimal) { amount(input: $input) }'] as $query) {
        $result = \GraphQL\GraphQL::executeQuery($schema, $query, variableValues: ['input' => $precise])->toArray();
        expect($result)->toBe(['data' => ['amount' => $precise]]);
    }
    $columns = \verbb\formie\gql\types\TableRowType::prepareRowFieldDefinitionFromColumns(['col1' => ['type' => 'number', 'handle' => 'amount']]);
    expect($columns['amount']->serialize($precise))->toBe($precise);
    expect($scalar->parseValue('invalid decimal'))->toBe('invalid decimal');
});


it('isolates nested relation values from integration and reference-block event mutations', function () {
    $form = formie()->form()->groupField('group', ['rows' => [['fields' => [
        ['type' => fields\Entries::class, 'handle' => 'entries', 'label' => 'Entries'],
    ]]]])->create();
    $field = $form->getFieldByHandle('group');
    $value = $field->normalizeFieldValue(['entries' => []]);
    $query = $value['entries'];
    $query->status('live');
    $field->on(Field::EVENT_MODIFY_VALUE_FOR_INTEGRATION, function ($event) {
        $event->rawValue['entries']->status(null);
    });
    $field->on(Field::EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK, function ($event) {
        $event->value['entries']->status(null);
    });
    $target = new \verbb\formie\models\IntegrationField(['type' => \verbb\formie\models\IntegrationField::TYPE_ARRAY]);
    $field->getValueForIntegration($value, $target, new class extends \verbb\formie\base\Integration {});
    expect($query->status)->toBe('live');
    $field->getValueForReferenceBlock($value, new \verbb\formie\models\Notification());
    expect($query->status)->toBe('live');
});


it('keeps nested date getters canonical while input parts use configured display formats', function () {
    $form = formie()->form()->dateField('when', ['displayType' => 'datePicker', 'dateFormat' => 'd/m/Y', 'timeFormat' => 'g:ia', 'includeTime' => true])->create();
    $field = $form->getFieldByHandle('when');
    $submission = new \verbb\formie\elements\Submission();
    $submission->setForm($form);
    $submission->setFieldValue('when', new DateTimeImmutable('2026-09-26 15:04:05'));
    $value = $submission->getFieldValue('when');
    expect($submission->getFieldValue('when.date'))->toBe('2026-09-26');
    expect($submission->getFieldValue('when.time'))->toBe('15:04:05');
    expect($field->getSubFieldPartValue($value, 'date'))->toBe('26/09/2026');
    expect($field->getSubFieldPartValue($value, 'time'))->toBe('3:04pm');
    foreach ($field->getFields() as $child) {
        if ($child->handle === 'date') {
            expect($child->getElementValue($submission))->toBe('26/09/2026');
        }
    }
});
