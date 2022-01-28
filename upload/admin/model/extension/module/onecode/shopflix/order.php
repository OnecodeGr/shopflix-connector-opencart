<?php

use Onecode\Shopflix\Helper;

require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Order.php';

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 */
class ModelExtensionModuleOnecodeShopflixOrder extends Helper\Model\Order
{
    protected function createOrderTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `refernce_id` varchar(255),
 `status` varchar(255),
 `sub_total` decimal(10,3),
 `discount_amount` decimal(10,3),
 `total_paid` decimal(10,3),
 `customer_email` varchar(255),
 `customer_firstname` varchar(255),
 `customer_lastname` varchar(255),
 `customer_remote_ip` varchar(255),
 `customer_note` varchar(255),
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`refernce_id`)
)", self::getTableName()));
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
)", self::getAddressTableName(), self::getTableName()));
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
)", self::getItemTableName(), self::getTableName()));
    }

    public function install()
    {
        $this->createOrderTable();
        $this->createOrderAddressTable();
        $this->createOrderItemTable();
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getItemTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getAddressTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getTableName()));
    }

    public function getOrderById($order_id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE id = " . intval($order_id);
        return $this->db->query($sql)->row;
    }

    public function getTotalOrders($data = []): int
    {
        $sql = sprintf("SELECT COUNT(DISTINCT o.product_id) AS total FROM %s AS o", self::getTableName());
        if (! empty($data['filter_reference_id']))
        {
            $sql .= " AND o.reference_id LIKE '" . $this->db->escape($data['filter_reference_id']) . "%'";
        }
        if (! empty($data['filter_customer_email']))
        {
            $sql .= " AND o.customer_email LIKE '" . $this->db->escape($data['filter_customer_email']) . "%'";
        }
        if (! empty($data['filter_sub_total']))
        {
            $sql .= " AND o.sub_total = " . floatval($data['filter_sub_total']);
        }
        if (! empty($data['filter_total_paid']))
        {
            $sql .= " AND o.total_paid = " . floatval($data['filter_total_paid']);
        }
        $query = $this->db->query($sql);
        return (int) $query->row['total'];
    }

    public function getAllOrders($data = []): array
    {
        $sql = sprintf("SELECT DISTINCT * FROM %s AS o", self::getTableName());
        if (! empty($data['filter_reference_id']))
        {
            $sql .= " AND o.reference_id LIKE '" . $this->db->escape($data['filter_reference_id']) . "%'";
        }
        if (! empty($data['filter_customer_email']))
        {
            $sql .= " AND o.customer_email LIKE '" . $this->db->escape($data['filter_customer_email']) . "%'";
        }
        if (! empty($data['filter_sub_total']))
        {
            $sql .= " AND o.sub_total = " . floatval($data['filter_sub_total']);
        }
        if (! empty($data['filter_total_paid']))
        {
            $sql .= " AND o.total_paid = " . floatval($data['filter_total_paid']);
        }
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function accept($order_id): void
    {

    }

    public function decline($order_id): void
    {
    }
}