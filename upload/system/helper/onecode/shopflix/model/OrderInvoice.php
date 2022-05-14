<?php
namespace Onecode\Shopflix\Helper\Model;

use Model;
use const DB_PREFIX;

class OrderInvoice extends Model
{
    public static function getTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_order_invoice';
    }

    public static function getOrderTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_order';
    }
}