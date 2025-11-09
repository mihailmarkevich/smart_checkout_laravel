<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'available_qty',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
