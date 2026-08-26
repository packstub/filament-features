<?php

namespace Packstub\Features\Support;

use Illuminate\Support\Str;

final class Plan
{
    /**
     * @param  array<string, mixed>  $features
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $features = [],
        public readonly ?string $description = null,
        public readonly ?string $price = null,
    ) {}

    /**
     * @param  array<string, mixed>|string  $definition
     */
    public static function fromArray(string $key, array|string $definition): self
    {
        if (is_string($definition)) {
            $definition = ['label' => $definition];
        }

        return new self(
            key: $key,
            label: (string) ($definition['label'] ?? Str::headline($key)),
            features: (array) ($definition['features'] ?? []),
            description: $definition['description'] ?? null,
            price: $definition['price'] ?? null,
        );
    }

    public function has(string $feature): bool
    {
        return array_key_exists($feature, $this->features);
    }

    public function value(string $feature): mixed
    {
        return $this->features[$feature] ?? null;
    }
}
