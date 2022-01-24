<?php

use Onecode\Shopflix\Helper;

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 */
class ModelExtensionModuleOnecodeShopflixShipment extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->helper('onecode/shopflix/model/Shipment');
        $this->load->helper('onecode/shopflix/model/Order');
    }

    protected function createShipmentTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `order_id` INT UNSIGNED NOT NULL,
 `refernce_id` varchar(255),
 `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `status` tinyint,
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`refernce_id`),
    FOREIGN KEY (order_id) REFERENCES %s(id) ON DELETE CASCADE ON UPDATE CASCADE 
)", Helper\Model\Shipment::getTableName(), Helper\Model\Order::getTableName()));
    }

    protected function createTrackingTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `shipment_id` INT UNSIGNED NOT NULL,
 `number` varchar(255),
 `url` varchar(255),
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`shipment_id`,`number`,`url`),
    FOREIGN KEY (shipment_id) REFERENCES %s(id) ON DELETE CASCADE ON UPDATE CASCADE
    )", Helper\Model\Shipment::getTrackingTableName(), Helper\Model\Shipment::getTableName()));
    }

    protected function createItemTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `shipment_id` INT UNSIGNED NOT NULL,
 `sku` varchar(255),
 `quantity` SMALLINT UNSIGNED,
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`shipment_id`,`sku`),
    FOREIGN KEY (shipment_id) REFERENCES %s(id) ON DELETE CASCADE ON UPDATE CASCADE
    )", Helper\Model\Shipment::getItemTableName(), Helper\Model\Shipment::getTableName()));
    }

    public function install()
    {
        $this->createShipmentTable();
        $this->createTrackingTable();
        $this->createItemTable();
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Shipment::getItemTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Shipment::getTrackingTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Shipment::getTableName()));
    }
}