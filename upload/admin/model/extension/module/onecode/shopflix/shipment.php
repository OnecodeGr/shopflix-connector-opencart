<?php

use Onecode\Shopflix\Helper;

require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Shipment.php';

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 */
class ModelExtensionModuleOnecodeShopflixShipment extends Helper\Model\Shipment
{
    public function __construct($registry)
    {
        parent::__construct($registry);
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
)", self::getTableName(), Helper\Model\Order::getTableName()));
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

    public function getByReferenceId($id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE reference_id = '" . $id . "'";
        return $this->db->query($sql)->row;
    }

    public function getByOrderId($id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE order_id = '" . $id . "'";
        return $this->db->query($sql)->rows;
    }

    public function getTrackingDataByProduct($order_id, $sku): array
    {
        $sql = [
            sprintf("SELECT t.*,s.reference_id FROM %s as t", self::getTrackingTableName()),
            sprintf("INNER JOIN %s AS s ON s.id = t.shipment_id AND s.order_id = %d",
                self::getTableName(), $order_id),
            sprintf(" INNER JOIN %s AS p ON p.shipment_id = t.shipment_id", self::getItemTableName()),
            sprintf(" AND p.sku = '%s'", $sku),
        ];
        $sql = implode(' ', $sql);
        return $this->db->query($sql)->rows;
    }

    public function save(array $data): ?array
    {
        $existing = $this->getByReferenceId($data['reference_id']);
        if (count($existing))
        {
            return $existing;
        }
        $this->db->query("START TRANSACTION;");
        try
        {
            $this->db->query("INSERT INTO " . self::getTableName()
                . " (`order_id`,`reference_id`,`created_at`,`status`)"
                . " VALUES "
                . " ('" . $data['order_id'] . "','" . $data['reference_id'] . "','" . $data['created_at'] . "'," . $data['status'] . ")");
            $query = $this->db->query('SELECT id FROM ' . self::getTableName() . ' WHERE reference_id = \'' . $data['reference_id'] . '\' LIMIT 1;');
            $shipment_id = intval(count($query->rows) ? $query->row['id'] : '0');
            $data['id'] = $shipment_id;
            if ($data['id'] == 0)
            {
                throw new Exception('No shipment saved');
            }

            array_walk($data['items'], function ($item) use ($shipment_id) {
                $this->db->query("INSERT INTO " . self::getItemTableName() .
                    "(`sku`,`shipment_id`,`quantity`)" .
                    " VALUES " .
                    "('" . $this->db->escape($item['sku']) . "'," . $shipment_id . "," . $item['quantity'] . ")"
                );
            });
            $query = $this->db->query('SELECT COUNT(*) as c FROM ' . self::getItemTableName() . ' WHERE shipment_id = ' . $shipment_id);
            if (intval(count($query->rows) ? $query->row['c'] : '0') == 0)
            {
                throw new Exception('No item saved');
            }

            $this->db->query("INSERT INTO " . self::getTrackingTableName()
                . " (`shipment_id`,`number`,`url`)"
                . " VALUES "
                . " (" . $shipment_id . ",'" . $data['track']['number'] . "','" . $data['track']['url'] . "')");

            $query = $this->db->query('SELECT COUNT(*) as c FROM ' . self::getTrackingTableName() . ' WHERE shipment_id = ' . $shipment_id);
            if (intval(count($query->rows) ? $query->row['c'] : '0') == 0)
            {
                throw new Exception('No tracking saved');
            }

            $this->db->query("COMMIT;");
            return $data;
        }
        catch (\Exception $e)
        {
            error_log($e->getMessage());
            $this->db->query('ROLLBACK;');
            return null;
        }
    }
}