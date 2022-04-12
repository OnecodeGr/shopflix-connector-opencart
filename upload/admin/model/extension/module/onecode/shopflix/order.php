<?php

use GuzzleHttp\Client;
use Onecode\Shopflix\Helper;
use Onecode\ShopFlixConnector\Library\Connector;
use Onecode\ShopFlixConnector\Library\Interfaces\OrderInterface;

require_once DIR_SYSTEM . 'library/onecode/vendor/autoload.php';
require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Order.php';

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
 * @property-read \ModelExtensionModuleOnecodeShopflixOrderInvoice
 *     $model_extension_module_onecode_shopflix_order_invoice
 * @property-read \ModelExtensionModuleOnecodeShopflixOrderInvoice $order_invoice_model
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \ModelExtensionModuleOnecodeShopflixApi $model_extension_module_onecode_shopflix_api
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $model_extension_module_onecode_shopflix_shipment
 * @property-read \GuzzleHttp\Client $client
 * @property-read \Onecode\ShopFlixConnector\Library\Connector $connector
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $shipment_model
 * @property-read \ModelExtensionModuleOnecodeShopflixApi $api_model
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $config_model
 */
class ModelExtensionModuleOnecodeShopflixOrder extends Helper\Model\Order
{
    const ADDRESS_TYPE_BILLING = 'billing';
    const ADDRESS_TYPE_SHIPPING = 'shipping';

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('sale/order');
        $this->load->model('setting/store');
        $this->load->model('setting/store');
        $this->load->model('setting/extension');
        $this->load->model('localisation/currency');
        $this->load->model('user/api');
        $this->load->model('catalog/product');
        $this->load->model('extension/module/onecode/shopflix/api');
        $this->load->model('extension/module/onecode/shopflix/order_invoice');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->model('extension/module/onecode/shopflix/shipment');
        $this->shipment_model = new ModelExtensionModuleOnecodeShopflixShipment($registry);
        $this->api_model = new ModelExtensionModuleOnecodeShopflixApi($registry);
        $this->config_model = new ModelExtensionModuleOnecodeShopflixConfig($registry);
        $this->order_invoice_model = new ModelExtensionModuleOnecodeShopflixOrderInvoice($registry);
        $catalog = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
        $catalog = parse_url($catalog, PHP_URL_HOST) == 'opencart.test' ? 'http://apache/' : $catalog;
        $this->client = new Client(['base_uri' => $catalog . 'index.php']);
        if ($this->model_extension_module_onecode_shopflix_config->apiUrl() != '')
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
 `reference_id` varchar(255),
 `state` varchar(255),
 `status` varchar(255),
 `sub_total` decimal(10,3),
 `discount_amount` decimal(10,3),
 `total_paid` decimal(10,3),
 `customer_email` varchar(255),
 `customer_firstname` varchar(255),
 `customer_lastname` varchar(255),
 `customer_remote_ip` varchar(255),
 `customer_note` varchar(255),
 `created_at` timestamp not null,
 `update_at` timestamp default current_timestamp not null,
 PRIMARY KEY (`id`),
 UNIQUE INDEX (`reference_id`)
)", self::getTableName()));
    }

    protected function createOrderRelationTable()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . self::getRelationTableName() . " (
 `shopflix_id` INT UNSIGNED NOT NULL,
 `oc_id` INT(11) UNSIGNED NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`shopflix_id`,`oc_id`),
FOREIGN KEY (shopflix_id) REFERENCES " . self::getTableName() . "(id) ON UPDATE CASCADE ON DELETE RESTRICT)");
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
        $this->createOrderRelationTable();
        $this->createOrderAddressTable();
        $this->createOrderItemTable();
        $this->update1_2_3();
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getRelationTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getItemTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getAddressTableName()));
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getTableName()));
    }

    public function update1_2_3()
    {
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getTableName()));
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getRelationTableName()));
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getItemTableName()));
        $this->db->query(sprintf('alter table %s convert to character set utf8 collate utf8_general_ci;', self::getAddressTableName()));
    }

    public function getOrderById($order_id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE id = " . intval($order_id) . " LIMIT 1;";
        $query = $this->db->query($sql);
        return count($query->rows) ? $query->row : [];
    }

    public function getLinkedOrder($shopflix_id): array
    {
        $query = $this->db->query(sprintf('SELECT * FROM %s WHERE `shopflix_id` = %d LIMIT 1;',
            self::getRelationTableName(), $shopflix_id));
        return count($query->rows) ? $query->row : [];
    }

    public function getOrderByReferenceId($id): array
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE reference_id = '" . $id . "'";
        return $this->db->query($sql)->row;
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
            : " ORDER BY pd.name";

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

    public function getOrderItems($order_id): array
    {
        $sql = "SELECT * FROM " . self::getItemTableName() . " WHERE order_id = " . intval($order_id);
        return $this->db->query($sql)->rows;
    }

    public function getOrderInvoiceData($order_id): array
    {
        return $this->order_invoice_model->getByOrder($order_id);
    }

    public function getOrderAddress($order_id): array
    {
        $sql = "SELECT * FROM " . self::getAddressTableName() . " WHERE order_id = " . intval($order_id);
        return $this->db->query($sql)->rows;
    }

    public function shipment(array $order_ids): array
    {
        $orders = [];
        foreach ($order_ids as $id)
        {
            $order = $this->getOrderById($id);
            if ($order['status'] != OrderInterface::STATUS_PICKING)
            {
                continue;
            }
            $orders[] = $order;
        }

        if (count($orders) == 0)
        {
            return [];
        }
        return $this->shipment_model->shipment($orders);
    }

    public function accept(array $order_ids): void
    {
        foreach ($order_ids as $id)
        {
            try
            {
                $oc_id = null;
                $order = $this->getOrderById($id);
                if ($order['status'] != OrderInterface::STATUS_PENDING_ACCEPTANCE)
                {
                    continue;
                }
                $this->db->query('START TRANSACTION;');

                if ($this->config_model->convertOrders())
                {
                    $oc_id = $this->createOpenCartOrder($id);
                    $this->connector->picking($order['reference_id']);
                    $this->acceptOrderDB($id, $oc_id);
                }
                else
                {
                    error_log(sprintf('Class: %s, method: %s, warning: %s', __CLASS__, __METHOD__, 'Conversion is disabled'));
                    $this->connector->picking($order['reference_id']);
                    $this->updateStatusPicking($id);
                }
                $this->db->query('COMMIT;');
            }
            catch (RuntimeException $exception)
            {
                $this->db->query('ROLLBACK;');
                //Error during shopflix communication (manual rollback for no innodb)
                if (! is_null($oc_id))
                {
                    $token = $this->api_model->apiLogin();
                    try
                    {
                        $this->api_model->apiOrderDelete($oc_id, $token);
                    }
                    catch (Exception $exception)
                    {
                        error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $exception->getMessage()));
                    }
                    $this->api_model->apiLogout($token);
                }
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

    private function fineTuneTotals(int $order_id, float $sub_total, float $total): void
    {
        $this->db->query('UPDATE ' . DB_PREFIX . "order SET `total` = " . $total
            . " WHERE `order_id` = " . $order_id . ";");
        $this->db->query('UPDATE ' . DB_PREFIX . "order_product SET `tax` = 0.00 WHERE `order_id` = " . $order_id . ";");
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE `order_id` = " . $order_id
            . " AND `code` = 'tax';");
        $this->db->query('UPDATE ' . DB_PREFIX . "order_total SET `value` = " . $sub_total
            . " WHERE `order_id` = " . $order_id . " AND `code` = 'sub_total';");
        $this->db->query('UPDATE ' . DB_PREFIX . "order_total SET `value` = " . $total
            . " WHERE `order_id` = " . $order_id . " AND `code` = 'total';");
        $this->db->query('UPDATE ' . DB_PREFIX . "order_total SET `value` = " . ($total - $sub_total)
            . " WHERE `order_id` = " . $order_id . " AND `code` = 'shipping';");
    }

    private function fineTuneProductTotals(array $items, int $order_id): void
    {
        array_walk($items, function ($item) use ($order_id) {
            $product = $this->model_extension_module_onecode_shopflix_product->getCatalogProductBySku($item['sku']);
            $this->db->query('UPDATE ' . DB_PREFIX . "order_product SET `price` = " . $item['price'] . ", `total` = " .
                $item['price'] * $item['quantity']
                . " WHERE `order_id` = " . $order_id . " AND `product_id` = " . $product['product_id'] . ";");
        });
    }

    /**
     * @param $id
     *
     * @return int
     * @throws \LogicException
     */
    protected function createOpenCartOrder($id): int
    {
        $order = $this->getOrderById($id);
        $invoice_data = $this->getOrderInvoiceData($id);
        $items = $this->getOrderItems($id);
        $address = $this->getOrderAddress($id);
        $api_token = $this->api_model->apiLogin();
        $this->api_model->apiCustomer($order, $api_token);
        $this->api_model->apiProduct($items, $api_token);
        $this->api_model->apiAddressPayment($address, $api_token, self::ADDRESS_TYPE_BILLING);
        $this->api_model->apiAddressShipping($address, $api_token, self::ADDRESS_TYPE_SHIPPING);
        $this->api_model->apiPaymentMethod($api_token);
        $this->api_model->apiShippingMethod($api_token);
        $order_id = $this->api_model->apiOrderAdd($order, $invoice_data, $api_token);
        $this->api_model->apiLogout($api_token);
        $this->fineTuneTotals($order_id, (float) $order['sub_total'], (float) $order['total_paid']);
        $this->fineTuneProductTotals($items, $order_id);
        return $order_id;
    }

    protected function acceptOrderDB($order_id, $oc_id)
    {
        //update database
        $this->updateStatusPicking($order_id);
        $this->db->query('DELETE FROM `' . self::getRelationTableName() . '` WHERE `shopflix_id` = ' . $order_id . ' and  `oc_id`=' . $oc_id . ';');
        $this->db->query('INSERT INTO `' . self::getRelationTableName() . '` (`shopflix_id`, `oc_id`)  VALUES (' . $order_id . ', ' . $oc_id . ');');
    }

    public function cancel($id, bool $force = false): array
    {
        $order = $this->getOrderById($id);
        if ($order['status'] == OrderInterface::STATUS_PENDING_ACCEPTANCE || $force)
        {
            $order['status'] = OrderInterface::STATUS_CANCELED;
            $this->updateStatusCancel($id);
            return $order;
        }
        return [];
    }

    public function onTheWay($id, bool $force = false): array
    {
        $order = $this->getOrderById($id);
        if ($order['status'] == OrderInterface::STATUS_READY_TO_BE_SHIPPED || $force)
        {
            $order['status'] = OrderInterface::STATUS_ON_THE_WAY;
            $this->updateStatusOnTheWay($id);
            return $order;
        }
        return [false];
    }

    public function decline($order_ids, string $decline_message): void
    {
        foreach ($order_ids as $id)
        {
            $order = $this->getOrderById($id);
            if ($order['status'] != OrderInterface::STATUS_PENDING_ACCEPTANCE)
            {
                continue;
            }
            if ($this->declineOrderShopflix($order['reference_id'], $decline_message))
            {
                $this->declineOrderDB($id);
            }
        }
    }

    protected function declineOrderDB($order_id)
    {
        //update database
        $this->updateStatusRejected($order_id);
        $this->db->query('DELETE FROM `' . self::getRelationTableName() . '` WHERE `shopflix_id` = ' . $order_id . ';');
    }

    protected function declineOrderShopflix(string $order_id, string $message): bool
    {
        try
        {
            $this->connector->rejected($order_id, $message);
            //print_r([
            //    'message' => $message,
            //    'order_id' => $order_id
            //]);
            return true;
        }
        catch (Exception $e)
        {
            error_log(sprintf('Class: %s, method: %s, error: %s', __CLASS__, __METHOD__, $e->getMessage()));
            return false;
        }
    }

    public function save(array $data): ?array
    {
        $existing_order = $this->getOrderByReferenceId($data['reference_id']);
        if (count($existing_order))
        {
            return $existing_order;
        }
        $this->db->query("START TRANSACTION;");
        try
        {
            $this->db->query("INSERT INTO " . self::getTableName() .
                "(`reference_id`,`status`,`state`,`sub_total`,`discount_amount`,`total_paid`,`customer_email`,`customer_firstname`,`customer_lastname`,`customer_remote_ip`,`customer_note`, `created_at`)" .
                " VALUES " .
                "('" . $this->db->escape($data['reference_id']) . "','" . $data['status'] . "','" . $data['state'] . "',"
                . $data['sub_total'] . "," . $data['discount_amount'] . "," . $data['total_paid'] . ",'"
                . $data['customer_email'] . "','" . $this->db->escape($data['customer_firstname']) . "','"
                . $this->db->escape($data['customer_lastname']) . "','" . $this->db->escape($data['customer_remote_ip']) . "','"
                . $this->db->escape($data['customer_note']) . "',now())"
            );
            $query = $this->db->query('SELECT id FROM ' . self::getTableName() . ' WHERE reference_id = \'' . $data['reference_id'] . '\' LIMIT 1;');
            $order_id = intval(count($query->rows) ? $query->row['id'] : '0');
            $data['id'] = $order_id;
            if ($data['id'] == 0)
            {
                throw new Exception('No order saved');
            }
            if (isset($data['invoice_data']) && count($data['invoice_data']))
            {
                $invoice_data = $data['invoice_data'];
                $invoice_data['order_id'] = $order_id;
                $this->order_invoice_model->save($invoice_data);
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
                //print_r([$item, $query]);
                $this->db->query($query);
            });
            //Store Items
            array_walk($data['items'], function ($item) use ($order_id) {
                $this->db->query("INSERT INTO " . self::getItemTableName() .
                    "(`sku`,`order_id`,`price`,`quantity`)" .
                    " VALUES " .
                    "('" . $this->db->escape($item['sku']) . "'," . $order_id . "," . $item['price'] . "," . $item['quantity'] . ")"
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

    public function updateStatusReadyToBeShipped($order_id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= \'' .
            OrderInterface::STATUS_READY_TO_BE_SHIPPED . '\' WHERE `id` = ' . $order_id . ';');
    }

    public function updateStatusPicking($order_id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= \'' .
            OrderInterface::STATUS_PICKING . '\' WHERE `id` = ' . $order_id . ';');
    }

    public function updateStatusCancel($order_id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= \'' .
            OrderInterface::STATUS_CANCELED . '\' WHERE `id` = ' . $order_id . ';');
    }

    public function updateStatusRejected($order_id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= \'' .
            OrderInterface::STATUS_REJECTED . '\' WHERE `id` = ' . $order_id . ';');
    }

    public function updateStatusOnTheWay($order_id): void
    {
        $this->db->query('UPDATE `' . self::getTableName() . '` SET `status`= \'' .
            OrderInterface::STATUS_ON_THE_WAY . '\' WHERE `id` = ' . $order_id . ';');
    }
}