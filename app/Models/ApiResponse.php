<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiResponse extends Model
{
    protected $fillable = [
        'api_endpoint_id',
        'status_code',
        'description',
        'schema',
        'example',
        'is_error',
    ];

    protected $casts = [
        'schema' => 'array',
        'example' => 'array',
        'is_error' => 'boolean',
    ];

    /**
     * Get the endpoint for this response.
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ApiEndpoint::class, 'api_endpoint_id');
    }
}
