<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'uri',
        'methods',
        'controller',
        'action',
        'description',
        'group',
        'is_deprecated',
        'middleware',
    ];

    protected $casts = [
        'middleware' => 'array',
        'methods' => 'array',
        'is_deprecated' => 'boolean',
    ];

    /**
     * Get the parameters for this endpoint.
     */
    public function parameters(): HasMany
    {
        return $this->hasMany(ApiParameter::class);
    }

    /**
    * Get the responses for this endpoint.
    */
    public function responses(): HasMany
    {
        return $this->hasMany(ApiResponse::class);
    }
}
