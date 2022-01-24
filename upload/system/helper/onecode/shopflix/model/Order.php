<?php
namespace Onecode\Shopflix\Helper\Model;

class Order
{
    public static function getTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_order';
    }

    public static function getAddressTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_order_address';
    }

    public static function getItemTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_order_items';
    }

}