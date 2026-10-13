<?php
namespace verbb\formie\fields\data;

use craft\base\ElementInterface;
use craft\elements\ElementCollection;
use craft\elements\db\ElementQueryInterface;

use yii\base\NotSupportedException;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Read-only relation value for sandboxed Twig.
 *
 * It intentionally remains non-stringable so length checks cannot authorize
 * collection serialization as a side effect.
 */
class ElementFieldData implements ArrayAccess, Countable, IteratorAggregate
{
    // Properties
    // =========================================================================

    private ElementCollection $_elements;


    // Public Methods
    // =========================================================================

    public function __construct(ElementQueryInterface $query)
    {
        $this->_elements = $query->collect();
    }

    public function one(): ?ElementInterface
    {
        return $this->_elements->first();
    }

    public function all(): array
    {
        return $this->_elements->all();
    }

    public function count(): int
    {
        return $this->_elements->count();
    }

    public function getIterator(): Traversable
    {
        return $this->_elements->getIterator();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->_elements->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->_elements->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NotSupportedException('Element field values are read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new NotSupportedException('Element field values are read-only.');
    }
}
