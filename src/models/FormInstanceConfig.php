<?php
namespace verbb\formie\models;

final class FormInstanceConfig
{
    // Static Methods
    // =========================================================================

    public static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            // Maps merge; lists and explicit empty arrays/null replace. Presence,
            // rather than truthiness, controls whether an authored value wins.
            $base[$key] = is_array($value) && $value !== [] && !array_is_list($value)
                && isset($base[$key]) && is_array($base[$key])
                ? self::merge($base[$key], $value) : $value;
        }
        return $base;
    }


    // Public Methods
    // =========================================================================

    public function __construct(
        public readonly array $form = [],
        public readonly array $fields = [],
        public readonly array $pages = [],
        public readonly array $initial = [],
        public readonly array $forced = [],
        public readonly array $query = [],
        public readonly array $prefill = [],
        public readonly ?string $completionRedirectOverride = null,
    ) {
    }

    public function with(string $section, array $values): self
    {
        $data = get_object_vars($this);
        if (!array_key_exists($section, $data)) {
            throw new \InvalidArgumentException('Unknown instance configuration section: ' . $section);
        }
        $data[$section] = self::merge($data[$section], $values);
        return new self(...$data);
    }

    public function withCompletionRedirectOverride(?string $value): self
    {
        $data = get_object_vars($this);
        $data['completionRedirectOverride'] = $value;

        return new self(...$data);
    }

    public function toArray(): array
    {
        return ['version' => 1] + get_object_vars($this);
    }
}
