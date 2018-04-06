<?php
/**
 * Created by PhpStorm.
 * User: joeldg
 * Date: 12/22/17
 * Time: 5:45 PM
 */

namespace App\Traits;

use App\Models;

/**
 * Class Config
 * @package Bowhead\Traits
 */
trait Config
{
    /**
     * @param $val
     *
     * @return bool|\Illuminate\Database\Eloquent\Model|mixed|null|string|static
     */
    public static function bowhead_config($val) {
        return false;
    }
}