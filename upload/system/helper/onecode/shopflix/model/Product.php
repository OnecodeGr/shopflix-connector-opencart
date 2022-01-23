<?php
namespace Onecode\Shopflix\Helper\Model;

class Product
{
    public static function getTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_product_xml';
    }

}