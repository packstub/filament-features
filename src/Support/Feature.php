<?php

namespace Packstub\Features\Support;

use Illuminate\Support\Str;

/**
 * A feature definition.
 */
final class Feature
{
    public const BOOLEAN = 'boolean';

    public const LIMIT = 'limit';

    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type = self::BOOLEAN,
        public readonly mixed $default = false,
        public readonly ?string $description = null,
        public readonly ?string $unit = null,
        public readonly ?string $group = null,
    ) {}

    /**
     * @param  array<string, mixed>|string  $definition
     */
    public static function fromArray(string $key, array|string $definition): self
    {
        if (is_string($definition)) {
            $definition = ['label' => $definition];
        }

        $type = $definition['type'] ?? (array_key_exists('default', $definition) && is_int($definition['default']) ? self::LIMIT : self::BOOLEAN);

        return new self(
            key: $key,
            label: (string) ($definition['label'] ?? Str::headline($key)),
            type: $type,
            default: array_key_exists('default', $definition) ? $definition['default'] : ($type === self::LIMIT ? 0 : false),
            description: $definition['description'] ?? null,
            unit: $definition['unit'] ?? null,
            group: $definition['group'] ?? null,
        );
    }

    public function isLimit(): bool
    {
        return $this->type === self::LIMIT;
    }

    public function isBoolean(): bool
    {
        return $this->type === self::BOOLEAN;
    }

    /**
     * Normalise a raw value (config, override, plan) for this feature's type.
     */
    public function cast(mixed $value): bool|int|null
    {
        if ($this->isLimit()) {
            if ($value === null || $value === true || $value === 'unlimited' || $value === '∞') {
                return null; // unlimited
            }

            if ($value === false) {
                return 0;
            }

            return (int) $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (is_int($value)) {
            return $value > 0;
        }

        return (bool) $value;
    }

    /**
     * Human-readable value.
     */
    public function format(bool|int|null $value): string
    {
        if ($this->isLimit()) {
            if ($value === null) {
                return __('packstub-features::features.values.unlimited');
            }

            return $this->unit ? "{$value} {$this->unit}" : (string) $value;
        }

        return $value ? __('packstub-features::features.values.included') : __('packstub-features::features.values.not_included');
    }
}
