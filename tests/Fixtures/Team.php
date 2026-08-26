<?php

namespace Packstub\Features\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Packstub\Features\Concerns\HasFeatures;

class Team extends Model
{
    use HasFeatures;

    protected $guarded = [];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
