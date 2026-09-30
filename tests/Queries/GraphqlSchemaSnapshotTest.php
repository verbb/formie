<?php

use craft\models\GqlSchema;
use verbb\formie\Formie;
use verbb\formie\gql\arguments\SubmissionArguments;
use verbb\formie\gql\mutations\SubmissionMutation;
use verbb\formie\gql\types\generators\FormGenerator;
use verbb\formie\gql\types\generators\SubmissionGenerator;

class SnapshotExtensionField extends \verbb\formie\fields\SingleLineText
{
    public static int $calls = 0;
    public static function supportsGqlConfigProvider(): bool { return false; }
    public function getContentGqlType(): \GraphQL\Type\Definition\Type|array {
        self::$calls++;
        return \GraphQL\Type\Definition\Type::string();
    }
}

it('shares scoped metadata without loading inaccessible form layouts and invalidates after schema or field changes', function() {
    $register = static function($event) { $event->fields[] = SnapshotExtensionField::class; };
    \yii\base\Event::on(\verbb\formie\services\Fields::class, \verbb\formie\services\Fields::EVENT_REGISTER_FIELDS, $register);
    (new ReflectionMethod(Formie::$plugin->getFields(), '_resetFieldCaches'))->invoke(Formie::$plugin->getFields());
    SnapshotExtensionField::$calls = 0;
    $allowed = formie()->form()->singleLineTextField('allowedValue')->addField(SnapshotExtensionField::class, 'extensionValue')->create();
    $denied = formie()->form()->singleLineTextField('deniedValue')->create();
    $originalFields = Formie::$plugin->getFields();
    $fields = new class extends \verbb\formie\services\Fields {
        public array $configRequests = [];
        public array $layoutRequests = [];
        public function getAllFieldConfigsForForms(array $formIds): array {
            $this->configRequests[] = $formIds;
            return parent::getAllFieldConfigsForForms($formIds);
        }
        public function getLayoutsByIds(array $layoutIds): array {
            $this->layoutRequests[] = $layoutIds;
            return parent::getLayoutsByIds($layoutIds);
        }
    };
    $gql = Craft::$app->getGql();
    try { $originalSchema = $gql->getActiveSchema(); } catch (\craft\errors\GqlException) { $originalSchema = null; }
    $forms = Formie::$plugin->getForms();
    Formie::$plugin->set('fields', $fields);
    $forms->invalidateFormCaches();
    $gql->flushCaches();
    try {
        $schema = new GqlSchema(['scope' => ["formieForms.{$allowed->uid}:read", "formieSubmissions.{$allowed->uid}:read", "formieSubmissions.{$allowed->uid}:create"]]);
        $gql->setActiveSchema($schema);
        $snapshot = $forms->getGqlSchemaSnapshot();
        expect($snapshot)->toBe($forms->getGqlSchemaSnapshot());
        expect(SubmissionArguments::getContentArguments())->toHaveKey('allowedValue')->not->toHaveKey('deniedValue');
        foreach (FormGenerator::generateTypes() as $type) $type->getFields();
        foreach (SubmissionGenerator::generateTypes() as $type) $type->getFields();
        expect(SnapshotExtensionField::$calls)->toBeGreaterThan(0);
        expect(SubmissionMutation::getMutations())->toHaveCount(2);
        expect($fields->configRequests)->toBe([[(int)$allowed->id]])
            ->and(array_merge([], ...$fields->layoutRequests))->not->toContain((int)$denied->layoutId);
        $schema->scope = ["formieSubmissions.{$denied->uid}:read"];
        $other = $forms->getGqlSchemaSnapshot();
        expect($other)->not->toBe($snapshot)
            ->and(array_column($other->fieldConfigs($denied->id), 'handle'))->toBe(['deniedValue']);
        $reset = new ReflectionMethod($fields, '_resetFieldCaches');
        $reset->invoke($fields);
        expect($forms->getGqlSchemaSnapshot())->not->toBe($other);
    } finally {
        \yii\base\Event::off(\verbb\formie\services\Fields::class, \verbb\formie\services\Fields::EVENT_REGISTER_FIELDS, $register);
        Formie::$plugin->set('fields', $originalFields);
        (new ReflectionMethod($originalFields, '_resetFieldCaches'))->invoke($originalFields);
        $forms->invalidateFormCaches();
        $gql->setActiveSchema($originalSchema);
        $gql->flushCaches();
    }
});
