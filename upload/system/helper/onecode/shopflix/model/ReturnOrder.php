<?php
namespace Onecode\Shopflix\Helper\Model;

use Model;
use const DB_PREFIX;

require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Order.php';

class ReturnOrder extends Model
{
    public static function getTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_return_order';
    }

    public static function getOrderTableName(): string
    {
        return Order::getTableName();
    }

    public static function getAddressTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_return_order_address';
    }

    public static function getItemTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_return_order_items';
    }
}