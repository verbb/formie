<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\base\ParentFieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\models\LayoutSaveContext;

use Craft;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;

use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;

/** Resolves portable definitions without trusting database IDs from an import document. */
class SyncedFieldImport
{
    // Properties
    // =========================================================================

    private array $_definitions = [];
    private array $_targets = [];
    private array $_references = [];
    private array $_translations = [];
    private ?array $_forms = null;
    private array $_links = [];
    private array $_layouts = [];
    private array $_ownedFields = [];
    private array $_kept = [];
    private array $_saved = [];
    private array $_options;
    private ?User $_user;


    // Public Methods
    // =========================================================================

    public function __construct(array $data, array $options = [], ?User $user = null)
    {
        $this->_options = $options;
        $this->_user = $user;

        if (!in_array($options['mode'] ?? 'preserve', ['preserve', 'independent'], true) || !is_array($options['definitions'] ?? [])) {
            throw new InvalidConfigException('Invalid synced field import options.');
        }

        foreach ($data['pages'] ?? [] as $page) {
            $this->_collect($page['rows'] ?? []);
        }

        foreach ($data['fieldSiteOverrides'] ?? [] as $site => $overrides) {
            foreach ($overrides as $reference => $settings) {
                foreach ($this->_references[$reference] ?? [] as $uid => $path) {
                    $this->_translations[$uid][$site][$path] = $settings;
                }
            }
        }

        foreach ($options['definitions'] ?? [] as $uid => $choice) {
            if (!isset($this->_definitions[$uid]) || !is_array($choice) || !in_array($choice['action'] ?? 'keep', ['keep', 'update', 'independent'], true)) {
                throw new InvalidConfigException('Invalid synced field import choice.');
            }
        }
    }

    public function preview(): array
    {
        $result = [];

        foreach ($this->_definitions as $uid => $node) {
            $target = $this->_target($uid);
            $field = $target['field'];
            $status = 'create';

            if ($target['definition']) {
                $status = 'conflict';

                if ($target['definition']->type !== ($node['type'] ?? '')) {
                    $status = 'incompatible';
                } elseif ($field && $this->_signature((new FormSerializer())->serializeField($field)) === $this->_signature($node) && !$this->_translationConflict($uid, $target)) {
                    $status = 'reuse';
                }
            }

            $result[$uid] = [
                'label' => $node['settings']['label'] ?? '',
                'handle' => $node['settings']['handle'] ?? '',
                'status' => $status,
                'canReuse' => $target['allowed'],
                'forms' => $target['allowed'] ? $target['forms'] : [],
                'expected' => $target['fingerprint'],
            ];
        }

        return $result;
    }

    public function resolve(array $node, ?Field $existing): ?array
    {
        $uid = isset($node['syncedDefinitionUid']) ? strtolower($node['syncedDefinitionUid']) : null;

        if (!$uid || $this->_action($uid) === 'independent') {
            return null;
        }

        $target = $this->_target($uid);
        $definition = $target['definition'];
        $action = $this->_action($uid);

        if (!$target['allowed']) {
            throw new ForbiddenHttpException('You must be able to manage every form using this synced field. Import it as an independent field instead.');
        }

        if ($definition && $definition->type !== $node['type']) {
            throw new InvalidConfigException('The synced field has a different type on this environment. Import it as an independent field.');
        }

        if ($action === 'update' && ($this->_options['definitions'][$uid]['expected'] ?? null) !== $target['fingerprint']) {
            throw new InvalidConfigException('The synced field or its uses have changed. Review the import again before updating it.');
        }

        if ($existing instanceof ParentFieldInterface && $existing->definitionUid !== $uid) {
            throw new InvalidConfigException('An existing independent parent field cannot be relinked without changing its stored child identities. Import it as independent fields or create a new form.');
        }

        if ($existing && $existing->definitionUid !== $uid && !in_array($this->_options['relink'] ?? false, [true, 1, '1'], true)) {
            throw new InvalidConfigException('This form has an independent or differently linked field. Enable linking existing fields to replace its relationship, or import independent fields.');
        }

        $settings = $definition ? ['handle' => $definition->handle] : [];

        if ($definition && $action === 'keep') {
            $settings = $definition->settings + ['label' => $definition->label, 'handle' => $definition->handle];
            unset($settings['nestedLayoutId']);
            $this->_kept[$definition->id] = true;

            if ($target['field'] instanceof ParentFieldInterface) {
                $settings['rows'] = (new FormSerializer())->serializeField($target['field'])['settings']['rows'];
            }
        }

        if ($target['field'] instanceof ParentFieldInterface) {
            $layout = $target['field']->getFieldLayout();
            $this->_layouts[$layout->id] = true;

            foreach (FieldTraversal::recursively($target['field']->getFields()) as $child) {
                $this->_ownedFields[$child->id] = true;

                if ($action === 'keep') {
                    $this->_kept[$child->definitionId] = true;
                }
            }
        }

        return ['uid' => $uid, 'definitionId' => $definition?->id, 'field' => $target['field'], 'settings' => $settings];
    }

    public function link(array $node, array $config, ?Field $existing, ?array $sync): array
    {
        $uid = isset($node['syncedDefinitionUid']) ? strtolower($node['syncedDefinitionUid']) : null;

        if (($this->_options['mode'] ?? 'preserve') === 'independent' || ($uid && $this->_action($uid) === 'independent')) {
            // Preserve placement identity so existing submissions remain readable, but detach its definition.
            $config['definitionId'] = null;
            $config['definitionUid'] = null;
            $config['isSynced'] = false;

            return $config;
        }

        if (!$sync) {
            return $config;
        }

        $config['definitionId'] = $sync['definitionId'];
        $config['definitionUid'] = $uid;
        $config['isSynced'] = true;
        $this->_links[$config['reference']] = ['uid' => $uid, 'id' => $existing?->id, 'definitionId' => $sync['definitionId']];

        if ($sync['field'] instanceof ParentFieldInterface) {
            $config['nestedLayoutId'] = $sync['field']->nestedLayoutId;
        }

        return $config;
    }

    public function shareLayouts(Form $form): void
    {
        $layouts = [];
        $visit = function(array $fields) use (&$visit, &$layouts): void {
            foreach ($fields as $field) {
                if (!$field instanceof ParentFieldInterface) {
                    continue;
                }

                $uid = $this->_links[$field->reference]['uid'] ?? null;

                if ($uid) {
                    if (isset($layouts[$uid])) {
                        $field->setFieldLayout($layouts[$uid]);

                        continue;
                    }

                    $layouts[$uid] = $field->getFieldLayout();
                }

                $visit($field->getFields());
            }
        };
        $visit($form->getFields());
    }

    public function includeLayouts(LayoutSaveContext $context): void
    {
        foreach (array_keys($this->_layouts) as $layoutId) {
            $context->includeLayout($layoutId);
        }
    }

    public function ownsField(Field $field): bool
    {
        return isset($this->_ownedFields[$field->id]);
    }

    public function allowsLink(Field $field): bool
    {
        $link = $this->_links[$field->reference] ?? null;

        return $link && $link['id'] === $field->id && $link['uid'] === $field->definitionUid && ($this->_saved[$link['uid']] ?? $link['definitionId']) === $field->definitionId;
    }

    public function prepareSave(Field $field): void
    {
        $link = $this->_links[$field->reference] ?? null;

        if ($link && $link['id'] === $field->id) {
            $field->definitionId = $this->_saved[$link['uid']] ?? $link['definitionId'];
        }
    }

    public function didSave(Field $field, int $definitionId): void
    {
        if (isset($this->_links[$field->reference])) {
            $this->_saved[$field->definitionUid] = $definitionId;
        }
    }

    public function keepsDefinition(int $id): bool
    {
        return isset($this->_kept[$id]);
    }

    public function updatesLayout(Field $field): bool
    {
        $link = $this->_links[$field->reference] ?? null;

        return $link && $link['definitionId'] && $this->_action($link['uid']) === 'update' && !isset($this->_saved[$link['uid']]);
    }


    // Private Methods
    // =========================================================================

    private function _action(string $uid): string
    {
        return ($this->_options['mode'] ?? 'preserve') === 'independent' ? 'independent' : ($this->_options['definitions'][$uid]['action'] ?? 'keep');
    }

    private function _collect(array $rows, array $parents = []): void
    {
        foreach ($rows as $row) {
            foreach ($row['fields'] ?? [] as $node) {
                $settings = Json::decodeIfJson($node['settings'] ?? []) ?: [];
                $node['settings'] = $settings;
                $scopes = array_map(fn($path) => ltrim($path . '.' . ($settings['handle'] ?? ''), '.'), $parents);

                if ($uid = $node['syncedDefinitionUid'] ?? null) {
                    if (!is_string($uid) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $uid)) {
                        throw new InvalidConfigException('Invalid synced field definition UID.');
                    }

                    $uid = strtolower($uid);

                    if (isset($this->_definitions[$uid]) && $this->_signature($this->_definitions[$uid]) !== $this->_signature($node)) {
                        throw new InvalidConfigException('The import contains conflicting settings for the same synced field.');
                    }

                    $this->_definitions[$uid] = $node;
                    $scopes[$uid] = '';
                }

                $this->_references[$node['reference'] ?? ''] = $scopes;
                $this->_collect($settings['rows'] ?? [], $scopes);
            }
        }
    }

    private function _target(string $uid): array
    {
        if (isset($this->_targets[$uid])) {
            return $this->_targets[$uid];
        }

        $definition = Formie::$plugin->getFields()->getFieldDefinitionByUid($uid);
        $field = null;
        $forms = [];
        $allowed = true;

        if ($definition) {
            // Include nested placements and disabled/trashed forms. CP visibility must not hide owners.
            $this->_forms ??= Form::find()->withoutCpIndexScope()->site('*')->unique()->status(null)->trashed(null)->all();

            $definitionIds = [$definition->id];

            foreach ($this->_forms as $form) {
                foreach (FieldTraversal::recursively($form->getFields()) as $candidate) {
                    if ($candidate->definitionId === $definition->id && $candidate instanceof ParentFieldInterface) {
                        foreach (FieldTraversal::recursively($candidate->getFields()) as $child) {
                            $definitionIds[] = $child->definitionId;
                        }
                    }
                }
            }

            foreach ($this->_forms as $form) {
                foreach (FieldTraversal::recursively($form->getFields()) as $candidate) {
                    if (in_array($candidate->definitionId, $definitionIds, true)) {
                        if ($candidate->definitionId === $definition->id) {
                            $field ??= $candidate;
                        }

                        $forms[$form->id] = ['id' => $form->id, 'title' => (string)$form->title, 'handle' => $form->handle];
                        $allowed = $allowed && (!$this->_user || Formie::$plugin->getPermissions()->canManageForm($this->_user, $form));
                    }
                }
            }
        }

        ksort($forms);
        $fingerprint = hash('sha256', Json::encode([$definition, $field ? (new FormSerializer())->serializeField($field) : null, array_keys($forms), $definition ? (new Query())->from(Table::FORMIE_FIELD_SITE_OVERRIDES)->where(['fieldId' => $definitionIds])->orderBy(['fieldId' => SORT_ASC, 'siteId' => SORT_ASC])->all() : []]));

        return $this->_targets[$uid] = compact('definition', 'field', 'forms', 'allowed', 'fingerprint');
    }

    private function _translationConflict(string $uid, array $target): bool
    {
        $fields = ['' => $target['definition']->id];
        $visit = function(array $children, string $prefix = '') use (&$visit, &$fields): void {
            foreach ($children as $child) {
                $path = $prefix . $child->handle;
                $fields[$path] = $child->definitionId;

                if ($child instanceof ParentFieldInterface) {
                    $visit($child->getFields(), $path . '.');
                }
            }
        };

        if ($target['field'] instanceof ParentFieldInterface) {
            $visit($target['field']->getFields());
        }

        foreach ($this->_translations[$uid] ?? [] as $siteHandle => $overrides) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            foreach ($overrides as $path => $settings) {
                if ($site && isset($fields[$path]) && Formie::$plugin->getFieldSiteOverrides()->getOverride($fields[$path], $site->id) != $settings) {
                    return true;
                }
            }
        }

        return false;
    }

    private function _signature(array $node): string
    {
        // Compare definition content, not placement identities. Child Required states
        // belong to a shared parent's layout, so only the outer placement is excluded.
        $normalizeField = function(array $field, bool $root = false) use (&$normalizeField): array {
            $settings = Json::decodeIfJson($field['settings'] ?? []) ?: [];
            unset($settings['nestedLayoutId']);

            if (isset($settings['rows'])) {
                $settings['rows'] = array_map(fn($row) => [
                    'fields' => array_map(fn($child) => $normalizeField($child), $row['fields'] ?? []),
                ], $settings['rows']);
            }

            return ['type' => $field['type'] ?? '', 'settings' => $settings] + ($root ? [] : ['instanceSettings' => $field['instanceSettings'] ?? []]);
        };
        $sort = function(array $value) use (&$sort): array {
            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    $value[$key] = $sort($item);
                }
            }

            if (!array_is_list($value)) {
                ksort($value);
            }

            return $value;
        };

        return hash('sha256', Json::encode($sort($normalizeField($node, true))));
    }
}
