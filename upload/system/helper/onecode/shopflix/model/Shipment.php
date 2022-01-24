<?php
namespace Onecode\Shopflix\Helper\Model;

class Shipment
{
    public static function getTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_shipment';
    }

    public static function getTrackingTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_shipment_tracking';
    }

    public static function getItemTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_shipment_items';
    }

}