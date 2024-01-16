<?php
/**
 * Created by PhpStorm.
 * User: chester
 * Date: 2021-12-07
 * Time: 10:23
 */

namespace App\Helper;


class Format
{
    public static function currencyAsNumber($value): string
    {
        return number_format((float)$value / 100, 2, '.', '');
    }

    public static function currencyAsString($value, $has_been_divided = false): string
    {
        if (!$has_been_divided) {
            $value = (float)$value / 100;
        }
        return number_format((float)$value, 2);
    }
}