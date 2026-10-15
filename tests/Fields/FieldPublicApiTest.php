<?php

declare(strict_types=1);

use craft\base\ElementInterface;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\compatibility\fields\FieldCompatibility;
use verbb\formie\elements\Submission;
use verbb\formie\fields;
use verbb\formie\fields\values\RecipientsFieldValue;
use verbb\formie\models\Notification;

class PublicApiLegacySchemaField extends Field
{
    public int $calls = 0;

    public function defineGeneralSchema(): array
    {
        if (++$this->calls > 2) {
            throw new RuntimeException('Recursive schema adapter.');
        }
        return [...parent::defineGeneralSchema(), ['name' => 'custom']];
    }
}

class PublicApiLegacyTextField extends fields\SingleLineText
{
    public function defineGeneralSchema(): array
    {
        return [...parent::defineGeneralSchema(), ['name' => 'custom']];
    }
}

class PublicApiLegacyReferenceField extends Field
{
    public int $calls = 0;

    public function getValueForVariable(mixed $value, Submission $submission, Notification $notification): mixed
    {
        ++$this->calls;
        return parent::getValueForVariable($value, $submission, $notification) . ':' . $notification->name;
    }
}

class PublicApiLegacyProtectedReferenceField extends fields\SingleLineText
{
    protected function defineValueForVariableRaw(mixed $value, Submission $submission, Notification $notification): mixed
    {
        return parent::defineValueForVariableRaw($value, $submission, $notification) . ':' . $notification->name;
    }
}

class PublicApiCanonicalReferenceField extends PublicApiLegacyReferenceField
{
    protected function defineValueForReference(mixed $value, ElementInterface $element = null): mixed
    {
        return 'canonical';
    }
}

class PublicApiLegacyBlockField extends Field
{
    public function getValueForEmail(mixed $value, Notification $notification, ?ElementInterface $element = null): mixed
    {
        return parent::getValueForEmail($value, $notification, $element) . ':legacy';
    }
}

class PublicApiCanonicalTemplateField extends Field
{
    public static function getInputTemplatePath(): string { return 'module/input'; }
    public static function getReferenceBlockTemplatePath(): string { return 'module/reference'; }
}

class PublicApiLegacyTemplateField extends Field
{
    public static function getFrontEndInputTemplatePath(): string { return parent::getFrontEndInputTemplatePath() . '/custom'; }
    public static function getEmailTemplatePath(): string { return parent::getEmailTemplatePath() . '/custom'; }
}

it('supports schema parent calls on base and native subclasses without recursion', function (): void {
    $base = new PublicApiLegacySchemaField();
    expect($base->defineFormBuilderGeneralSchema())->toBe([['name' => 'custom']])
        ->and($base->calls)->toBe(1);
    $native = new PublicApiLegacyTextField(['limit' => true, 'maxType' => 'words', 'max' => 3]);
    $schema = FieldCompatibility::resolveSchema($native, 'defineFormBuilderGeneralSchema');
    expect(end($schema))->toBe(['name' => 'custom'])
        ->and(count($schema))->toBeGreaterThan(1)
        ->and($native->limit)->toBeTrue()
        ->and($native->maxType)->toBe('words');
});

it('preserves stable reference signatures with notification context and one event', function (): void {
    $submission = new Submission();
    $notification = new Notification(['name' => 'Receipt']);
    foreach ([PublicApiLegacyReferenceField::class, PublicApiLegacyProtectedReferenceField::class] as $class) {
        $field = new $class();
        $events = 0;
        $field->on(Field::EVENT_MODIFY_VALUE_FOR_REFERENCE, function () use (&$events) { ++$events; });
        $value = FieldCompatibility::withReferenceNotification($field, $notification, fn() => $field->getValueForReference('value', $submission));
        expect($value)->toBe('value:Receipt')->and($events)->toBe(1);
        expect($field->getValueForReference('value', $submission))->toBe('value');
    }
    $field = new PublicApiLegacyReferenceField();
    expect($field->getValueForVariable('direct', $submission, $notification))->toBe('direct:Receipt');
    $canonical = new PublicApiCanonicalReferenceField();
    expect(FieldCompatibility::withReferenceNotification($canonical, $notification, fn() => $canonical->getValueForReference('value', $submission)))->toBe('canonical')
        ->and($canonical->calls)->toBe(0);
});

it('adapts old block projections and template aliases in both directions', function (): void {
    $field = new PublicApiLegacyBlockField();
    $events = 0;
    $field->on(Field::EVENT_MODIFY_VALUE_FOR_REFERENCE_BLOCK, function () use (&$events) { ++$events; });
    expect($field->getValueForReferenceBlock('value', new Notification(), new Submission()))->toBe('value:legacy')
        ->and($events)->toBe(1)
        ->and(PublicApiCanonicalTemplateField::getFrontEndInputTemplatePath())->toBe('module/input')
        ->and(PublicApiCanonicalTemplateField::getEmailTemplatePath())->toBe('module/reference')
        ->and(PublicApiLegacyTemplateField::getInputTemplatePath())->toEndWith('/custom')
        ->and(PublicApiLegacyTemplateField::getReferenceBlockTemplatePath())->toEndWith('/custom');
});

it('normalizes renamed settings with canonical precedence and supports old PHP properties', function (): void {
    foreach ([
        [fields\Phone::class, 'countryAllowed', 'allowedCountryCodes', ['AU'], ['NZ']],
        [fields\Address::class, 'countryAllowed', 'allowedCountryCodes', ['AU'], ['NZ']],
        [fields\FileUpload::class, 'sizeLimit', 'maxFileSize', '10', '20'],
        [fields\FileUpload::class, 'sizeMinLimit', 'minFileSize', '1', '2'],
        [fields\FileUpload::class, 'limitFiles', 'maxFiles', '3', '4'],
        [fields\Entries::class, 'limitOptions', 'optionLimit', '3', '4'],
        [fields\Repeater::class, 'addLabel', 'addRowLabel', 'Add old', 'Add new'],
    ] as [$class, $old, $new, $value, $other]) {
        $field = new $class([$old => $value]);
        expect($field->$new, $class)->toBe($value)
            ->and($field->$old)->toBe($value)
            ->and($field->canGetProperty($old))->toBeTrue()
            ->and($field->canSetProperty($old))->toBeTrue();
        $field->$old = $other;
        expect($field->$new)->toBe($other);
        $field = new $class([$old => $value, $new => $other]);
        expect($field->$new)->toBe($other);
        $field->setAttributes([$old => $other, $new => $value], false);
        expect($field->$new)->toBe($value)
            ->and($field->getSettings())->toHaveKey($new)
            ->and($field->getSettings())->not->toHaveKey($old);
    }
});

it('preserves setter-backed context on nested presentation fields', function (): void {
    $parent = new fields\Group(['handle' => 'group', 'uid' => 'group-uid']);
    foreach ([fields\Survey::class, fields\Quiz::class, fields\Recipients::class, fields\Entries::class] as $class) {
        $field = new $class(['handle' => 'choice', 'uid' => 'choice-uid', 'displayType' => 'radio']);
        $field->setParentField($parent);
        $field->setNamespace('fields[group][row]');
        $presentation = $field->getDisplayTypeField();
        expect($presentation->getParentField())->toBe($parent)
            ->and($presentation->getNamespace())->toBe($field->getNamespace())
            ->and($presentation->getHtmlName())->toBe($field->getHtmlName())
            ->and($presentation->valueKey())->toBe($field->valueKey())
            ->and($presentation->errorKey())->toBe($field->errorKey());
        $repeater = new fields\Repeater(['handle' => 'people']);
        $field->setParentField($repeater);
        $field->setNamespace('fields[people][2]');
        $nested = $field->getDisplayTypeField();
        expect($nested->getParentField())->toBe($repeater)
            ->and($nested->getNamespace())->toBe('fields[people][2]')
            ->and($nested->getHtmlName())->toBe($field->getHtmlName())
            ->and($nested->valueKey())->toBe($field->valueKey());
    }
});

it('keeps natural option data scalar and recipient values independent of widgets', function (): void {
    foreach ([fields\Radio::class, fields\Checkboxes::class] as $class) {
        $field = new $class(['options' => [['label' => 'Alpha', 'value' => 'a']]]);
        $input = $field->multi ? ['a', 'invalid'] : 'a';
        $value = $field->normalizeFieldValue($input);
        expect($field->getValueAsData($value))->toBe($input)
            ->and($value->toArray())->toBeArray();
    }
    foreach (['dropdown', 'radio', 'checkboxes', 'hidden'] as $display) {
        $field = new fields\Recipients(['displayType' => $display, 'options' => [['label' => 'Sales', 'value' => 'sales@example.test']]]);
        $value = $field->normalizeFieldValue($display === 'checkboxes' ? ['sales@example.test'] : 'sales@example.test');
        expect($value)->toBeInstanceOf(RecipientsFieldValue::class)
            ->and(method_exists($value, 'displayType'))->toBeFalse()
            ->and($field->getSettings())->not->toHaveKey('multiple')
            ->and($field->getMultiple())->toBe($display === 'checkboxes');
        expect($field->getValueAsData($value))->toBe($display === 'checkboxes' ? ['sales@example.test'] : 'sales@example.test');
        expect(fn() => $value->value = 'mutated')->toThrow(Error::class);
    }
});

it('keeps the documented hook family and visibility coherent', function (): void {
    foreach (['General', 'Settings', 'Validation', 'Appearance', 'Advanced', 'Conditions'] as $tab) {
        expect(method_exists(FieldInterface::class, 'defineFormBuilder' . $tab . 'Schema'))->toBeTrue();
    }
    expect((new ReflectionMethod(fields\Quiz::class, 'defineClientRenderedInput'))->isProtected())->toBeTrue()
        ->and(fields\Radio::getShortClassName())->toBe('Radio')
        ->and(fields\Radio::className())->toBe('Radio');
});

class PublicApiLegacyClassName extends Field
{
    public static int $nameCalls = 0;

    public static function className(): string
    {
        if (++static::$nameCalls > 1) {
            throw new RuntimeException('Recursive legacy class name dispatch.');
        }
        return parent::className() . 'Alias';
    }
}

class PublicApiInheritedLegacyClassName extends PublicApiLegacyClassName {}

class PublicApiNativeLegacyClassName extends fields\SingleLineText
{
    public static function className(): string { return 'SharedWidget'; }
}

class PublicApiCanonicalClassName extends PublicApiLegacyClassName
{
    public static function getShortClassName(): string { return parent::getShortClassName() . 'Canonical'; }
}

class PublicApiSameClassName extends Field
{
    public static function className(): string { throw new RuntimeException('Canonical name must take precedence.'); }
    public static function getShortClassName(): string { return parent::getShortClassName() . 'Canonical'; }
}

class PublicApiOnlyCanonicalClassName extends Field
{
    public static function getShortClassName(): string { return parent::getShortClassName() . 'Canonical'; }
}

class PublicApiThrowingClassName extends Field
{
    public static function className(): string { throw new RuntimeException('Legacy name failed.'); }
}

it('preserves legacy class names in derived handles templates and theme keys', function (): void {
    foreach ([
        PublicApiLegacyClassName::class => 'PublicApiLegacyClassNameAlias',
        PublicApiInheritedLegacyClassName::class => 'PublicApiInheritedLegacyClassNameAlias',
        PublicApiNativeLegacyClassName::class => 'SharedWidget',
    ] as $class => $name) {
        $handle = \craft\helpers\StringHelper::toKebabCase($name);
        $field = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        $calls = [
            'className' => $name,
            'getShortClassName' => $name,
            'kebabClassName' => $handle,
            'lowerClassName' => strtolower($name),
            'getInputTemplatePath' => 'fields/' . $handle,
            'getReferenceBlockTemplatePath' => 'fields/' . $handle,
            'getFrontEndInputTemplatePath' => 'fields/' . $handle,
            'getEmailTemplatePath' => 'fields/' . $handle,
        ];
        foreach ($calls as $method => $expected) {
            PublicApiLegacyClassName::$nameCalls = 0;
            expect($class::$method())->toBe($expected);
            if (is_a($class, PublicApiLegacyClassName::class, true)) {
                expect($class::$nameCalls)->toBe(1);
            }
        }
        PublicApiLegacyClassName::$nameCalls = 0;
        expect($field->themeConfigKey())->toBe(\craft\helpers\StringHelper::toCamelCase($handle));
    }
});

it('prefers same-class and more-derived canonical class names including parent calls', function (): void {
    PublicApiLegacyClassName::$nameCalls = 0;
    foreach ([PublicApiCanonicalClassName::class, PublicApiSameClassName::class, PublicApiOnlyCanonicalClassName::class] as $class) {
        $name = (new ReflectionClass($class))->getShortName() . 'Canonical';
        $handle = \craft\helpers\StringHelper::toKebabCase($name);
        $field = (new ReflectionClass($class))->newInstanceWithoutConstructor();
        expect($class::getShortClassName())->toBe($name)
            ->and($class::kebabClassName())->toBe($handle)
            ->and($class::lowerClassName())->toBe(strtolower($name))
            ->and($class::getInputTemplatePath())->toBe('fields/' . $handle)
            ->and($class::getReferenceBlockTemplatePath())->toBe('fields/' . $handle)
            ->and($field->themeConfigKey())->toBe(\craft\helpers\StringHelper::toCamelCase($handle));
    }
    expect(PublicApiLegacyClassName::$nameCalls)->toBe(0)
        ->and(PublicApiOnlyCanonicalClassName::className())->toBe('PublicApiOnlyCanonicalClassNameCanonical');
});

it('clears the legacy class name guard after exceptions', function (): void {
    for ($attempt = 0; $attempt < 2; ++$attempt) {
        expect(fn() => PublicApiThrowingClassName::getShortClassName())->toThrow(RuntimeException::class, 'Legacy name failed.')
            ->and(FieldCompatibility::isCalling(PublicApiThrowingClassName::class, 'className'))->toBeFalse();
    }
    expect(fields\Radio::getShortClassName())->toBe('Radio');
});

class PublicApiLegacyOptionDataField extends fields\Radio
{
    protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed
    {
        return ['legacy' => parent::defineValueAsJson($value, $element)];
    }
}

it('dispatches legacy data hooks on native subclasses without losing their parent projection', function (): void {
    $field = new PublicApiLegacyOptionDataField(['options' => [['label' => 'Alpha', 'value' => 'a']]]);
    $events = 0;
    $field->on(Field::EVENT_MODIFY_VALUE_AS_DATA, function () use (&$events) { ++$events; });
    expect($field->getValueAsData($field->normalizeFieldValue('a')))->toBe(['legacy' => 'a'])
        ->and($events)->toBe(1);
});

it('makes legacy properties visible to Twig and keeps runtime settings canonical', function (): void {
    $field = new fields\Phone(['allowedCountryCodes' => ['AU']]);
    expect(isset($field->countryAllowed))->toBeTrue()
        ->and(Craft::$app->getView()->renderString('{{ field.countryAllowed|join(",") }}', ['field' => $field]))->toBe('AU');
    $field = new fields\Repeater();
    $settings = (new verbb\formie\services\RuntimeConfiguration())->validateSettings($field, ['addLabel' => 'Add person'], ['addRowLabel'], 'repeater');
    expect($settings)->toBe(['addRowLabel' => 'Add person']);
});

it('supports direct legacy schema calls and normalizes persisted field defaults', function (): void {
    $field = new PublicApiLegacySchemaField();
    expect($field->defineGeneralSchema())->toBe([['name' => 'custom']])->and($field->calls)->toBe(1);
    $defaults = verbb\formie\Formie::$plugin->getFormDefaults();
    expect($defaults->normalizeFieldDefaultsForStorage([fields\Entries::class => ['limitOptions' => '12']]))
        ->toBe([fields\Entries::class => ['optionLimit' => '12']]);
});

class PublicApiLegacyPresentationField extends Field
{
    public function hasEmailLabel(): bool { return !parent::hasEmailLabel(); }
    public function hasEmailPlaceholder(): bool { return !parent::hasEmailPlaceholder(); }
    public function getEmailOptions(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): array
    {
        return [...parent::getEmailOptions($submission, $notification, $value, $renderOptions), 'legacy' => true];
    }
}

it('adapts legacy presentation parent calls without recursion', function (): void {
    $field = new PublicApiLegacyPresentationField();
    $base = new class extends Field {};
    expect($field->hasReferenceBlockLabel())->toBe(!$base->hasReferenceBlockLabel())
        ->and($field->hasEmailLabel())->toBe(!$base->hasReferenceBlockLabel())
        ->and($field->hasReferenceBlockPlaceholder())->toBe(!$base->hasReferenceBlockPlaceholder())
        ->and($field->hasEmailPlaceholder())->toBe(!$base->hasReferenceBlockPlaceholder());
    expect($field->getReferenceBlockOptions(new Submission(), new Notification(), 'value'))->toHaveKey('legacy', true);
});

it('retains deprecated graphql setting names with canonical values', function (): void {
    $gql = Craft::$app->getGql();
    try { $previous = $gql->getActiveSchema(); } catch (craft\errors\GqlException) { $previous = null; }
    $gql->flushCaches();
    try {
        $gql->setActiveSchema(new craft\models\GqlSchema(['scope' => ['formieForms.all:read']]));
        $types = verbb\formie\gql\types\generators\FieldGenerator::generateTypes();
        foreach ([
            [new fields\FileUpload(['maxFileSize' => '12']), 'sizeLimit', 'maxFileSize'],
            [new fields\Repeater(['addRowLabel' => 'Add person']), 'addLabel', 'addRowLabel'],
            [new fields\Phone(['allowedCountryCodes' => ['AU']]), 'countryAllowed', 'allowedCountryCodes'],
            [new fields\Entries(['optionLimit' => '3']), 'limitOptions', 'optionLimit'],
        ] as [$field, $legacy, $canonical]) {
            $definitions = $types[$field->getGqlTypeName()]->getFields();
            expect($definitions)->toHaveKeys([$legacy, $canonical]);
            expect($definitions[$legacy]->deprecationReason)->toBe('Use ' . $canonical . ' instead.');
            $resolve = $definitions[$legacy]->resolveFn;
            $canonicalResolve = $definitions[$canonical]->resolveFn;
            $expected = $canonicalResolve ? $canonicalResolve($field, [], null, null) : $field->$canonical;
            expect($resolve($field, [], null, null))->toBe($expected);
        }
    } finally {
        $gql->setActiveSchema($previous);
        $gql->flushCaches();
    }
});


it('projects empty multi selections as lists and empty single selections as null', function (): void {
    foreach ([new fields\Checkboxes(), new fields\Dropdown(['multi' => true]), new fields\Recipients(['displayType' => 'checkboxes'])] as $field) {
        expect($field->getValueAsData(null))->toBe([]);
    }
    foreach ([new fields\Radio(), new fields\Dropdown(), new fields\Recipients(['displayType' => 'radio'])] as $field) {
        expect($field->getValueAsData(null))->toBeNull();
    }
});

it('preserves renamed inherited settings and extension-owned configuration', function (): void {
    $phone = new class(['countryAllowed' => ['AU']]) extends fields\Phone {};
    expect($phone->allowedCountryCodes)->toBe(['AU'])->and($phone->countryAllowed)->toBe(['AU']);
    $extension = new class(['limit' => 'custom', 'maxType' => 'custom', 'limitType' => 'custom', 'limitAmount' => 7]) extends Field {
        public string $limit = '';
        public string $maxType = '';
        public string $limitType = '';
        public int $limitAmount = 0;
    };
    expect($extension->limit)->toBe('custom')->and($extension->maxType)->toBe('custom')
        ->and($extension->limitType)->toBe('custom')->and($extension->limitAmount)->toBe(7);
});


it('migrates stored beta quiz configuration without retaining its old public property', function (): void {
    $quiz = new fields\Quiz(['fieldType' => 'checkboxes']);
    expect($quiz->displayType)->toBe('checkboxes')->and($quiz->canGetProperty('fieldType'))->toBeFalse();
    $quiz = new fields\Quiz(['fieldType' => 'checkboxes', 'displayType' => 'radio']);
    expect($quiz->displayType)->toBe('radio')->and($quiz->getSettings())->not->toHaveKey('fieldType');
});

class PublicApiLegacyPhoneDefaults extends fields\Phone
{
    public array $countryAllowed = ['AU'];
}

class PublicApiCanonicalPhoneDefaults extends PublicApiLegacyPhoneDefaults
{
    public array $allowedCountryCodes = ['GB'];
}

class PublicApiSameClassPhoneDefaults extends fields\Phone
{
    public array $countryAllowed = ['AU'];
    public array $allowedCountryCodes = ['GB'];
}

class PublicApiLegacyAddressDefaults extends fields\Address
{
    public array $countryAllowed = ['AU'];
}

class PublicApiLegacyEntriesDefaults extends fields\Entries
{
    public ?string $limitOptions = '3';
}

class PublicApiLegacyUploadDefaults extends fields\FileUpload
{
    public ?string $sizeLimit = '1';
    public ?string $sizeMinLimit = '0.1';
    public ?string $limitFiles = '2';
}

class PublicApiLegacyRepeaterDefaults extends fields\Repeater
{
    public ?string $addLabel = 'Add person';
}

it('preserves declared legacy defaults and writes without sharing cloned settings', function (): void {
    $phone = new PublicApiLegacyPhoneDefaults();
    expect($phone->allowedCountryCodes)->toBe(['AU']);
    $phone->countryAllowed[] = 'NZ';
    expect($phone->allowedCountryCodes)->toBe(['AU', 'NZ']);
    $phone->allowedCountryCodes = ['CA'];
    expect($phone->countryAllowed)->toBe(['CA']);

    $clone = clone $phone;
    $clone->countryAllowed[] = 'US';
    expect($clone->allowedCountryCodes)->toBe(['CA', 'US'])
        ->and($phone->countryAllowed)->toBe(['CA']);
    $clone->setAttributes(['countryAllowed' => ['NZ'], 'allowedCountryCodes' => ['GB']], false);
    expect($clone->countryAllowed)->toBe(['GB'])->and($phone->allowedCountryCodes)->toBe(['CA']);
    $restored = unserialize(serialize($phone));
    $restored->countryAllowed[] = 'AU';
    expect($restored->allowedCountryCodes)->toBe(['CA', 'AU'])->and($phone->allowedCountryCodes)->toBe(['CA']);

    $upload = new PublicApiLegacyUploadDefaults();
    expect($upload->maxFileSize)->toBe('1')->and($upload->minFileSize)->toBe('0.1')->and($upload->maxFiles)->toBe('2')
        ->and($upload->exceedsMaxUploadSize(2000000))->toBeTrue();
    $upload->sizeLimit = '3';
    expect($upload->exceedsMaxUploadSize(2000000))->toBeFalse()->and($upload->maxFiles)->toBe('2')->and($upload->minFileSize)->toBe('0.1');
    $uploadClone = clone $upload;
    $uploadClone->maxFiles = '5';
    $uploadClone->sizeLimit = null;
    expect($uploadClone->limitFiles)->toBe('5')->and($uploadClone->maxFileSize)->toBeNull()
        ->and($upload->limitFiles)->toBe('2')->and($upload->sizeLimit)->toBe('3');

    expect((new PublicApiCanonicalPhoneDefaults())->countryAllowed)->toBe(['GB'])
        ->and((new PublicApiSameClassPhoneDefaults())->countryAllowed)->toBe(['GB'])
        ->and((new PublicApiLegacyPhoneDefaults(['countryAllowed' => ['NZ']]))->allowedCountryCodes)->toBe(['NZ'])
        ->and((new PublicApiLegacyPhoneDefaults(['countryAllowed' => ['NZ'], 'allowedCountryCodes' => ['US']]))->countryAllowed)->toBe(['US']);
    $repeater = new PublicApiLegacyRepeaterDefaults();
    expect($repeater->addRowLabel)->toBe('Add person')
        ->and((new PublicApiLegacyRepeaterDefaults(['addLabel' => 'Add old', 'addRowLabel' => 'Add new']))->addLabel)->toBe('Add new');
    $address = new PublicApiLegacyAddressDefaults();
    $address->countryAllowed[] = 'NZ';
    expect($address->allowedCountryCodes)->toBe(['AU', 'NZ']);
    $entries = new PublicApiLegacyEntriesDefaults();
    expect($entries->optionLimit)->toBe('3');
    $entries->limitOptions = '7';
    expect($entries->optionLimit)->toBe('7');
    $entries->optionLimit = null;
    expect($entries->limitOptions)->toBeNull();
    $runtime = (new \verbb\formie\services\RuntimeConfiguration())->validateSettings($repeater, ['addLabel' => 'Add runtime'], ['addRowLabel'], 'repeater');
    expect($runtime)->toBe(['addRowLabel' => 'Add runtime'])->and($repeater->addLabel)->toBe('Add person');
    foreach ([$phone, $upload, $repeater, $address, $entries] as $field) {
        foreach (\verbb\formie\compatibility\fields\FieldConfigNormalizer::settingAliases($field::class) as $old => $new) {
            expect($field->getSettings())->not->toHaveKey($old);
        }
    }
});

class PublicApiInheritedDropdown extends fields\Dropdown {}
class PublicApiInheritedRadio extends fields\Radio {}
class PublicApiInheritedCheckboxes extends fields\Checkboxes {}
class PublicApiInheritedRecipients extends fields\Recipients {}

class PublicApiRecipientSourceIntegration extends \verbb\formie\integrations\automations\WebRequest
{
    public static function getOptionSourceDefinitions(): array
    {
        return [['handle' => 'example', 'label' => 'Example', 'optionSourceUsages' => ['options', 'recipients']]];
    }
}

it('retains integration option sources through native subclass construction and persistence', function (): void {
    $previous = \verbb\formie\Formie::$plugin->getIntegrations();
    $service = new class extends \verbb\formie\services\Integrations {
        public function getAllIntegrationTypes(): array { return ['automations' => [PublicApiRecipientSourceIntegration::class]]; }
    };
    \verbb\formie\Formie::$plugin->set('integrations', $service);
    try {
        $source = ['type' => 'integration', 'provider' => 'example', 'params' => ['listId' => 'list-1']];
        foreach ([PublicApiInheritedDropdown::class, PublicApiInheritedRadio::class, PublicApiInheritedCheckboxes::class, PublicApiInheritedRecipients::class] as $class) {
            $field = new $class(['optionsMode' => 'dynamic', 'optionSource' => $source]);
            expect($field->optionsMode)->toBe('dynamic')->and($field->optionSource)->toBe($source);
            $restored = new $class($field->getSettings());
            expect($restored->optionsMode)->toBe('dynamic')->and($restored->optionSource)->toBe($source);
        }
        $allowed = \verbb\formie\options\OptionSourceConfigHelper::allowedTypesForFieldClass(PublicApiInheritedRecipients::class);
        expect($allowed)->toBe(['provider', 'integration']);
        $field = new PublicApiInheritedRecipients(['optionsMode' => 'dynamic', 'optionSource' => ['type' => 'predefined', 'provider' => 'countries']]);
        expect($field->optionsMode)->toBe('static')->and($field->optionSource)->toBeNull();
    } finally {
        \verbb\formie\Formie::$plugin->set('integrations', $previous);
    }
});

class PublicApiLegacyTextData extends fields\SingleLineText
{
    public int $legacyCalls = 0;

    protected function defineValueAsJson(mixed $value, ?ElementInterface $element = null): mixed
    {
        ++$this->legacyCalls;
        return ['legacy' => parent::defineValueAsJson($value, $element)];
    }
}

class PublicApiCanonicalTextData extends PublicApiLegacyTextData
{
    protected function defineValueAsData(mixed $value, ?ElementInterface $element = null): mixed
    {
        return ['canonical' => $value];
    }
}

it('resolves legacy data hooks for integration and condition consumers without extra projection events', function (): void {
    $integration = new \verbb\formie\integrations\automations\WebRequest();
    $target = new \verbb\formie\models\IntegrationField(['type' => 'array']);
    foreach ([PublicApiLegacyTextData::class => 'legacy', PublicApiCanonicalTextData::class => 'canonical'] as $class => $key) {
        $field = new $class();
        $dataEvents = $integrationEvents = 0;
        $field->on(Field::EVENT_MODIFY_VALUE_AS_DATA, function () use (&$dataEvents) { ++$dataEvents; });
        $field->on(Field::EVENT_MODIFY_VALUE_FOR_INTEGRATION, function () use (&$integrationEvents) { ++$integrationEvents; });
        expect($field->getValueAsData('a'))->toBe([$key => 'a'])->and($dataEvents)->toBe(1);
        expect($field->getValueForIntegration('a', $target, $integration))->toBe([$key => 'a'])
            ->and($dataEvents)->toBe(1)->and($integrationEvents)->toBe(1);
        // Text conditions intentionally use their dedicated scalar projection.
        expect($field->getValueForCondition('a', new Submission()))->toBe('a')
            ->and($dataEvents)->toBe(1)->and($integrationEvents)->toBe(1)
            ->and($field->legacyCalls)->toBe($key === 'legacy' ? 2 : 0);
    }
});

class PublicApiLegacyTableData extends fields\Table
{
    protected function defineValueAsJson(mixed $value, ?ElementInterface $element = null): mixed
    {
        return ['legacy' => parent::defineValueAsJson($value, $element)];
    }
}

it('honors legacy data hooks when a native field reuses data for conditions and browser input', function (): void {
    $field = new PublicApiLegacyTableData(['columns' => ['col1' => ['handle' => 'name', 'type' => 'singleline']]]);
    $value = [['col1' => 'Alice']];
    $expected = ['legacy' => [['name' => 'Alice']]];
    $events = 0;
    $field->on(Field::EVENT_MODIFY_VALUE_AS_DATA, function () use (&$events) { ++$events; });
    expect($field->getValueForCondition($value, new Submission()))->toBe($expected)
        ->and($field->serializeValueForClientInput($value))->toBe($expected)->and($events)->toBe(0)
        ->and($field->getValueAsData($value))->toBe($expected)->and($events)->toBe(1);
});

class PublicApiLegacyCountryTemplates extends fields\subfields\AddressCountry
{
    public static function getFrontEndInputTemplatePath(): string { return parent::getFrontEndInputTemplatePath() . '/custom'; }
    public static function getEmailTemplatePath(): string { return parent::getEmailTemplatePath() . '/custom'; }
}

class PublicApiCanonicalCountryTemplates extends PublicApiLegacyCountryTemplates
{
    public static function getInputTemplatePath(): string { return 'module/input'; }
    public static function getReferenceBlockTemplatePath(): string { return 'module/reference'; }
}

class PublicApiLegacyBuilderTemplate extends \verbb\formie\base\BuilderField
{
    public static function getFrontEndInputTemplatePath(): string { return parent::getFrontEndInputTemplatePath() . 'module/builder'; }
}

it('keeps native template defaults behind legacy dispatch and canonical override precedence', function (): void {
    expect(PublicApiLegacyCountryTemplates::getInputTemplatePath())->toBe('fields/dropdown/custom')
        ->and(PublicApiLegacyCountryTemplates::getReferenceBlockTemplatePath())->toBe('fields/dropdown/custom')
        ->and(PublicApiLegacyCountryTemplates::getFrontEndInputTemplatePath())->toBe('fields/dropdown/custom')
        ->and(PublicApiLegacyCountryTemplates::getEmailTemplatePath())->toBe('fields/dropdown/custom')
        ->and(PublicApiCanonicalCountryTemplates::getInputTemplatePath())->toBe('module/input')
        ->and(PublicApiCanonicalCountryTemplates::getReferenceBlockTemplatePath())->toBe('module/reference')
        ->and(PublicApiLegacyBuilderTemplate::getInputTemplatePath())->toBe('module/builder');
    $field = new PublicApiLegacyCountryTemplates();
    expect((new ReflectionMethod($field, 'defineInputTemplatePath'))->invoke($field))->toBe('fields/dropdown/custom');
    foreach ([
        fields\subfields\AddressCountry::class => 'fields/dropdown',
        fields\subfields\NamePrefix::class => 'fields/dropdown',
        fields\subfields\DateMonthDropdown::class => 'fields/dropdown',
        fields\subfields\NameFirst::class => 'fields/single-line-text',
        fields\subfields\Address1::class => 'fields/single-line-text',
    ] as $class => $path) {
        expect($class::getInputTemplatePath())->toBe($path)->and($class::getReferenceBlockTemplatePath())->toBe($path);
    }
    expect(fields\MissingField::getInputTemplatePath())->toBe('')
        ->and(fields\CustomField::getInputTemplatePath())->toBe('fields/custom-field');
});

it('uses selected scalar data for option subfields while retaining labels for text and export', function (): void {
    foreach ([
        [new fields\subfields\NamePrefix(['options' => [['value' => 'dr', 'label' => 'Doctor']]]), 'dr', 'Doctor'],
        [new fields\subfields\AddressCountry(['optionValue' => 'short', 'optionLabel' => 'full']), 'AU', 'Australia'],
    ] as [$field, $selection, $label]) {
        $value = $field->normalizeFieldValue($selection);
        expect($field->getValueAsData($value))->toBe($selection)
            ->and($field->getValueAsString($value))->toBe($label)
            ->and($field->getValueForExport($value))->toBe($label)
            ->and($field->getValueAsData($field->normalizeFieldValue(null)))->toBeNull()
            ->and($field->getValueAsData($field->normalizeFieldValue('invalid')))->toBe('invalid');
    }
});

it('preserves array mutations on native legacy country properties and independent clones', function (): void {
    foreach ([fields\Phone::class, fields\Address::class] as $class) {
        $field = new $class(['allowedCountryCodes' => ['AU']]);
        $field->countryAllowed[] = 'NZ';
        unset($field->countryAllowed[0]);
        array_push($field->countryAllowed, 'GB');
        expect(array_values($field->allowedCountryCodes))->toBe(['NZ', 'GB']);
        $clone = clone $field;
        $clone->countryAllowed[] = 'US';
        expect(array_values($clone->allowedCountryCodes))->toBe(['NZ', 'GB', 'US'])
            ->and(array_values($field->allowedCountryCodes))->toBe(['NZ', 'GB']);
        $restored = unserialize(serialize($field));
        $restored->countryAllowed[] = 'CA';
        expect(array_values($restored->allowedCountryCodes))->toBe(['NZ', 'GB', 'CA'])
            ->and(array_values($field->allowedCountryCodes))->toBe(['NZ', 'GB']);
        $field->allowedCountryCodes = ['DE'];
        expect($field->countryAllowed)->toBe(['DE'])
            ->and($field->getSettings())->not->toHaveKey('countryAllowed');
    }
});

trait PublicApiLegacyNativeEmailHtml
{
    public int $legacyHtmlCalls = 0;

    public function getEmailHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        if (++$this->legacyHtmlCalls > 1) {
            throw new RuntimeException('Recursive legacy HTML dispatch.');
        }
        return (string)parent::getEmailHtml($submission, $notification, $value, $renderOptions) . ':legacy';
    }
}

class PublicApiLegacySectionHtml extends fields\Section { use PublicApiLegacyNativeEmailHtml; }
class PublicApiLegacyContentHtml extends fields\Content { use PublicApiLegacyNativeEmailHtml; }
class PublicApiLegacyHtmlHtml extends fields\Html { use PublicApiLegacyNativeEmailHtml; }
class PublicApiLegacyPasswordHtml extends fields\Password { use PublicApiLegacyNativeEmailHtml; }
class PublicApiLegacySummaryHtml extends fields\Summary { use PublicApiLegacyNativeEmailHtml; }
class PublicApiLegacyMissingHtml extends fields\MissingField { use PublicApiLegacyNativeEmailHtml; }
class PublicApiCanonicalSectionHtml extends PublicApiLegacySectionHtml
{
    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return (string)parent::getReferenceBlockHtml($submission, $notification, $value, $renderOptions) . ':canonical';
    }
}
class PublicApiSameClassSectionHtml extends fields\Section
{
    use PublicApiLegacyNativeEmailHtml;
    public function getReferenceBlockHtml(Submission $submission, Notification $notification, mixed $value, array $renderOptions = []): string|null|bool
    {
        return (string)parent::getReferenceBlockHtml($submission, $notification, $value, $renderOptions) . ':canonical';
    }
}
class PublicApiLegacyAgreePlaceholder extends fields\Agree
{
    public function hasEmailPlaceholder(): bool { return !parent::hasEmailPlaceholder(); }
}
class PublicApiLegacyCosmeticLabels extends fields\Section
{
    public function hasEmailLabel(): bool { return !parent::hasEmailLabel(); }
    public function hasEmailPlaceholder(): bool { return !parent::hasEmailPlaceholder(); }
}
class PublicApiCanonicalAgreePlaceholder extends PublicApiLegacyAgreePlaceholder
{
    public function hasReferenceBlockPlaceholder(): bool { return parent::hasReferenceBlockPlaceholder(); }
}

it('dispatches legacy notification rendering on every native HTML override with bounded parent calls', function (): void {
    $form = new \verbb\formie\elements\Form();
    $submission = new Submission();
    $submission->setForm($form);
    $notification = new Notification();
    foreach ([
        [PublicApiLegacySectionHtml::class, fields\Section::class, []],
        [PublicApiLegacyContentHtml::class, fields\Content::class, ['content' => \verbb\formie\models\RichText::from('<p>Content</p>')]],
        [PublicApiLegacyHtmlHtml::class, fields\Html::class, ['htmlContent' => '<p>HTML</p>']],
        [PublicApiLegacyPasswordHtml::class, fields\Password::class, []],
        [PublicApiLegacySummaryHtml::class, fields\Summary::class, []],
        [PublicApiLegacyMissingHtml::class, fields\MissingField::class, []],
    ] as [$legacyClass, $nativeClass, $config]) {
        $native = new $nativeClass($config);
        $expected = (string)$native->getReferenceBlockHtml($submission, $notification, null) . ':legacy';
        foreach (['getReferenceBlockHtml', 'getEmailHtml'] as $method) {
            $field = new $legacyClass($config);
            expect($field->$method($submission, $notification, null))->toBe($expected)
                ->and($field->legacyHtmlCalls)->toBe(1);
        }
    }
    foreach ([PublicApiCanonicalSectionHtml::class, PublicApiSameClassSectionHtml::class] as $class) {
        $field = new $class();
        expect($field->getReferenceBlockHtml($submission, $notification, null))->toBe('<hr>:canonical')
            ->and($field->legacyHtmlCalls)->toBe(0);
    }
});

it('dispatches native legacy notification label and placeholder overrides', function (): void {
    $agree = new PublicApiLegacyAgreePlaceholder();
    expect($agree->hasReferenceBlockPlaceholder())->toBeTrue()->and($agree->hasEmailPlaceholder())->toBeTrue();
    $cosmetic = new PublicApiLegacyCosmeticLabels();
    expect($cosmetic->hasReferenceBlockLabel())->toBeTrue()->and($cosmetic->hasEmailLabel())->toBeTrue()
        ->and($cosmetic->hasReferenceBlockPlaceholder())->toBeTrue()->and($cosmetic->hasEmailPlaceholder())->toBeTrue();
    expect((new PublicApiCanonicalAgreePlaceholder())->hasReferenceBlockPlaceholder())->toBeFalse();
});
