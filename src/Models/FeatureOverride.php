<?php

namespace Packstub\Features\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $scope_type
 * @property string|null $scope_id
 * @property string $feature
 * @property mixed $value
 * @property string|null $reason
 * @property Carbon|null $expires_at
 */
class FeatureOverride extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'expires_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('packstub-features.overrides_table', 'feature_overrides');
    }

    public function getConnectionName(): ?string
    {
        return config('packstub-features.connection') ?? parent::getConnectionName();
    }

    public function scope(): MorphTo
    {
        return $this->morphTo('scope');
    }

    public function isGlobal(): bool
    {
        return $this->scope_type === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('scope_type')->whereNull('scope_id');
    }

    public function scopeForScope(Builder $query, Model $scope): Builder
    {
        return $query->where('scope_type', $scope->getMorphClass())->where('scope_id', (string) $scope->getKey());
    }
}
