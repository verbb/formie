<?php
namespace verbb\formie\cache;

use GraphQL\Type\Definition\Type;

class FieldGqlCache
{
    // Properties
    // =========================================================================

    private array $_field = [
        'includeInSchema' => [],
        'contentType' => [],
        'queryArgumentType' => [],
        'mutationArgumentType' => [],
    ];

    private array $_config = [
        'includeInSchema' => [],
        'contentType' => [],
        'queryArgumentType' => [],
        'mutationArgumentType' => [],
    ];


    // Public Methods
    // =========================================================================

    public function reset(): void
    {
        $this->_field = [
            'includeInSchema' => [],
            'contentType' => [],
            'queryArgumentType' => [],
            'mutationArgumentType' => [],
        ];

        $this->_config = [
            'includeInSchema' => [],
            'contentType' => [],
            'queryArgumentType' => [],
            'mutationArgumentType' => [],
        ];
    }

    public function getFieldIncludeInSchema(string $subjectKey, int $schemaKey): ?bool
    {
        return $this->_field['includeInSchema'][$subjectKey][$schemaKey] ?? null;
    }

    public function setFieldIncludeInSchema(string $subjectKey, int $schemaKey, bool $value): void
    {
        $this->_field['includeInSchema'][$subjectKey][$schemaKey] = $value;
    }

    public function getFieldContentType(string $subjectKey): Type|array|null
    {
        return $this->_field['contentType'][$subjectKey] ?? null;
    }

    public function setFieldContentType(string $subjectKey, Type|array $value): void
    {
        $this->_field['contentType'][$subjectKey] = $value;
    }

    public function getFieldQueryArgumentType(string $subjectKey): Type|array|null
    {
        return $this->_field['queryArgumentType'][$subjectKey] ?? null;
    }

    public function setFieldQueryArgumentType(string $subjectKey, Type|array $value): void
    {
        $this->_field['queryArgumentType'][$subjectKey] = $value;
    }

    public function getFieldMutationArgumentType(string $subjectKey): Type|array|null
    {
        return $this->_field['mutationArgumentType'][$subjectKey] ?? null;
    }

    public function setFieldMutationArgumentType(string $subjectKey, Type|array $value): void
    {
        $this->_field['mutationArgumentType'][$subjectKey] = $value;
    }

    public function getConfigIncludeInSchema(string $subjectKey, int $schemaKey): ?bool
    {
        return $this->_config['includeInSchema'][$subjectKey][$schemaKey] ?? null;
    }

    public function setConfigIncludeInSchema(string $subjectKey, int $schemaKey, bool $value): void
    {
        $this->_config['includeInSchema'][$subjectKey][$schemaKey] = $value;
    }

    public function getConfigContentType(string $subjectKey): Type|array|null
    {
        return $this->_config['contentType'][$subjectKey] ?? null;
    }

    public function setConfigContentType(string $subjectKey, Type|array $value): void
    {
        $this->_config['contentType'][$subjectKey] = $value;
    }

    public function getConfigQueryArgumentType(string $subjectKey): Type|array|null
    {
        return $this->_config['queryArgumentType'][$subjectKey] ?? null;
    }

    public function setConfigQueryArgumentType(string $subjectKey, Type|array $value): void
    {
        $this->_config['queryArgumentType'][$subjectKey] = $value;
    }

    public function getConfigMutationArgumentType(string $subjectKey): Type|array|null
    {
        return $this->_config['mutationArgumentType'][$subjectKey] ?? null;
    }

    public function setConfigMutationArgumentType(string $subjectKey, Type|array $value): void
    {
        $this->_config['mutationArgumentType'][$subjectKey] = $value;
    }
}
