<?php

use GuzzleHttp\Client;
use Onecode\Shopflix\Helper;
use Onecode\ShopFlixConnector\Library\Connector;
use Onecode\ShopFlixConnector\Library\Interfaces\OrderInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ReturnOrderInterface;

require_once DIR_SYSTEM . 'library/onecode/vendor/autoload.php';
require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/ReturnOrder.php';

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
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingStore $model_setting_store
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $config_model
 */
class ModelExtensionModuleOnecodeShopflixReturnOrder extends Helper\Model\ReturnOrder
{
    const ADDRESS_TYPE_BILLING = 'billing';
    const ADDRESS_TYPE_SHIPPING = 'shipping';

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/store');
        $this->load->model('setting/extension');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->config_model = new ModelExtensionModuleOnecodeShopflixConfig($registry);
        if ($this->config_model->apiUrl() != '')
        {
            $this->connector = new Connector(
                $this->config_model->apiUsername(),
                $this->config_model->apiPassword(),
                $this->config_model->apiUrl()
            );
        }
    }

    protected function createOrderTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
 `order_id` INT UNSIGNED NOT NULL,
 `vendor_parent_id` varchar(255),
 `reference_id` varchar(255),
 `state` varchar(255),
 `status` varchar(255),
 `sub_total` decimal(10,3),
 `total_paid` decimal(10,3),
 `customer_email` varchar(255),
 `customer_firstname` varchar(255),
 `customer_lastname` varchar(255),
 `customer_remote_ip` varchar(255),
 `customer_note` varchar(255),
 `created_at` timestamp not null,
 `update_at` timestamp default current_timestamp not null,
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`reference_id`),
 INDEX (`order_id`),
 FOREIGN KEY (order_id) REFERENCES %s(id) ON DELETE CASCADE ON UPDATE CASCADE
)", self::getTableName(), self::getOrderTableName()));
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
 `reason` TEXT,
 PRIMARY KEY (`id`),
    FOREIGN KEY (order_id) REFERENCES %s(id) ON DELETE CASCADE ON UPDATE CASCADE
)", self::getItemTableName(), self::getTableName()));
    }

    public function install()
    {
        //$this->createOrderTable();
        //$this->createOrderAddressTable();
        //$this->createOrderItemTable();
        //$this->update_character_collection();
    }

    public function uninstall()
    {
        //$this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getItemTableName()));
        //$this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getAddressTableName()));
        //$this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getTableName()));
    }

    public function update_character_collection()
    {
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getTableName()));
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getItemTableName()));
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getAddressTableName()));
    }

    public function getOrderById($order_id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE id = " . intval($order_id) . " LIMIT 1;";
        $query = $this->db->query($sql);
        return count($query->rows) ? $query->row : [];
    }

    public function getOrderAddress($order_id): array
    {
        $sql = "SELECT * FROM " . self::getAddressTableName() . " WHERE order_id = " . intval($order_id);
        return $this->db->query($sql)->rows;
    }

    public function getOrderItems($order_id): array
    {
        $sql = "SELECT * FROM " . self::getItemTableName() . " WHERE order_id = " . intval($order_id);
        return $this->db->query($sql)->rows;
    }

    public function getTotalOrders($data = []): int
    {
        $sql = sprintf("SELECT COUNT(DISTINCT o.id) AS total FROM %s AS o WHERE o.id > 0 ", self::getTableName());
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
        $sql = sprintf("SELECT DISTINCT * FROM %s AS o WHERE o.id > 0  ", self::getTableName());
        if (! empty($data['filter_reference_id']))
        {
            $sql .= " AND o.reference_id LIKE '" . $this->db->escape($data['filter_reference_id']) . "%'";
        }
        if (! empty($data['filter_related_order']))
        {
            $sql .= " AND o.vendor_parent_id LIKE '" . $this->db->escape($data['filter_related_order']) . "%'";
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

        $sort_data = [
            'o.reference_id',
            'o.sub_total',
            'o.discount_amount',
            'o.total_paid',
            'o.status',
            'o.customer_email',
            'o.id',
        ];

        $sql .= (isset($data['sort']) && in_array($data['sort'], $sort_data))
            ? " ORDER BY " . $data['sort']
            : " ORDER BY o.reference_id";

        $sql .= isset($data['order']) && ($data['order'] == 'DESC') ? " DESC" : " ASC";

        if (isset($data['start']) || isset($data['limit']))
        {
            $data['start'] = max($data['start'], 0);
            $data['limit'] = $data['limit'] < 1 ? 20 : $data['limit'];
            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function accept(array $order_ids): void
    {
        foreach ($order_ids as $id)
        {
            try
            {
                $order = $this->getOrderById($id);
                if ($order['status'] != ReturnOrderInterface::STATUS_ON_THE_WAY_TO_THE_STORE)
                {
                    continue;
                }
                $this->db->query('START TRANSACTION;');
                $this->connector->approveReturnedOrder($order['reference_id']);
                $this->updateStatusAccept($id);
                $this->db->query('COMMIT;');
            }
            catch (RuntimeException $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new RuntimeException($exception->getMessage());
            }
            catch (LogicException $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new LogicException($exception->getMessage());
            }
            catch (Exception $exception)
            {
                $this->db->query('ROLLBACK;');
                throw new Exception($exception->getMessage());
            }
        }
    }

    public function decline($order_ids, string $decline_message): void
    {
        foreach ($order_ids as $id)
        {
            $order = $this->getOrderById($id);
            if ($order['status'] != ReturnOrderInterface::STATUS_ON_THE_WAY_TO_THE_STORE)
            {
                continue;
            }

            $this->db->query('START TRANSACTION;');
            $this->connector->declineReturnedOrder($order['reference_id']);
            $this->updateStatusDecline($id);
            $this->db->query('COMMIT;');
        }
    }

    private function updateStatusAccept($order_id)
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= \'' .
            ReturnOrderInterface::STATUS_RETURN_APPROVED . '\' WHERE `id` = ' . $order_id . ';');
    }

    private function updateStatusDecline($order_id)
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= \'' .
            ReturnOrderInterface::STATUS_RETURN_DECLINED . '\' WHERE `id` = ' . $order_id . ';');
    }

    public function getOrderByReferenceId($id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE reference_id = '" . $id . "'";
        return $this->db->query($sql)->row;
    }

    public function getParentOrderByReferenceId($id): array
    {
        $sql = "SELECT * FROM " . self::getOrderTableName() . " WHERE reference_id = '" . $id . "'";
        return $this->db->query($sql)->row;
    }

    public function save(array $data): ?array
    {
        $existing_order = $this->getOrderByReferenceId($data['reference_id']);
        if (count($existing_order))
        {
            return $existing_order;
        }
        $this->db->query("START TRANSACTION;");
        $parent_order = $this->getParentOrderByReferenceId($data['vendor_parent_id']);
        try
        {
            $this->db->query("INSERT INTO " . self::getTableName() .
                "(
                `reference_id`,
                `vendor_parent_id`,
                `order_id`,
                `status`,
                `state`,
                `sub_total`,
                `discount_amount`,
                `total_paid`,
                `customer_email`,
                `customer_firstname`,
                `customer_lastname`,
                `customer_remote_ip`,
                `customer_note`,
                `created_at`
                )" .
                " VALUES " .
                "('" .
                $this->db->escape($data['reference_id']) . "','" .
                $this->db->escape($data['vendor_parent_id']) . "','" .
                (int) $parent_order['id'] . "','" .
                $data['status'] . "','" .
                $data['state'] . "'," .
                $data['sub_total'] . "," .
                $data['discount_amount'] . "," .
                $data['total_paid'] . ",'" .
                $data['customer_email'] . "','" .
                $this->db->escape($data['customer_firstname']) . "','" .
                $this->db->escape($data['customer_lastname']) . "','" .
                $this->db->escape($data['customer_remote_ip']) . "','" .
                $this->db->escape($data['customer_note']) . "',
                now()
                )"
            );
            $query = $this->db->query('SELECT id FROM ' . self::getTableName() . ' WHERE reference_id = \'' . $data['reference_id'] . '\' LIMIT 1;');
            $order_id = intval(count($query->rows) ? $query->row['id'] : '0');
            $data['id'] = $order_id;
            if ($data['id'] == 0)
            {
                throw new Exception('No order saved');
            }
            //Store Address
            array_walk($data['address'], function ($item) use ($order_id) {
                $query = "INSERT INTO " . self::getAddressTableName() .
                    "(`order_id`,`firstname`,`lastname`,`postcode`,`telephone`,`street`,`type`,`city`,`email`,`country_id`)" .
                    " VALUES " .
                    "(" . $order_id . ",'" . $this->db->escape($item['firstname']) . "','" . $this->db->escape($item['lastname']) . "','"
                    . $this->db->escape($item['postcode']) . "','" . $item['telephone'] . "','"
                    . $this->db->escape($item['street']) . "','" . $item['type'] . "','" . $this->db->escape($item['city']) .
                    "','" . $item['email'] . "','" . $this->db->escape($item['country_id']) . "')";
                $this->db->query($query);
            });
            //Store Items
            array_walk($data['items'], function ($item) use ($order_id) {
                $this->db->query("INSERT INTO " . self::getItemTableName() .
                    "(
                        `sku`,
                        `order_id`,
                        `price`,
                        `quantity`,
                        `reason`
                    )" .
                    " VALUES " .
                    "('" .
                        $this->db->escape($item['sku']) . "'," .
                        $order_id . "," .
                        $item['price'] . "," .
                        $item['quantity'] .
                        $item['reason'] .
                    ")"
                );
            });

            $query = $this->db->query('SELECT COUNT(*) as c FROM ' . self::getAddressTableName() . ' WHERE order_id = ' . $order_id);
            if (intval(count($query->rows) ? $query->row['c'] : '0') == 0)
            {
                throw new Exception('No address saved');
            }
            $query = $this->db->query('SELECT COUNT(*) as c FROM ' . self::getItemTableName() . ' WHERE order_id = ' . $order_id);
            if (intval(count($query->rows) ? $query->row['c'] : '0') == 0)
            {
                throw new Exception('No item saved');
            }
            $this->db->query("COMMIT;");
            return $data;
        }
        catch (Exception $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            $this->db->query('ROLLBACK;');
            return null;
        }
    }
}