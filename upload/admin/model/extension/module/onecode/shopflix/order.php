<?php

use Onecode\Shopflix\Helper;

require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Order.php';

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 * @property-read \Config $config
 * @property-read \Language $language
 * @property-read \Session $session
 * @property-read \ModelSaleOrder $model_sale_order
 * @property-read \ModelCatalogProduct $model_catalog_product
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingStore $model_setting_store
 * @property-read \ModelLocalisationCurrency $model_localisation_currency
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
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
        $this->load->model('setting/extension');
        $this->load->model('setting/extension');
        $this->load->model('localisation/currency');
        $this->load->model('catalog/product');
        $this->load->model('extension/module/onecode/shopflix/config');
    }

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

    protected function createOrderRelationTable()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . self::getRelationTableName() . " (
 `shopflix_id` INT UNSIGNED NOT NULL,
 `oc_id` INT UNSIGNED NOT NULL,
 `created_at` UNIX_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`shopflix_id`,`oc_id`),
    FOREIGN KEY (shopflix_id) REFERENCES " . self::getTableName() . "(id) ON UPDATE CASCADE ON DELETE RESTRICT ,
    FOREIGN KEY (oc_id) REFERENCES " . \DB_PREFIX . "order(id) ON UPDATE CASCADE ON DELETE RESTRICT
)");
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
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getRelationTableName()));
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
        $sql = sprintf("SELECT COUNT(DISTINCT o.id) AS total FROM %s AS o", self::getTableName());
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

    public function getOrderItems($order_id): array
    {
        $sql = "SELECT * FROM " . self::getItemTableName() . " WHERE order_id = " . intval($order_id);
        return $this->db->query($sql)->rows;
    }

    public function getOrderAddress($order_id): array
    {
        $sql = "SELECT * FROM " . self::getAddressTableName() . " WHERE order_id = " . intval($order_id);
        return $this->db->query($sql)->rows;
    }

    public function accept($order_id): void
    {
        $oc_id = $this->createOpenCartOrder($order_id);
        $this->acceptOrderDB($order_id, $oc_id);
        $this->acceptOrderShopflix($order_id);
    }

    protected function createOpenCartOrder($shopflix_id): int
    {
        $order = $this->getOrderById($shopflix_id);
        $items = $this->getOrderItems($shopflix_id);
        $address = $this->getOrderAddress($shopflix_id);
        $order_data = $this->setupOrderFields();
        //update order fields
        $order_data['total'] = $order['total_paid'];
        $order_data['comment'] = $order['customer_note'];
        $order_data['order_status_id'] = 1; //Pending
        $order_data['shipping_method'] = '';
        $order_data['shipping_code'] = '';
        //update customer fields
        $order_data['firstname'] = $order['customer_firstname'];
        $order_data['lastname'] = $order['customer_lastname'];
        $order_data['email'] = $order['customer_email'];
        $order_data['telephone'] = '';
        //address
        foreach ($address as $row)
        {
            $prefix = ($row['type'] == self::ADDRESS_TYPE_BILLING) ? 'payment_' : 'shipping_';
            $order_data[$prefix . 'firstname'] = $row['firstname'];
            $order_data[$prefix . 'lastname'] = $row['lastname'];
            $order_data[$prefix . 'company'] = '';
            $order_data[$prefix . 'address_1'] = $row['street'];
            $order_data[$prefix . 'address_2'] = '';
            $order_data[$prefix . 'city'] = $row['city'];
            $order_data[$prefix . 'postcode'] = $row['postcode'];
            $order_data[$prefix . 'zone'] = '';
            $order_data[$prefix . 'zone_id'] = '';
            $order_data[$prefix . 'country'] = '';
            $order_data[$prefix . 'country_id'] = $row['country_id'];
            $order_data[$prefix . 'address_format'] = '';
            $order_data[$prefix . 'custom_field'] = [
                'phone' => $row['telephone'],
                'email' => $row['email'],
            ];
        }
        //products
        foreach ($items as $item)
        {
            $product = $this->getCatalogProductBySku($item['sku']);

            $product_data = [
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['price'] * $item['quantity'],
                'tax' => 0,
                'option' => [],
                'download' => 0,
                'subtract' => '',
                'reward' => '',
            ];
            $order_data['products'][] = $product_data;
        }
        //POST DATA -> var url = '{{ catalog }}index.php?route=api/order/add&api_token={{ api_token }}&store_id=' + $
        //('select[name=\'store_id\'] option:selected').val();
        return  1;
    }

    protected function setupOrderFields(): array
    {
        $order_data = [];
        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        $total_data = [
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total,
        ];

        $sort_order = [];
        $results = $this->model_setting_extension->getExtensions('total');
        foreach ($results as $key => $value)
        {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }
        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result)
        {
            if ($this->config->get('total_' . $result['code'] . '_status'))
            {
                $this->load->model('extension/total/' . $result['code']);
                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }
        $sort_order = [];
        foreach ($totals as $key => $value)
        {
            $sort_order[$key] = $value['sort_order'];
        }
        array_multisort($sort_order, SORT_ASC, $totals);
        $order_data['totals'] = $totals;

        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');

        if ($order_data['store_id'])
        {
            $order_data['store_url'] = $this->config->get('config_url');
        }
        else
        {
            $order_data['store_url'] = ($this->request->server['HTTPS']) ? HTTPS_SERVER : HTTP_SERVER;
        }

        $order_data['customer_id'] = 0;
        $order_data['customer_group_id'] = $this->model_extension_module_onecode_shopflix_config->customerGroup();
        $order_data['firstname'] = '';
        $order_data['lastname'] = '';
        $order_data['email'] = '';
        $order_data['telephone'] = '';
        $order_data['custom_field'] = '';

        $order_data['payment_firstname'] = '';
        $order_data['payment_lastname'] = '';
        $order_data['payment_company'] = '';
        $order_data['payment_address_1'] = '';
        $order_data['payment_address_2'] = '';
        $order_data['payment_city'] = '';
        $order_data['payment_postcode'] = '';
        $order_data['payment_zone'] = '';
        $order_data['payment_zone_id'] = '';
        $order_data['payment_country'] = '';
        $order_data['payment_country_id'] = '';
        $order_data['payment_address_format'] = '';
        $order_data['payment_custom_field'] = [];
        $order_data['payment_method'] = '';
        $order_data['payment_code'] = $this->model_extension_module_onecode_shopflix_config->paymentMethod();
        $order_data['shipping_firstname'] = '';
        $order_data['shipping_lastname'] = '';
        $order_data['shipping_company'] = '';
        $order_data['shipping_address_1'] = '';
        $order_data['shipping_address_2'] = '';
        $order_data['shipping_city'] = '';
        $order_data['shipping_postcode'] = '';
        $order_data['shipping_zone'] = '';
        $order_data['shipping_zone_id'] = '';
        $order_data['shipping_country'] = '';
        $order_data['shipping_country_id'] = '';
        $order_data['shipping_address_format'] = '';
        $order_data['shipping_custom_field'] = [];
        $order_data['shipping_method'] = '';
        $order_data['shipping_code'] = $this->model_extension_module_onecode_shopflix_config->shippingMethod();
        $order_data['products'] = [];
        $order_data['comment'] = '';
        $order_data['total'] = $total_data['total'];

        $order_data['affiliate_id'] = 0;
        $order_data['commission'] = 0;
        $order_data['marketing_id'] = 0;
        $order_data['tracking'] = '';

        $order_data['language_id'] = $this->config->get('config_language_id');
        $order_data['currency_code'] = $this->session->data['currency'];

        $currencies = $this->model_localisation_currency->getCurrencies();
        $currency = (isset($currencies[$order_data['currency_code']])) ?
            $currencies[$order_data['currency_code']] : ['currency_id' => 0, "value" => ''];

        $order_data['currency_id'] = $currency['currency_id'];
        $order_data['currency_value'] = $currency['value'];
        $order_data['ip'] = '';
        $order_data['forwarded_ip'] = '';
        $order_data['user_agent'] = '';
        $order_data['accept_language'] = '';
        return $order_data;
    }

    protected function acceptOrderDB($order_id, $oc_id)
    {
        //update database
        $this->db->query('UPDATE TABLE `' . self::getTableName() . '` SET `status`= \'completed\' WHERE `id` = ' .
            $order_id . ';');
        $this->db->query('DELETE FROM `' . self::getRelationTableName() . '` WHERE `id` = ' . $order_id . ' and  `oc_id`=' . $oc_id . ';');
        $this->db->query('INSERT INTO `' . self::getRelationTableName() . '` VALUES (' . $order_id . ', ' . $oc_id . ');');
    }

    protected function acceptOrderShopflix($order_id)
    {
    }

    public function decline($order_id): void
    {
        $this->declineOrderDB($order_id);
        $this->declineOrderShopflix($order_id);
    }

    protected function declineOrderDB($order_id)
    {
        //update database
        $this->db->query('UPDATE TABLE `' . self::getTableName() . '` SET `status`= \'cancelled\' WHERE `id` = ' .
            $order_id . ';');
        $this->db->query('DELETE FROM `' . self::getRelationTableName() . '` WHERE `id` = ' . $order_id . ';');
    }

    protected function declineOrderShopflix($order_id)
    {
    }

    protected function getCatalogProductBySku($sku): array
    {
        $sku = trim(preg_replace('/\s+/', ' ', $sku));
        $query = $this->db->query('SELECT * FROM ' . \DB_PREFIX . 'product WHERE sku = \'' . $this->db->escape($sku) . '\'');
        if (! empty($query->row))
        {
            return $this->model_catalog_product->getProduct($query->row['product_id']);
        }
        return [];
    }
}