<?php
namespace Onecode\Shopflix\Helper\Model;

use Model;
use const DB_PREFIX;

class Order extends Model
{
    public static function getTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_order';
    }

    public static function getRelationTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_oc_order';
    }

    public static function getAddressTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_order_address';
    }

    public static function getItemTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_order_items';
    }

}