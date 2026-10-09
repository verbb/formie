<?php
namespace verbb\formie\references;

use verbb\formie\Formie;
use verbb\formie\events\RegisterReferencesEvent;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\Variables;

use Craft;

use yii\base\Component;
use yii\base\Event;

use InvalidArgumentException;

final class ReferenceCatalogue extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER = 'registerReferences';


    // Properties
    // =========================================================================

    private array $_sources = [];
    private array $_transforms = [];


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();
        $event = new RegisterReferencesEvent();
        Event::trigger(self::class, self::EVENT_REGISTER, $event);

        foreach ($event->sources as $source) {
            if (!$source instanceof ReferenceSource || isset($this->_sources[$source->definition->id])) {
                throw new InvalidArgumentException('Invalid or duplicate reference source registration.');
            }
            $this->_sources[$source->definition->id] = $source;
        }

        foreach ($event->transforms as $transform) {
            if (!$transform instanceof ReferenceTransform || isset($this->_transforms[$transform->id])) {
                throw new InvalidArgumentException('Invalid or duplicate reference transform registration.');
            }
            $this->_transforms[$transform->id] = $transform;
        }
    }

    public function source(string $id): ?ReferenceSource
    {
        return $this->_sources[$id] ?? null;
    }

    public function transform(string $id): ?ReferenceTransform
    {
        return $this->_transforms[$id] ?? null;
    }

    public function definition(string $target, string $identifier): ?ReferenceDefinition
    {
        if ($target === 'custom') {
            return $this->source($identifier)?->definition;
        }

        $id = $target . ':' . $identifier;
        $definitions = $this->_builtinDefinitions();

        if (isset($definitions[$id])) {
            return $definitions[$id];
        }

        if ($target === 'env' && preg_match('/^[A-Z][A-Z0-9_]*$/D', $identifier)) {
            return $this->_definition($id, '$' . $identifier, Variables::GROUP_ENVIRONMENT, [ReferenceType::Text, ReferenceType::Email, ReferenceType::Number, ReferenceType::Url]);
        }

        if (in_array($target, ['metadata', 'report', 'dispatch'], true) && $identifier !== '') {
            return $this->_definition($id, $identifier, $target, [ReferenceType::Text]);
        }

        return null;
    }

    public function pickerTransforms(): array
    {
        $registry = [];

        foreach ($this->_transforms as $transform) {
            $type = $transform->inputType->kind === 'string' ? 'text' : $transform->inputType->kind;
            $registry[$type][] = [
                'id' => $transform->id, 'label' => $transform->id,
                'inputType' => $transform->inputType->toArray(), 'outputType' => $transform->outputType->toArray(),
                'availability' => ['server' => $transform->server, 'browser' => $transform->browser],
                'params' => array_map(static fn(string $name): array => ['name' => $name, 'label' => $name, 'type' => 'text'], $transform->parameters),
            ];
        }
        return $registry;
    }

    public function pickerGroups(): array
    {
        $groups = [];

        foreach ($this->_builtinDefinitions() as $definition) {
            $groups[$definition->category][] = $definition->toPickerSource($this->_token($definition->id));
        }

        foreach (Formie::$plugin->getSettings()->referenceEnvironmentAllowlist as $name) {
            if (!is_string($name) || !preg_match('/^[A-Z][A-Z0-9_]*$/D', $name)) {
                continue;
            }

            $definition = $this->definition('env', $name);
            $groups[Variables::GROUP_ENVIRONMENT][] = $definition->toPickerSource('{env:' . $name . '}');
        }

        $groups[Variables::GROUP_CUSTOM] = $this->pickerSources();

        return $groups;
    }

    public function pickerSources(): array
    {
        return array_values(array_map(static fn(ReferenceSource $source): array => $source->definition->toPickerSource('{custom:' . $source->definition->id . '}'), $this->_sources));
    }


    // Private Methods
    // =========================================================================

    private function _builtinDefinitions(): array
    {
        $definitions = [
            $this->_definition('allFields:', Craft::t('formie', 'All Form Fields'), Variables::GROUP_FORM, [], ReferenceShape::Block, false),
            $this->_definition('allContentFields:', Craft::t('formie', 'All Non Empty Fields'), Variables::GROUP_FORM, [], ReferenceShape::Block, false),
            $this->_definition('allVisibleFields:', Craft::t('formie', 'All Visible Fields'), Variables::GROUP_FORM, [], ReferenceShape::Block, false),
            $this->_definition('form:name', Craft::t('formie', 'Form Name'), Variables::GROUP_FORM),
            $this->_definition('form:handle', Craft::t('formie', 'Form Handle'), Variables::GROUP_FORM),
            $this->_definition('submission:title', Craft::t('formie', 'Submission Title'), Variables::GROUP_SUBMISSION),
            $this->_definition('submission:id', Craft::t('formie', 'Submission ID'), Variables::GROUP_SUBMISSION, [ReferenceType::Number, ReferenceType::Text]),
            $this->_definition('submission:uid', Craft::t('formie', 'Submission UID'), Variables::GROUP_SUBMISSION),
            $this->_definition('submission:url', Craft::t('formie', 'Submission URL'), Variables::GROUP_SUBMISSION, [ReferenceType::Url]),
            $this->_definition('submission:date', Craft::t('formie', 'Submission Date'), Variables::GROUP_SUBMISSION, [ReferenceType::Date, ReferenceType::Text]),
            $this->_definition('submission:site', Craft::t('formie', 'Submission Site'), Variables::GROUP_SUBMISSION),
            $this->_definition('submission:status', Craft::t('formie', 'Submission Status'), Variables::GROUP_SUBMISSION),
            $this->_definition('system:name', Craft::t('formie', 'System Name'), Variables::GROUP_SYSTEM),
            $this->_definition('system:email', Craft::t('formie', 'System Email'), Variables::GROUP_SYSTEM, [ReferenceType::Text, ReferenceType::Email]),
            $this->_definition('system:replyTo', Craft::t('formie', 'System Reply-To'), Variables::GROUP_SYSTEM, [ReferenceType::Text, ReferenceType::Email]),
            $this->_definition('timestamp:', Craft::t('formie', 'Current Date/Time'), Variables::GROUP_CURRENT_TIME, [ReferenceType::Text, ReferenceType::Date]),
            $this->_definition('site:id', Craft::t('formie', 'Site ID'), Variables::GROUP_CURRENT_SITE, [ReferenceType::Number, ReferenceType::Text]),
            $this->_definition('site:name', Craft::t('formie', 'Site Name'), Variables::GROUP_CURRENT_SITE),
            $this->_definition('site:handle', Craft::t('formie', 'Site Handle'), Variables::GROUP_CURRENT_SITE),
            $this->_definition('site:url', Craft::t('formie', 'Site URL'), Variables::GROUP_CURRENT_SITE, [ReferenceType::Url]),
            $this->_definition('site:language', Craft::t('formie', 'Site Language'), Variables::GROUP_CURRENT_SITE),
            $this->_definition('user:ip', Craft::t('formie', 'User IP Address'), Variables::GROUP_CURRENT_USER),
            $this->_definition('user:id', Craft::t('formie', 'User ID'), Variables::GROUP_CURRENT_USER, [ReferenceType::Number, ReferenceType::Text]),
            $this->_definition('user:email', Craft::t('formie', 'User Email'), Variables::GROUP_CURRENT_USER, [ReferenceType::Text, ReferenceType::Email]),
            $this->_definition('user:name', Craft::t('formie', 'Username'), Variables::GROUP_CURRENT_USER),
            $this->_definition('user:username', Craft::t('formie', 'Username'), Variables::GROUP_CURRENT_USER),
            $this->_definition('user:fullName', Craft::t('formie', 'User Full Name'), Variables::GROUP_CURRENT_USER),
            $this->_definition('user:firstName', Craft::t('formie', 'User First Name'), Variables::GROUP_CURRENT_USER),
            $this->_definition('user:lastName', Craft::t('formie', 'User Last Name'), Variables::GROUP_CURRENT_USER),
        ];

        $indexed = [];

        foreach ($definitions as $definition) {
            $indexed[$definition->id] = $definition;
        }

        return $indexed;
    }

    private function _definition(
        string $id,
        string $label,
        string $category,
        array $types = [ReferenceType::Text],
        ReferenceShape $shape = ReferenceShape::Inline,
        bool $allowTransforms = true,
    ): ReferenceDefinition {
        return new ReferenceDefinition($id, $label, $category, FieldValueType::storageSafe(), types: $types, shape: $shape, allowTransforms: $allowTransforms);
    }

    private function _token(string $id): string
    {
        [$target, $identifier] = array_pad(explode(':', $id, 2), 2, '');

        return '{' . $target . ($identifier !== '' ? ':' . $identifier : '') . '}';
    }
}
