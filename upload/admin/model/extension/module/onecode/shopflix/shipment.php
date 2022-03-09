<?php

use GuzzleHttp\Exception\ServerException;
use Onecode\Shopflix\Helper;
use Onecode\ShopFlixConnector\Library\Connector;
use Onecode\ShopFlixConnector\Library\Interfaces\OrderInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ShipmentInterface;

require_once DIR_SYSTEM . 'library/onecode/vendor/autoload.php';
require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Shipment.php';

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 * @property-read \Config $config
 * @property-read \Language $language
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Cart\Cart $cart
 * @property-read \Cart\User $user
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \GuzzleHttp\Client $client
 * @property-read \Onecode\ShopFlixConnector\Library\Connector $connector
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $model_extension_module_onecode_shopflix_order
 */
class ModelExtensionModuleOnecodeShopflixShipment extends Helper\Model\Shipment
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->helper('onecode/shopflix/model/Order');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->model('extension/module/onecode/shopflix/order');
        if ($this->model_extension_module_onecode_shopflix_config->apiUrl() != '')
        {
            $this->connector = new Connector(
                $this->model_extension_module_onecode_shopflix_config->apiUsername(),
                $this->model_extension_module_onecode_shopflix_config->apiPassword(),
                $this->model_extension_module_onecode_shopflix_config->apiUrl()
            );
        }
    }

    protected function createShipmentTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `order_id` INT UNSIGNED NOT NULL,
 `reference_id` varchar(255),
 `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `status` tinyint,
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`reference_id`),
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
        $this->update1_2_3();
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Shipment::getItemTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Shipment::getTrackingTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Shipment::getTableName()));
    }

    public function update1_2_3()
    {
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci', self::getTableName()));
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci', self::getTrackingTableName()));
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci', self::getItemTableName()));
    }

    public function getById($id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE id = '" . $id . "'";
        return $this->db->query($sql)->row;
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
        try
        {
            $this->db->query("START TRANSACTION;");
            $existing = $this->getByReferenceId($data['reference_id']);
            $response = (count($existing)) ? $this->update($existing['id'], $data) : $this->insert($data);
        }
        catch (\Exception $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            $this->db->query('ROLLBACK;');
            return null;
        }

        $this->db->query("COMMIT;");
        return $response;
    }

    private function update(int $id, array $data): ?array
    {
        $this->db->query("UPDATE " . self::getTableName() . " SET "
            . " `order_id` = " . $data['order_id']
            . ", `reference_id` = " . $data['reference_id']
            . ", `status` = " . $data['status']
            . " WHERE `id` = " . $id . ";");
        if (isset($data['items']) && is_array($data['items']) && count($data['items']))
        {
            array_walk($data['items'], function ($item) use ($id) {
                if (isset($item['id']))
                {
                    $this->db->query("UPDATE " . self::getItemTableName() . " SET "
                        . "`sku` = " . $this->db->escape($item['sku'])
                        . ",`shipment_id` = " . $id
                        . ",`quantity` = " . $item['quantity']
                        . " WHERE `id` = " . $item['id'] . ";"
                    );
                }
            });
        }
        if (isset($data['track']) && isset($data['track']['id']))
        {
            $this->updateTrackByShipment($id, $data['track']['number'], $data['track']['url']);
        }
        return $data;
    }

    public function updateTrackByShipment(int $shipment_id, string $number, string $url): ?array
    {
        $result = $this->getTrackByShipment($shipment_id);
        if (empty($results->rows))
        {
            $this->db->query("INSERT INTO " . self::getTrackingTableName()
                . " (`shipment_id`,`number`,`url`)"
                . " VALUES "
                . " (" . $shipment_id . ",'" . $number . "','" . $url . "')");
        }
        else
        {
            $this->db->query(sprintf('UPDATE %s SET `number` = \'%s\', `url` = \'%s\' WHERE `id` = %d'
                , self::getTrackingTableName(), $number, $url, $result['id']));
        }
        return $this->getTrackByShipment($shipment_id);
    }

    private function insert(array $data): ?array
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

        if (isset($data['track']))
        {
            $this->db->query("INSERT INTO " . self::getTrackingTableName()
                . " (`shipment_id`,`number`,`url`)"
                . " VALUES "
                . " (" . $shipment_id . ",'" . $data['track']['number'] . "','" . $data['track']['url'] . "')");

            $query = $this->db->query('SELECT COUNT(*) as c FROM ' . self::getTrackingTableName() . ' WHERE shipment_id = ' . $shipment_id);
            if (intval(count($query->rows) ? $query->row['c'] : '0') == 0)
            {
                throw new Exception('No tracking saved');
            }
        }

        $this->db->query("COMMIT;");
        return $data;
    }

    public function getTrackByShipment($id): array
    {
        $results = $this->db->query(sprintf('SELECT * FROM %s WHERE `shipment_id` = %d'
            , self::getTrackingTableName(), $id));
        return count($results->rows) == 0 ? [] : $results->rows[0];
    }

    public function getTotalOrders($data = []): int
    {
        $sql = sprintf("SELECT COUNT(DISTINCT o.id) AS total FROM %s AS o WHERE o.id > 0 ", self::getTableName());
        if (! empty($data['filter_reference_id']))
        {
            $sql .= " AND o.reference_id LIKE '" . $this->db->escape($data['filter_reference_id']) . "%'";
        }
        if (! empty($data['filter_status']))
        {
            $sql .= " AND o.status LIKE '" . $this->db->escape($data['filter_status']) . "%'";
        }
        if (! empty($data['filter_order']))
        {
            $sql .= " AND o.order_id = " . floatval($data['filter_order']);
        }
        $query = $this->db->query($sql);
        return (int) $query->row['total'];
    }

    public function getAllOrders($data = []): array
    {
        $sql = sprintf("SELECT DISTINCT * FROM %s AS o WHERE o.id > 0  ", self::getTableName());
        if (! empty($data['filter_reference_id']))
        {
            $sql .= " AND o.reference_id LIKE '" . $this->db->escape($data['filter_reference_id']) . "%'";
        }
        if (! empty($data['filter_status']))
        {
            $sql .= " AND o.status LIKE '" . $this->db->escape($data['filter_status']) . "%'";
        }
        if (! empty($data['filter_order']))
        {
            $sql .= " AND o.order_id = " . floatval($data['filter_order']);
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function isReadyToCreateVoucher($status): bool
    {
        return ($status == 1);
    }

    public function isPrintVoucherAvailable($status): bool
    {
        return ($status == 2);
    }

    public function createVoucher(array $shipment_ids): void
    {
        foreach ($shipment_ids as $id)
        {
            try
            {
                $shipment = $this->getById($id);
                if (count($shipment) == 0)
                {
                    continue;
                }
                $tracking = $this->getTrackByShipment($id);
                if (empty($tracking) || empty($tracking['number']) || $tracking['number'] == 'unknown number')
                {
                    $this->db->query('START TRANSACTION;');
                    $this->createVoucherForShipment($id, $shipment['reference_id']);
                    $this->db->query('COMMIT;');
                }
            }
            catch (\RuntimeException $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new RuntimeException($exception->getMessage());
            }
            catch (\LogicException $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new LogicException($exception->getMessage());
            }
            catch (\Exception $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new Exception($exception->getMessage());
            }
        }
    }

    private function createVoucherForShipment($shipment_id, $reference_id): array
    {
        try
        {
            $voucher = $this->connector->createVoucher($reference_id);
            $voucher = $voucher['voucher']['ShipmentNumber'] ?? null;
        }
        catch (ServerException $e)
        {
            $voucher = $this->connector->getVoucher($reference_id);
        }
        if ($voucher)
        {
            $trackingUrl = $this->connector->getShipmentUrl($reference_id);
            $this->updateTrackByShipment($shipment_id, $voucher, $trackingUrl);
        }
        return $this->getTrackByShipment($shipment_id);
    }

    public function printVoucherByShipments(array $shipment_ids): string
    {
        $voucher_list = [];
        foreach ($shipment_ids as $id)
        {
            $shipment = $this->getById($id);
            if (count($shipment) == 0)
            {
                continue;
            }
            $tracking = $this->getTrackByShipment($id);
            if (empty($tracking) || empty($tracking['number']) || $tracking['number'] == 'unknown number')
            {
                $tracking = $this->createVoucherForShipment($id, $shipment['reference_id']);
            }
            $voucher_list[] = $tracking['number'];
        }
        try
        {
            $voucherPdf = $this->connector->printVouchers($voucher_list);
            $fileContent = base64_decode($voucherPdf[0]['Voucher']);
            $order_list = [];
            $shipment_list = [];
            foreach ($shipment_ids as $id)
            {
                $shipment = $this->getById($id);
                if (count($shipment) == 0)
                {
                    continue;
                }
                $tracking = $this->getTrackByShipment($id);
                //print_r([$tracking]);
                if (in_array($tracking['number'], $voucher_list))
                {
                    $order_list[] = $shipment['order_id'];
                    $shipment_list[] = $shipment['id'];
                }
            }
            $order_list = array_unique($order_list);
            $shipment_list = array_unique($shipment_list);
            foreach ($order_list as $id)
            {
                $this->model_extension_module_onecode_shopflix_order->updateStatusReadyToBeShipped($id);
            }
            foreach ($shipment_list as $id)
            {
                $this->updateStatusVoucher($id);
            }
            return $fileContent;
        }
        catch (\RuntimeException $exception)
        {
            $this->db->query('ROLLBACK;');
            throw new RuntimeException($exception->getMessage());
        }
        catch (\LogicException $exception)
        {
            $this->db->query('ROLLBACK;');
            throw new LogicException($exception->getMessage());
        }
        catch (\Exception $exception)
        {
            $this->db->query('ROLLBACK;');
            throw new Exception($exception->getMessage());
        }
    }

    public function printManifest(array $shipment_ids): string
    {
        $list = [];
        foreach ($shipment_ids as $id)
        {
            $shipment = $this->getById($id);
            if (count($shipment) == 0)
            {
                continue;
            }
            $tracking = $this->getTrackByShipment($id);
            if (empty($tracking) || $tracking['number'] == 'unknown number')
            {
                continue;
            }
            $list[] = $shipment['reference_id'];
        }
        try
        {
            $manifest = $this->connector->printManifest($list);
            if (isset($manifest['status']) && $manifest['status'] == "error")
            {
                throw new \LogicException($manifest['message']);
            }
            return base64_decode($manifest['manifest']);
        }
        catch (\RuntimeException $exception)
        {
            $this->db->query('ROLLBACK;');
            throw new RuntimeException($exception->getMessage());
        }
        catch (\LogicException $exception)
        {
            $this->db->query('ROLLBACK;');
            throw new LogicException($exception->getMessage());
        }
        catch (\Exception $exception)
        {
            $this->db->query('ROLLBACK;');
            throw new Exception($exception->getMessage());
        }
    }

    protected function clearShipments(int $order_id): void
    {
        $this->db->query(sprintf('DELETE FROM %s WHERE order_id = %d; ', self::getTableName(), $order_id));
    }

    protected function storeShipment(int $id, array $shipments): array
    {
        $ship = [];
        $to_save = [];
        array_walk($shipments, function ($row) use (&$to_save, $id) {
            $shipment_data = $row['shipment'];
            $tracks_data = $row['tracks'];
            $items_row = $row['items'];

            //print_r($shipment_data);
            $o_s = [
                'order_id' => $id,
                'reference_id' => $shipment_data[ShipmentInterface::INCREMENT_ID],
                'status' => $shipment_data[ShipmentInterface::SHIPMENT_STATUS],
                'created_at' => gmdate("Y-m-d\TH:i:s\Z", $shipment_data[ShipmentInterface::CREATED_AT]),
            ];
            //$o_s['track'] = [
            //    'number' => $tracks_data[ShipmentTrackInterface::TRACK_NUMBER] != '' ? $tracks_data[ShipmentTrackInterface::TRACK_NUMBER] : "unknown number",
            //    'url' => $tracks_data[ShipmentTrackInterface::TRACKING_URL],
            //];
            array_walk($items_row, function ($item) use (&$o_s) {
                $o_s['items'][] = [
                    "sku" => $item['sku'],
                    "quantity" => $item['qty'],
                ];
            });
            $to_save[] = $o_s;
        });
        foreach ($to_save as $shipment)
        {
            $stored = $this->save($shipment);
            if (is_null($stored))
            {
                throw new LogicException('Error during shipment save');
            }
            $ship[] = $stored;
        }
        return $ship;
    }

    public function updateStatusPending($id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= 1 WHERE `id` = ' . $id . ';');
    }

    public function updateStatusVoucher($id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= 2 WHERE `id` = ' . $id . ';');
    }

    public function updateStatusComplete($id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= 3 WHERE `id` = ' . $id . ';');
    }

    public function shipment(array $orders): array
    {
        $shipments = [];
        foreach ($orders as $order)
        {
            try
            {
                if (! isset($order['status']) || $order['status'] != OrderInterface::STATUS_PICKING)
                {
                    continue;
                }
                $this->db->query('START TRANSACTION;');
                $ship = $this->connector->getShipment($order['reference_id']);
                $this->clearShipments($order['id']);
                $shipments = array_merge($shipments, $this->storeShipment($order['id'], $ship));
                $this->db->query('COMMIT;');
            }
            catch (\RuntimeException $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new RuntimeException($exception->getMessage());
            }
            catch (\LogicException $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new LogicException($exception->getMessage());
            }
            catch (\Exception $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new Exception($exception->getMessage());
            }
        }
        return $shipments;
    }
}