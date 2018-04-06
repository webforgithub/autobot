<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model {

    use SoftDeletes;
    protected $table = 'order_history';
    protected $hidden = [];
    protected $guarded = [];
    protected $dates = ['deleted_at'];
    
    /* ALTER TABLE `order_history` ADD `user_id` INT NOT NULL AFTER `order_history_id`; */
    protected $fillable = ['user_id', 'configuremacdbot_id', 'symbol', 'orderId', 'clientOrderId', 'transactTime', 'price', 'origQty', 'executedQty', 'status', 'timeInForce', 'type', 'side'];

}