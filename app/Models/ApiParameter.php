<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiParameter extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'name',
        'type',
        'location',
        'is_required',
        'description',
        'default_value',
        'validation_rules',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'is_required' => 'boolean',
    ];

    protected $appends = ['required'];

    /**
     * Get the endpoint for this parameter.
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }

    /**
     * Accessor for 'required' to maintain compatibility with frontend.
     */
    public function getRequiredAttribute(): bool
    {
        return $this->is_required;
    }
}
