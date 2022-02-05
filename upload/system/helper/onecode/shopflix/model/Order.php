<?php
namespace Onecode\Shopflix\Helper\Model;

use Onecode\ShopFlixConnector\Library\Connector;

class Order extends \Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->connector = new Connector(
            $this->model_extension_module_onecode_shopflix_config->apiUsername(),
            $this->model_extension_module_onecode_shopflix_config->apiPassword(),
            $this->model_extension_module_onecode_shopflix_config->apiUrl()
        );
    }

    public static function getTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_order';
    }

    public static function getRelationTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_oc_order';
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