<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ClearanceDependency extends Model
{
    protected $fillable = [
        'dependent_type',
        'dependent_id',
        'prerequisite_type',
        'prerequisite_id',
    ];

    // Relationships
    public function dependent(): MorphTo
    {
        return $this->morphTo();
    }

    public function prerequisite(): MorphTo
    {
        return $this->morphTo();
    }
}
