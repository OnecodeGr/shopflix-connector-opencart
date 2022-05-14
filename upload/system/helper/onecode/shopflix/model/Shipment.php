<?php
namespace Onecode\Shopflix\Helper\Model;

use Model;
use const DB_PREFIX;

class Shipment extends Model
{
    public static function getTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_shipment';
    }

    public static function getTrackingTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_shipment_tracking';
    }

    public static function getItemTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_shipment_items';
    }

}