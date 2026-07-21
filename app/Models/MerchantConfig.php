<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantConfig extends Model
{
    protected $table = 'MerchantConfig';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'posId',
        'token',
        'branchName',
        'branchAddress',
        'apiUrl',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];
}
