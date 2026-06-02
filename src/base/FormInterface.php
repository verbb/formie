<?php
namespace verbb\formie\base;

use verbb\formie\models\FieldLayout;
use verbb\formie\models\FormSettings;

interface FormInterface
{
    // Public Methods
    // =========================================================================

    public function getId(): ?int;
    public function getHandle(): ?string;
    public function getSettings(): ?FormSettings;
    public function getFormLayout(): FieldLayout;
}
