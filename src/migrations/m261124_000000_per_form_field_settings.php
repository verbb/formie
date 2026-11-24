<?php
namespace verbb\formie\migrations;

use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Table;
use verbb\formie\references\ReferenceParser;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

/**
 * Moves field settings that point at other fields from the shared field definition to each placement: conditions,
 * Calculations formulas, Payment provider settings, Hidden default values and File Upload paths and filenames.
 * They point at other fields by reference, which only exists in one form, so a synced definition could only
 * ever hold one form's references. Creating forms from a stencil rewrote them each time (issue #3159).
 */
class m261124_000000_per_form_field_settings extends Migration
{
    // Constants
    // =========================================================================

    private const MOVED_KEYS = ['enableConditions', 'conditions', 'formula', 'paymentIntegration', 'paymentIntegrationType', 'providerSettings', 'defaultValue', 'uploadLocationSubpath', 'filenameFormat'];


    // Properties
    // =========================================================================

    private array $_definitions = [];
    private array $_instances = [];
    private array $_instanceIdsByDefinition = [];
    private array $_formsByLayoutId = [];
    private array $_layoutOwners = [];
    private array $_layoutContexts = [];
    private array $_movedKeysByType = [];


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach ((new Query())->select(['id', 'type', 'handle', 'settings'])->from(Table::FORMIE_FIELDS)->each(500, $this->db) as $row) {
            $this->_definitions[(int)$row['id']] = $row;
        }

        foreach ((new Query())->select(['id', 'fieldId', 'layoutId', 'reference', 'settings'])->from(Table::FORMIE_FORM_FIELDS)->each(500, $this->db) as $row) {
            $this->_instances[(int)$row['id']] = $row;
            $this->_instanceIdsByDefinition[(int)$row['fieldId']][] = (int)$row['id'];
        }

        foreach ((new Query())->select(['id', 'layoutId'])->from(Table::FORMIE_FORMS)->where(['not', ['layoutId' => null]])->each(500, $this->db) as $row) {
            $this->_formsByLayoutId[(int)$row['layoutId']][] = (int)$row['id'];
        }

        // A nested layout belongs to the parent definition that holds its ID.
        foreach ($this->_definitions as $id => $definition) {
            $nestedLayoutId = $this->_definitionSettings($definition)['nestedLayoutId'] ?? null;

            if (is_numeric($nestedLayoutId)) {
                $this->_layoutOwners[(int)$nestedLayoutId] = $id;
            }
        }

        $settingsById = [];

        foreach ($this->_instances as $id => $instance) {
            $settingsById[$id] = $this->_copyDefinitionSettings($instance);
        }

        $settingsById = $this->_repairForeignReferences($settingsById);

        foreach ($settingsById as $id => $settings) {
            if ($settings !== (Json::decodeIfJson($this->_instances[$id]['settings'] ?? null) ?: [])) {
                $this->update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode($settings)], ['id' => $id], [], false);
            }
        }

        // Definitions no longer own these settings. A stale copy would be read back by synced-field imports.
        foreach ($this->_definitions as $id => $definition) {
            $settings = Json::decodeIfJson($definition['settings'] ?? null);
            $keys = array_flip($this->_movedKeys($definition));

            if (!is_array($settings) || !$keys) {
                continue;
            }

            $stripped = $settings;

            if (isset($stripped['__formieMissingSettings']) && is_array($stripped['__formieMissingSettings'])) {
                $stripped['__formieMissingSettings'] = array_diff_key($stripped['__formieMissingSettings'], $keys);
            } else {
                $stripped = array_diff_key($stripped, $keys);
            }

            if ($stripped !== $settings) {
                $this->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($stripped)], ['id' => $id], [], false);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261124_000000_per_form_field_settings cannot be reverted.\n";

        return false;
    }


    // Private Methods
    // =========================================================================

    /**
     * Only move what the field's class reads back per placement. A type that's unavailable, or a custom type that
     * doesn't call the parent instance settings methods, keeps them on the definition, where it still reads them.
     */
    private function _movedKeys(array $definition): array
    {
        $type = (string)($definition['type'] ?? '');

        if (!array_key_exists($type, $this->_movedKeysByType)) {
            $prototype = $type !== '' ? Formie::$plugin->getFields()->getRegisteredFieldByType($type, false) : null;
            $this->_movedKeysByType[$type] = $prototype ? array_values(array_intersect(self::MOVED_KEYS, array_keys($prototype->getInstanceSettings()))) : [];
        }

        return $this->_movedKeysByType[$type];
    }

    private function _definitionSettings(array $definition): array
    {
        $settings = Json::decodeIfJson($definition['settings'] ?? null);

        if (!is_array($settings)) {
            return [];
        }

        return is_array($settings['__formieMissingSettings'] ?? null) ? $settings['__formieMissingSettings'] : $settings;
    }

    private function _copyDefinitionSettings(array $instance): array
    {
        $settings = Json::decodeIfJson($instance['settings'] ?? null);
        $settings = is_array($settings) ? $settings : [];
        $definition = $this->_definitions[(int)$instance['fieldId']] ?? null;

        if (!$definition) {
            return $settings;
        }

        $source = $this->_definitionSettings($definition);

        foreach ($this->_movedKeys($definition) as $key) {
            if (!array_key_exists($key, $settings) && array_key_exists($key, $source)) {
                $settings[$key] = $source[$key];
            }
        }

        return $settings;
    }

    /**
     * Every placement of a synced definition was given the references of whichever form saved it last.
     * Point those at the field with the same handle path in the placement's own form, where there is one.
     */
    private function _repairForeignReferences(array $settingsById): array
    {
        $pathsByReference = [];
        $referencesByForm = [];

        foreach ($this->_instances as $instance) {
            $reference = (string)($instance['reference'] ?? '');
            $handle = $this->_definitions[(int)$instance['fieldId']]['handle'] ?? null;

            if ($reference === '' || !$handle) {
                continue;
            }

            foreach ($this->_layoutContext((int)$instance['layoutId']) as $formId => $prefix) {
                $path = $prefix . $handle;
                $pathsByReference[$reference][$path] = true;
                $referencesByForm[$formId]['paths'][$path] = $reference;
                $referencesByForm[$formId]['references'][$reference] = true;
            }
        }

        foreach ($settingsById as $id => $settings) {
            $forms = array_keys($this->_layoutContext((int)$this->_instances[$id]['layoutId']));

            // Children of a shared parent appear in several forms through one row, so there's no single form to point at.
            if (count($forms) !== 1) {
                continue;
            }

            $form = $referencesByForm[$forms[0]] ?? ['paths' => [], 'references' => []];
            $resolve = function(string $identifier) use ($form, $pathsByReference): ?string {
                // Only references to another form's field, found at one path there, are repaired.
                if (isset($form['references'][$identifier]) || count($pathsByReference[$identifier] ?? []) !== 1) {
                    return null;
                }

                return $form['paths'][array_key_first($pathsByReference[$identifier])] ?? null;
            };

            foreach (self::MOVED_KEYS as $key) {
                if (isset($settings[$key])) {
                    $settings[$key] = $this->_remap($settings[$key], $resolve);
                }
            }

            $settingsById[$id] = $settings;
        }

        return $settingsById;
    }

    /**
     * The forms a layout appears in, each with the handle path prefix its fields have there.
     */
    private function _layoutContext(int $layoutId, array $visited = []): array
    {
        if (array_key_exists($layoutId, $this->_layoutContexts)) {
            return $this->_layoutContexts[$layoutId];
        }

        $context = [];

        if (isset($this->_formsByLayoutId[$layoutId])) {
            $context = array_fill_keys($this->_formsByLayoutId[$layoutId], '');
        } elseif (isset($this->_layoutOwners[$layoutId]) && !isset($visited[$layoutId])) {
            // A nested field's path runs through each placement of its parent.
            $ownerId = $this->_layoutOwners[$layoutId];

            foreach ($this->_instanceIdsByDefinition[$ownerId] ?? [] as $parentId) {
                foreach ($this->_layoutContext((int)$this->_instances[$parentId]['layoutId'], $visited + [$layoutId => true]) as $formId => $prefix) {
                    $context[$formId] ??= $prefix . $this->_definitions[$ownerId]['handle'] . '.';
                }
            }
        }

        return $this->_layoutContexts[$layoutId] = $context;
    }

    private function _remap(mixed $value, callable $resolve): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->_remap($item, $resolve);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // Conditions can also hold a bare reference, so replace a complete identity too.
        if (($bare = $resolve($value)) !== null) {
            return $bare;
        }

        return preg_replace_callback('/\{field:[^}]+\}/', function(array $match) use ($resolve): string {
            $expression = ReferenceParser::parse($match[0]);

            if (!$expression->isValid || $expression->target !== 'field' || ($local = $resolve($expression->identifier)) === null) {
                return $match[0];
            }

            return References::remapFieldReferenceToken($match[0], [$expression->identifier => $local]);
        }, $value) ?? $value;
    }
}
