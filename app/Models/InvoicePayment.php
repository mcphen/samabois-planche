<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use HasFactory;

    protected  $table="invoice_payment";

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount_paid',
    ];

    public function invoice(){
        return $this->belongsTo(Invoice::class,'invoice_id');
    }
}
