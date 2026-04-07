<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes, HasSyncUuid;

    protected $fillable = ['invoice_id', 'article_item_id', 'price','total_price_item'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function articleItem()
    {
        return $this->belongsTo(ArticleItem::class);
    }




}
