<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class InvoiceItem extends Model
{
    use HasUuids;

    protected $table = 'InvoiceItem';

    public $timestamps = false;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invoiceId',
        'itemCode',
        'itemName',
        'quantity',
        'pctCode',
        'taxRate',
        'saleValue',
        'salesTaxApplicable',
        'furtherTax',
        'federalTax',
        'discount',
        'netAmount',
    ];

    protected $casts = [
        'taxRate' => 'float',
        'saleValue' => 'float',
        'salesTaxApplicable' => 'float',
        'furtherTax' => 'float',
        'federalTax' => 'float',
        'discount' => 'float',
        'netAmount' => 'float',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoiceId', 'id');
    }
}
