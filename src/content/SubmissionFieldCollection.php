<?php
namespace verbb\formie\content;

use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;

class SubmissionFieldCollection
{
    // Static Methods
    // =========================================================================

    public static function fromSubmission(Submission $submission): self
    {
        return new self($submission->getFields());
    }


    // Properties
    // =========================================================================

    private array $_fields = [];
    private array $_fieldsByHandle = [];
    private array $_fieldsById = [];
    private array $_fieldsByUid = [];
    private array $_persistedFields = [];
    private array $_persistedFieldsByUid = [];
    private array $_persistedFieldUids = [];
    private array $_nonCosmeticFields = [];
    private array $_disabledFields = [];
    private array $_fileFieldsByHandle = [];


    // Public Methods
    // =========================================================================

    public function __construct(array $fields = [])
    {
        $this->_fields = $fields;

        foreach ($fields as $field) {
            if (!isset($field->handle)) {
                continue;
            }

            $this->_fieldsByHandle[$field->handle] = $field;

            if (isset($field->id)) {
                $this->_fieldsById[(int)$field->id] = $field;
            }

            if (isset($field->uid)) {
                $this->_fieldsByUid[(string)$field->uid] = $field;
            }

            if (!$field->getIsCosmetic()) {
                $this->_nonCosmeticFields[] = $field;
            }

            if ($field->visibility === 'disabled') {
                $this->_disabledFields[] = $field;
            }

            if ($field::dbType() !== null) {
                // Persisted fields are the only ones that should round-trip
                // through stored submission content. Cosmetic/client-only fields
                // still exist in the collection, but not in the persisted subset.
                $this->_persistedFields[] = $field;

                if (isset($field->uid)) {
                    $uid = (string)$field->uid;
                    $this->_persistedFieldsByUid[$uid] = $field;
                    $this->_persistedFieldUids[] = $uid;
                }
            }

            if ($field->fieldKind() === Field::KIND_FILE) {
                $this->_fileFieldsByHandle[$field->handle] = $field;
            }
        }
    }

    public function all(): array
    {
        return $this->_fields;
    }

    public function getByHandle(string $handle): ?FieldInterface
    {
        return $this->_fieldsByHandle[$handle] ?? null;
    }

    public function getById(int $id): ?FieldInterface
    {
        return $this->_fieldsById[$id] ?? null;
    }

    public function getByUid(string $uid): ?FieldInterface
    {
        return $this->_fieldsByUid[$uid] ?? null;
    }

    public function persisted(): array
    {
        return $this->_persistedFields;
    }

    public function getPersistedByUid(string $uid): ?FieldInterface
    {
        return $this->_persistedFieldsByUid[$uid] ?? null;
    }

    public function persistedUids(): array
    {
        return $this->_persistedFieldUids;
    }

    public function nonCosmetic(): array
    {
        return $this->_nonCosmeticFields;
    }

    public function disabled(): array
    {
        return $this->_disabledFields;
    }

    public function fileFieldsByHandle(): array
    {
        return $this->_fileFieldsByHandle;
    }
}
