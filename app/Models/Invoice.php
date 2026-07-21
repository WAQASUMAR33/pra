<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Invoice extends Model
{
    use HasUuids;

    protected $table = 'Invoice';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invoiceNumber',
        'posId',
        'usin',
        'dateTime',
        'buyerNtn',
        'buyerCnic',
        'buyerName',
        'buyerPhone',
        'invoiceType',
        'totalQuantity',
        'totalSaleValue',
        'totalTaxCharged',
        'totalDiscount',
        'totalBillAmount',
        'paymentMode',
        'status',
        'praFiscalNumber',
        'praResponseCode',
        'praResponseMsg',
        'eventType',
        'eventDate',
        'numberOfGuests',
    ];

    protected $casts = [
        'dateTime' => 'datetime',
        'eventDate' => 'datetime',
        'totalSaleValue' => 'float',
        'totalTaxCharged' => 'float',
        'totalDiscount' => 'float',
        'totalBillAmount' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoiceId', 'id');
    }
}
