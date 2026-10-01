<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\ChildFieldInterface;
use verbb\formie\base\Field;
use verbb\formie\base\FixedParentField;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\fields\MissingField;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\FieldLayoutPageSettings;

use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;

use yii\base\InvalidConfigException;

/** Structural serialization and identity remapping shared by the distinct form lifecycle operations. */
class FormSerializer
{
    // Constants
    // =========================================================================

    public const SCHEMA_VERSION = 1;
    private const PROTECTED_SETTINGS = ['id', 'uid', 'reference', 'class', 'type', 'fieldId', 'syncId', 'definitionId', 'definitionUid', 'formId', 'siteId', 'sourceSiteId', 'layoutId', 'nestedLayoutId', 'pageId', 'rowId', 'parentId', 'parentField', 'layoutSaveContext', 'isSynced', 'usageCount'];


    // Properties
    // =========================================================================

    public array $warnings = [];
    public array $remaps = [];
    public array $missingTypes = [];
    public array $changes = ['added' => [], 'retained' => [], 'removed' => []];

    private array $_tokenHandles = [];
    private array $_existing = [];
    private array $_paths = [];
    private array $_used = [];
    private array $_builderIds = [];
    private array $_parentsById = [];
    private ?int $_targetFormId = null;


    // Public Methods
    // =========================================================================

    public function serializeLayout(FieldLayout $layout): array
    {
        return array_map(fn($page) => [
            'uid' => $page->uid,
            'label' => $page->label,
            'settings' => $page->getSettings(),
            'rows' => $this->_serializeRows($page->getRows()),
        ], $layout->getPages());
    }

    public function serializeField(Field $field): array
    {
        $settings = array_merge($field->getDefinitionSettings(), ['label' => $field->label, 'handle' => $field->handle]);
        unset($settings['nestedLayoutId']);

        if ($field instanceof ParentFieldInterface) {
            $settings['rows'] = $this->_serializeRows($field->getRows());
        }

        if ($field instanceof MissingField) {
            $settings = $field->getSettings();
        }

        return [
            'type' => $field instanceof MissingField ? $field->expectedType : $field->type,
            'uid' => $field->uid,
            'reference' => $field->reference,
            'definitionUid' => $field->definitionUid,
            'syncedDefinitionUid' => $field->getIsSynced() ? $field->definitionUid : null,
            'settings' => $settings,
            'instanceSettings' => $field->getInstanceSettings(),
        ];
    }

    public function recoverSettings(string $type, array $settings): array
    {
        $prototype = Formie::$plugin->getFields()->getRegisteredFieldByType($type);
        $keys = array_diff(array_merge($prototype->settingsAttributes(), ['label', 'handle']), self::PROTECTED_SETTINGS);
        $config = array_intersect_key($settings, array_flip($keys));

        if ($prototype instanceof ParentFieldInterface && isset($settings['rows'])) {
            $config['rows'] = $this->_rows($settings['rows'], 'duplicate', $prototype);
        }

        // Editor variants must cross the same boundary before they can become a live layout.
        if ($prototype instanceof FixedParentField && isset($settings['layouts'])) {
            foreach ($settings['layouts'] as $key => $rows) {
                $config['layouts'][$key] = $this->_rows($rows, 'duplicate', $prototype);
            }
        }

        return $config;
    }

    public function migrate(array $data): array
    {
        $version = $data['schemaVersion'] ?? 0;

        if (!is_int($version) || $version < 0 || $version > self::SCHEMA_VERSION) {
            throw new InvalidConfigException('Unsupported form document schemaVersion.');
        }

        // Legacy Formie 2/3/early 4 documents share the page/row tree. Normalize once here.
        while ($version < self::SCHEMA_VERSION) {
            if ($version === 0) {
                unset($data['fieldLayoutId'], $data['exportVersion']);
                $data['dependencies'] ??= [];
            }
            $version++;
        }
        $data['schemaVersion'] = $version;

        if (!is_array($data['pages'] ?? null)) {
            throw new InvalidConfigException('A form document must contain a pages array.');
        }

        return $data;
    }

    public function prepareImport(array $data, ?Form $existing = null): array
    {
        $data = $this->migrate($data);
        $this->_indexExisting($existing);
        $data['pages'] = $this->_pages($data['pages'], 'import');

        foreach ($this->_existing as $reference => $field) {
            if (!isset($this->_used[$field->id])) {
                $this->changes['removed'][] = $reference;
            }
        }
        $this->remap($data);

        return $data;
    }

    public function prepareCopy(array $data, string $operation): array
    {
        if (!in_array($operation, ['duplicate', 'stencil'], true)) {
            throw new InvalidConfigException('Unknown form copy operation.');
        }
        $data['pages'] = $this->_pages($data['pages'] ?? [], $operation);
        $this->remap($data);

        return $data;
    }

    public function hydrateBuilder(array $pages, Form $form): array
    {
        $this->_targetFormId = $form->id;
        $this->_indexExisting($form->id ? $form : null);

        return $this->_pages($pages, 'builder');
    }

    public function remap(mixed &$value): void
    {
        if (is_string($value)) {
            $value = preg_replace_callback('/\{field:[^}]+\}/', fn($match) => References::remapFieldReferenceToken($match[0], $this->remaps + $this->_tokenHandles), $value);
            // Conditions also store bare references. Replace complete identities only.
            $value = $this->remaps[$value] ?? $value;
        } elseif (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                $this->remap($item);
                $result[$this->remaps[$key] ?? $key] = $item;
            }
            $value = $result;
        }
    }


    // Private Methods
    // =========================================================================

    private function _serializeRows(array $rows): array
    {
        return array_map(fn($row) => ['fields' => array_map(fn($field) => $this->serializeField($field), $row->getFields())], $rows);
    }

    private function _indexExisting(?Form $form): void
    {
        if (!$form) {
            return;
        }
        $index = function(array $fields, string $prefix = '', ?int $parentId = null) use (&$index): void {
            foreach ($fields as $field) {
                $path = $prefix . $field->handle;
                $this->_existing[$field->reference] = $field;
                $this->_paths[$path] = $field;
                $this->_builderIds[$field->id] = $field;
                $this->_parentsById[$field->id] = $parentId;

                if ($field instanceof ParentFieldInterface) {
                    $index($field->getFields(), $path . '.', $field->id);
                }
            }
        };
        $index($form->getFields());
    }

    private function _pages(array $pages, string $operation): array
    {
        $result = [];

        foreach ($pages as $page) {
            if (!is_array($page) || !is_array($page['rows'] ?? [])) {
                throw new InvalidConfigException('Invalid form page.');
            }
            $uid = $operation === 'builder' ? ($page['uid'] ?? null) : StringHelper::UUID();

            if (!empty($page['uid']) && $uid !== $page['uid']) {
                $this->remaps[$page['uid']] = $uid;
            }
            $settings = Json::decodeIfJson($page['settings'] ?? []) ?: [];
            $result[] = [
                'id' => $operation === 'builder' && is_numeric($page['id'] ?? null) ? (int)$page['id'] : null,
                'uid' => $uid,
                'label' => $page['label'] ?? '',
                'settings' => array_intersect_key($settings, (new FieldLayoutPageSettings())->getAttributes()),
                'rows' => $this->_rows($page['rows'] ?? [], $operation),
            ];
        }

        return $result;
    }

    private function _rows(array $rows, string $operation, ?Field $parent = null, string $prefix = ''): array
    {
        $result = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !is_array($row['fields'] ?? [])) {
                throw new InvalidConfigException('Invalid form row.');
            }
            $fields = [];

            foreach ($row['fields'] ?? [] as $node) {
                $fields[] = $this->_field($node, $operation, $parent, $prefix);
            }
            $result[] = [
                'id' => $operation === 'builder' && is_numeric($row['id'] ?? null) ? (int)$row['id'] : null,
                'fields' => $fields,
            ];
        }

        return $result;
    }

    private function _field(array $node, string $operation, ?Field $parent, string $prefix): array
    {
        $service = Formie::$plugin->getFields();
        $type = $node['type'] ?? '';
        $settings = Json::decodeIfJson($node['settings'] ?? []) ?: [];
        $source = array_merge($settings, $node['instanceSettings'] ?? [], $node);
        $path = $prefix . ($source['handle'] ?? '');
        $existing = $operation === 'builder'
            ? ($this->_builderIds[$source['id'] ?? ''] ?? null)
            : ($this->_existing[$node['reference'] ?? ''] ?? $this->_paths[$path] ?? null);

        if ($operation === 'builder' && is_numeric($source['id'] ?? null) && !$existing) {
            throw new InvalidConfigException('The field instance does not belong to this form.');
        }

        if ($existing && isset($this->_used[$existing->id])) {
            throw new InvalidConfigException('A field instance can occur only once in a layout.');
        }

        if ($existing) {
            $this->_used[$existing->id] = true;
        }
        $prototype = is_string($type) ? $service->getRegisteredFieldByType($type) : null;

        if (!$prototype || $type === MissingField::class) {
            if ($operation === 'builder' && !($existing instanceof MissingField)) {
                throw new InvalidConfigException('The field type is not registered or is disabled.');
            }
            $type = $existing instanceof MissingField ? $existing->expectedType : (string)$type;
            $this->missingTypes[] = $type;
            $this->warnings[] = "Missing field type: $type. Original settings retained.";
            $config = ['type' => MissingField::class, 'expectedType' => $type, 'settings' => $settings ?: $source,
                'label' => $source['label'] ?? 'Missing Field', 'handle' => $source['handle'] ?? 'missingField'];
        } else {
            if ($existing instanceof ChildFieldInterface && ($this->_parentsById[$existing->id] ?? null) !== $parent?->id) {
                throw new InvalidConfigException('Fixed child instances cannot move to a different parent.');
            }
            $prototype->id = $existing?->id;
            \verbb\formie\compatibility\fields\FieldConfigNormalizer::normalize($source, $type);

            if ($prototype instanceof ChildFieldInterface && !($parent instanceof FixedParentField)) {
                throw new InvalidConfigException('Fixed child fields require their intrinsic parent.');
            }

            if ($parent instanceof FixedParentField) {
                $allowed = $parent->getNestedLayoutBuilderAllowedFieldTypes();

                if (!in_array($type, $allowed, true) || !in_array($type, $parent->getChildFieldTypesByHandle()[$source['handle'] ?? ''] ?? [], true)) {
                    throw new InvalidConfigException('This field type is not a child of the selected parent.');
                }
            }

            if ($parent instanceof ParentFieldInterface && !($parent instanceof FixedParentField) && !in_array($type, $parent->getNestedLayoutBuilderAllowedFieldTypes(), true)) {
                throw new InvalidConfigException('This field type is not allowed in the selected nested field.');
            }
            $keys = array_diff(array_merge($prototype->settingsAttributes(), ['label', 'handle']), self::PROTECTED_SETTINGS);
            $config = array_intersect_key($source, array_flip($keys));
            $config['type'] = $type;

            if ($prototype instanceof FixedParentField && isset($source['layouts']) && is_array($source['layouts'])) {
                $config['layouts'] = [];

                foreach ($source['layouts'] as $key => $variantRows) {
                    // Inactive editor variants are templates, never existing persisted instances.
                    $variantSerializer = new self();
                    $config['layouts'][$key] = $variantSerializer->_rows($variantRows, 'duplicate', $prototype, $path . '.');
                }
            }

            if ($prototype instanceof ParentFieldInterface && isset($source['rows'])) {
                $config['rows'] = $this->_rows($source['rows'], $operation, $prototype, $path . '.');

                if ($prototype instanceof \verbb\formie\fields\Date) {
                    $key = match ($source['displayType'] ?? 'datePicker') {
                        'dropdowns' => 'dropdowns', 'inputs' => 'inputs',
                        'datePicker' => ($source['collectMode'] ?? 'single') === 'range' ? 'calendarRange' : 'calendar',
                        default => 'calendar',
                    };
                    $config['layouts'][$key] = $config['rows'];
                }
            }
        }
        $reference = $existing?->reference ?? ($operation === 'builder' ? ($source['reference'] ?? StringHelper::UUID()) : StringHelper::UUID());
        $config['reference'] = $reference;
        $config['uid'] = $existing?->uid ?? StringHelper::UUID();
        $config['id'] = $existing?->id;
        $config['definitionId'] = $existing?->definitionId;
        $config['definitionUid'] = $existing?->definitionUid;

        if ($existing instanceof ParentFieldInterface) {
            $config['nestedLayoutId'] = $existing->nestedLayoutId;
        }

        if ($existing) {
            $this->changes['retained'][] = $reference;
        } else {
            $this->changes['added'][] = $path;
        }

        if (!empty($node['reference']) && $node['reference'] !== $reference) {
            $this->remaps[$node['reference']] = $reference;
        }

        if (!empty($node['uid']) && $node['uid'] !== $config['uid']) {
            $this->remaps[$node['uid']] = $config['uid'];
        }

        if ($operation !== 'builder' && $path !== '') {
            $this->_tokenHandles[$path] = $reference;
        }

        if ($operation === 'builder') {
            if ($existing && $existing->getIsSynced() && empty($source['isSynced'])) {
                $config['definitionId'] = null;
                $config['definitionUid'] = null;
            }

            if (!$existing && !empty($source['isSynced'])) {
                $signed = Craft::$app->getSecurity()->validateData((string)($source['definitionToken'] ?? ''));
                $grant = $signed ? Json::decode($signed) : [];

                if (($grant['purpose'] ?? null) !== 'field-definition' || ($grant['formId'] ?? null) !== $this->_targetFormId || ($grant['userId'] ?? null) !== Craft::$app->getUser()->getId()) {
                    throw new InvalidConfigException('Invalid synced field selection. Select the field again.');
                }
                $definition = $service->getFieldDefinitionById((int)$grant['definitionId']);

                if (!$definition || $definition->type !== $type) {
                    throw new InvalidConfigException('The selected field definition is unavailable.');
                }
                $config['definitionId'] = $definition->id;
                $config['definitionUid'] = $definition->uid;
                $config['handle'] = $definition->handle;
                $config['isSynced'] = true;
            }
        }

        // Portable sync is explicit UID resolution. Numeric foreign IDs never confer ownership.
        if ($operation === 'stencil' && !empty($node['syncedDefinitionUid'])) {
            $definition = $service->getFieldDefinitionByUid($node['syncedDefinitionUid']);

            if ($definition && $definition->type === $type) {
                $config['definitionId'] = $definition->id;
                $config['definitionUid'] = $definition->uid;
                $config['isSynced'] = true;
            } else {
                $this->warnings[] = "Synced definition unavailable for $path; created an independent definition.";
            }
        }

        return $config;
    }
}
