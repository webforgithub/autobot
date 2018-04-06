<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $exchange_id
 * @property string $exchange_pair
 */
class ExchangePairs extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['exchange_id', 'market_id', 'exchange_pair', 'baseAsset', 'quoteAsset', 'quotePrecision', 'baseAssetPrecision'];   
}