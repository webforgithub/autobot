<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class BalanceMaster extends Model {

    use SoftDeletes;
    protected $table = 'balance_master';
    protected $hidden = [];
    protected $guarded = [];
    protected $dates = ['deleted_at'];
    
    /* ALTER TABLE `order_history` ADD `user_id` INT NOT NULL AFTER `order_history_id`; */
    protected $fillable = ['bh_exchanges_id', 'user_id', 'symbol', 'available_balance', 'allocated_balance'];
}