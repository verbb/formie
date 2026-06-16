<?php
namespace verbb\formie\variables;

use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

interface VariableSourceInterface
{
    // Public Methods
    // =========================================================================

    public function getHandle(): string;
    public function getLabel(): string;
    public function getTypes(): array;
    public function getContent(): string;
    public function getToken(): string;
    public function resolveValue(Submission $submission, ?Notification $notification = null): mixed;
    public function toPickerSource(): array;
}
