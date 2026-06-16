<?php
namespace verbb\formie\variables;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\Variables;
use verbb\formie\models\Notification;

use Closure;

class VariableSource implements VariableSourceInterface
{
    // Properties
    // =========================================================================

    private string $_handle;
    private string $_label;
    private array $_types;
    private string $_content;
    private mixed $_resolver;


    // Constructors
    // =========================================================================

    public function __construct(
        string $handle,
        string $label,
        array $types = [Variables::TYPE_TEXT],
        string $content = Variables::CONTENT_SINGLE_LINE,
        mixed $resolver = null,
    ) {
        $this->_handle = strtolower(trim($handle));
        $this->_label = $label;
        $this->_types = $types;
        $this->_content = $content;
        $this->_resolver = $resolver;
    }

    public static function create(string $handle, string $label): self
    {
        return new self($handle, $label);
    }


    // Public Methods
    // =========================================================================

    public function types(array $types): self
    {
        $this->_types = $types;

        return $this;
    }

    public function content(string $content): self
    {
        $this->_content = $content;

        return $this;
    }

    public function resolve(Closure|callable $resolver): self
    {
        $this->_resolver = $resolver;

        return $this;
    }

    public function getHandle(): string
    {
        return $this->_handle;
    }

    public function getLabel(): string
    {
        return $this->_label;
    }

    public function getTypes(): array
    {
        return $this->_types;
    }

    public function getContent(): string
    {
        return $this->_content;
    }

    public function getToken(): string
    {
        return '{' . Variables::TARGET_CUSTOM . ':' . $this->_handle . '}';
    }

    public function resolveValue(Submission $submission, ?Notification $notification = null): mixed
    {
        if (!is_callable($this->_resolver)) {
            return null;
        }

        return ($this->_resolver)($submission, $notification);
    }

    public function toPickerSource(): array
    {
        $entry = [
            'label' => $this->_label,
            'value' => $this->getToken(),
            'content' => $this->_content,
            'types' => array_values(array_unique(array_filter(array_map('strval', $this->_types)))),
            'group' => 'selector',
        ];

        if ($entry['types'] === []) {
            $entry['types'] = [Variables::TYPE_TEXT];
        }

        return $entry;
    }
}
