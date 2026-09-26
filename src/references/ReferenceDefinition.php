<?php
namespace verbb\formie\references;

use verbb\formie\fields\definitions\FieldValueType;

final readonly class ReferenceDefinition
{
    // Public Methods
    // =========================================================================

    public function __construct(
        public string $id,
        public string $label,
        public string $category,
        public FieldValueType $valueType,
        public array $selectors = [],
        public array $transforms = [],
        public bool $server = true,
        public bool $browser = false,
    ) {
    }

    public function toPickerSource(string $token): array
    {
        return ['label' => $this->label, 'value' => $token, 'category' => $this->category, 'valueType' => $this->valueType->toArray(), 'selectors' => $this->selectors, 'transforms' => $this->transforms, 'content' => 'singleLine', 'types' => [$this->valueType->kind === 'string' ? 'text' : $this->valueType->kind], 'availability' => ['server' => $this->server, 'browser' => $this->browser]];
    }
}
