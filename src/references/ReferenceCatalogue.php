<?php
namespace verbb\formie\references;

use verbb\formie\events\RegisterReferencesEvent;

use yii\base\Event;

use InvalidArgumentException;

final class ReferenceCatalogue
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

    public function __construct()
    {
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
        return [...\verbb\formie\helpers\Variables::getBuiltinPickerGroups(), \verbb\formie\helpers\Variables::GROUP_CUSTOM => $this->pickerSources()];
    }

    public function pickerSources(): array
    {
        return array_values(array_map(static fn(ReferenceSource $source): array => $source->definition->toPickerSource('{custom:' . $source->definition->id . '}'), $this->_sources));
    }
}
