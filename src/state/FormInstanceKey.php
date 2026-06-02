<?php
namespace verbb\formie\state;

use craft\base\Model;

class FormInstanceKey extends Model
{
    // Properties
    // =========================================================================

    public ?int $formId = null;
    public ?int $siteId = null;
    public string $scope = 'default';
    public ?int $submissionId = null;
    public ?int $ownerId = null;
    public ?string $fingerprint = null;


    // Public Methods
    // =========================================================================

    public function toStorageKey(): string
    {
        return implode(':', array_filter([
            'formie',
            'submission-state',
            'form',
            (string)$this->formId,
            'site',
            (string)$this->siteId,
            'scope',
            $this->scope,
            'owner',
            (string)$this->ownerId,
            'submission',
            (string)$this->submissionId,
            'fingerprint',
            $this->fingerprint,
        ], static fn($value) => $value !== ''));
    }
}
