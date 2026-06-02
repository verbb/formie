<?php

declare(strict_types=1);

namespace Tests\Support;

final class FieldCapabilityMatrix
{
    public static function requiredFlagMethods(): array
    {
        return [
            ['singleLineTextField', 'username', []],
            ['multiLineTextField', 'bio', []],
            ['emailField', 'email', []],
            ['numberField', 'age', []],
            ['phoneField', 'phone', []],
            ['passwordField', 'password', []],
        ];
    }

    public static function requiredUnsupportedMethods(): array
    {
        return [
            ['dateField', 'Date field required is managed differently in runtime models.'],
        ];
    }

    public static function integrationExcludedHandles(): array
    {
        return [
            ['name', 'Container parent integration resolution requires subfield-aware field keys.'],
            ['topics', 'Checkbox multi-option values can contain array payloads not uniformly supported across integration types.'],
        ];
    }
}
