<?php

use GuzzleHttp\Client;
use Onecode\Shopflix\Helper;
use Onecode\ShopFlixConnector\Library\Connector;
use Onecode\ShopFlixConnector\Library\Interfaces\OrderInterface;

require_once DIR_SYSTEM . 'library/onecode/vendor/autoload.php';
require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/OrderInvoice.php';

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 * @property-read \Config $config
 * @property-read \Language $language
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Cart\Cart $cart
 * @property-read \ModelSaleOrder $model_sale_order
 * @property-read \ModelUserApi $model_user_api
 * @property-read \Cart\User $user
 * @property-read \ModelCatalogProduct $model_catalog_product
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingStore $model_setting_store
 * @property-read \ModelLocalisationCurrency $model_localisation_currency
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \ModelExtensionModuleOnecodeShopflixApi $model_extension_module_onecode_shopflix_api
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $model_extension_module_onecode_shopflix_shipment
 * @property-read \GuzzleHttp\Client $client
 * @property-read \Onecode\ShopFlixConnector\Library\Connector $connector
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $shipment_model
 * @property-read \ModelExtensionModuleOnecodeShopflixApi $api_model
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $config_model
 */
class ModelExtensionModuleOnecodeShopflixOrderInvoice extends Helper\Model\OrderInvoice
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/store');
        $this->load->model('setting/store');
        $this->load->model('setting/extension');
        $this->load->model('localisation/currency');
        $this->load->model('user/api');
        $this->load->model('catalog/product');
        $this->load->model('extension/module/onecode/shopflix/api');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->model('extension/module/onecode/shopflix/shipment');
        $this->shipment_model = new ModelExtensionModuleOnecodeShopflixShipment($registry);
        $this->api_model = new ModelExtensionModuleOnecodeShopflixApi($registry);
        $this->config_model = new ModelExtensionModuleOnecodeShopflixConfig($registry);
    }

    protected function createInvoiceTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `order_id` INT UNSIGNED NOT NULL,
 `name` varchar(255),
 `address` varchar(255),
 `owner` varchar(255),
 `vat` varchar(255),
 `tax_office` varchar(255),
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`order_id`, `id`),
 FOREIGN KEY (order_id) REFERENCES %s(id) ON UPDATE CASCADE ON DELETE RESTRICT
)", self::getTableName(), self::getOrderTableName()));

        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getTableName()));
    }

    public function install()
    {
        $this->createInvoiceTable();
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getTableName()));
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function save(array $data){
        $query = "INSERT INTO " . self::getTableName() .
            "(`order_id`,`name`,`address`,`owner`,`vat`,`tax_office`)" .
            " VALUES " .
            "(" . $data['order_id'] . ",'" . $this->db->escape($data['name']) . "','" . $this->db->escape($data['address']) . "','"
            . $this->db->escape($data['owner']) . "','" . $data['vat'] . "','"
            . $this->db->escape($data['tax_office']) . "')";
        $this->db->query($query);
    }

    /**
     * @param $order_id
     *
     * @return array
     */
    public function getByOrder($order_id)
    {
        $query = sprintf("SELECT * FROM %s WHERE `order_id` = %d LIMIT 1", self::getTableName(), $order_id);
        return $this->db->query($query)->row;
    }
}