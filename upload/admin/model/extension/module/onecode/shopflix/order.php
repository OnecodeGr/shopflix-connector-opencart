<?php

use Onecode\Shopflix\Helper;

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 */
class ModelExtensionModuleOnecodeShopflixOrder extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->helper('onecode/shopflix/model/Order');
    }

    protected function createOrderTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `refernce_id` varchar(255),
 `status` varchar(255),
 `sub_total` decimal(10,3),
 `dicount_amount` decimal(10,3),
 `total_paid` decimal(10,3),
 `customer_email` varchar(255),
 `customer_firstname` varchar(255),
 `customer_lastname` varchar(255),
 `customer_remote_ip` varchar(255),
 `customer_note` varchar(255),
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`refernce_id`)
)", Helper\Model\Order::getTableName()));
    }

    protected function createOrderAddressTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `order_id` INT UNSIGNED NOT NULL,
 `firstname` varchar(255),
 `lastname` varchar(255),
 `postcode` varchar(255),
 `telephone` varchar(255),
 `street` varchar(255),
 `type` varchar(255),
 `city` varchar(255),
 `email` varchar(255),
 `country_id` varchar(255),
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`order_id`,`type`),
    FOREIGN KEY (order_id) REFERENCES %s(id) ON DELETE CASCADE ON UPDATE CASCADE
)", Helper\Model\Order::getAddressTableName(),Helper\Model\Order::getTableName()));
    }

    protected function createOrderItemTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `order_id` INT UNSIGNED NOT NULL,
 `sku` varchar(255),
 `price` decimal(10,3),
 `quantity` SMALLINT UNSIGNED,
 PRIMARY KEY (`id`),
    FOREIGN KEY (order_id) REFERENCES %s(id) ON DELETE CASCADE ON UPDATE CASCADE
)", Helper\Model\Order::getItemTableName(),Helper\Model\Order::getTableName()));
    }

    public function install()
    {
        $this->createOrderTable();
        $this->createOrderAddressTable();
        $this->createOrderItemTable();
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Order::getItemTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Order::getAddressTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Order::getTableName()));
    }
}