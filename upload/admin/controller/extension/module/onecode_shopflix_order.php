<?php

use Onecode\Shopflix\Helper;
use Onecode\ShopFlixConnector\Library\Connector;

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Response $response
 * @property-read \Loader $load
 * @property-read \Language $language
 * @property-read \Url $url
 * @property-read \Config $config
 * @property-read \Cart\User $user
 * @property-read Connector $connector
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $model_extension_module_onecode_shopflix_order
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $model_extension_module_onecode_shopflix_shipment
 */
class ControllerExtensionModuleOnecodeShopflixOrder extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('localisation/language');
        $this->load->model('setting/store');
        $this->load->model('extension/module/onecode/shopflix/order');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->model('extension/module/onecode/shopflix/shipment');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Helper\BasicHelper($registry);
        $this->connector = new Connector(
            $this->model_extension_module_onecode_shopflix_config->apiUsername(),
            $this->model_extension_module_onecode_shopflix_config->apiPassword(),
            $this->model_extension_module_onecode_shopflix_config->apiUrl()
        );
        $this->load->language($this->getLink());
    }

    protected function getLink()
    {
        return Helper\BasicHelper::getMainLink() . '_order';
    }

    protected function response404()
    {
        $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
        $data['continue'] = $this->url->link('common/home');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $this->response->setOutput($this->load->view('error/not_found', $data));
    }

    public function index()
    {
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getList();
    }

    public function view()
    {
        $this->document->setTitle($this->language->get('heading_title'));

        $filter_order_id = (isset($this->request->get['order_id'])) ? intval($this->request->get['order_id']) : '';
        $user_token = $this->session->data['user_token'];
        $db_order = $this->model_extension_module_onecode_shopflix_order->getOrderById($filter_order_id);

        if (count($db_order) == 0)
        {
            $this->response404();
            return null;
        }

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(Helper\BasicHelper::getMainLink(), 'user_token=' . $user_token, true),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_order'),
            'href' => $this->url->link($this->getLink(), 'user_token=' . $user_token, true),
        ];
        $url = '&order_id=' . $filter_order_id;
        $data['breadcrumbs'][] = [
            'text' => $db_order['reference_id'],
            'href' => $this->url->link($this->getLink(), 'user_token=' . $user_token . $url, true),
        ];

        $data['user_token'] = $user_token;
        $data['error_warning'] = (isset($this->error['warning'])) ? $this->error['warning'] : '';

        if (isset($this->session->data['success']))
        {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        else
        {
            $data['success'] = '';
        }
        if (isset($this->session->data['errors']))
        {
            $data['error_warning'] = $this->session->data['errors'][0];
            unset($this->session->data['errors']);
        }
        $data['shipment'] = ($db_order['status'] == 'pending_acceptance')
            ? $this->url->link($this->getLink() . '/syncShipments', 'user_token=' . $user_token . $url, true)
            : false;
        $data['accept'] = ($this->model_extension_module_onecode_shopflix_config->convertOrders() && $db_order['status'] == 'pending_acceptance')
            ? $this->url->link($this->getLink() . '/accept', 'user_token=' . $user_token . $url, true)
            : false;

        $data['decline'] = ($db_order['status'] == 'pending_acceptance')
            ? $this->url->link($this->getLink() . '/decline', 'user_token=' . $user_token . $url, true)
            : false;
        $data['cancel'] = $this->url->link($this->getLink(), 'user_token=' . $user_token, true);

        $data['order'] = $db_order;
        $data['order']['status_string'] = $this->language->get('text_status_' . $db_order['status']);
        $data['order']['discount_amount'] = number_format($data['order']['discount_amount'], 2);
        $data['order']['total_paid'] = number_format($data['order']['total_paid'], 2);
        $data['order']['sub_total'] = number_format($data['order']['sub_total'], 2);
        $data['addresses'] = $this->model_extension_module_onecode_shopflix_order->getOrderAddress($filter_order_id);
        $data['addresses'] = array_map(function ($item) {
            $item['name'] = ($item['type'] == ModelExtensionModuleOnecodeShopflixOrder::ADDRESS_TYPE_BILLING)
                ? $this->language->get('text_billing')
                : $this->language->get('text_shipping');
            return $item;
        }, $data['addresses']);
        $data['items'] = $this->model_extension_module_onecode_shopflix_order->getOrderItems($filter_order_id);
        $data['items'] = array_map(function ($item) {
            $item['price'] = number_format($item['price'], 2);
            $catalog_product = $this->model_extension_module_onecode_shopflix_product->getCatalogProductBySku($item['sku']);
            $item['name'] = (isset($catalog_product['description']) && isset
                ($catalog_product['description'][$this->config->get('config_language_id')]))
                ? $catalog_product['description'][$this->config->get('config_language_id')]['name']
                : $this->language->get('text_unknown_product');
            $shipment = $this->model_extension_module_onecode_shopflix_shipment->getTrackingDataByProduct($item['order_id'],$item['sku']);
            $item['shipment'] = [
                'references' => array_column($shipment, 'reference_id'),
                'urls' => array_column($shipment, 'url')
            ];
            return $item;
        }, $data['items']);

        $data['shipments'] = $this->model_extension_module_onecode_shopflix_shipment->getByOrderId($filter_order_id);
        $data['shipments'] = array_map(function ($item) {
            $item['status_string'] = $this->language->get('text_shipment_status_' . $item['status']);
            return $item;
        },$data['shipments']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(Helper\BasicHelper::getPath() . '/order_view', $data));
    }

    public function accept()
    {
        $order_ids = [];
        if (isset($this->request->get['order_id']))
        {
            $order_ids = (array) $this->request->get['order_id'];
        }
        elseif (isset($this->request->post['order_id']))
        {
            $order_ids = (array) $this->request->post['order_id'];
        }
        elseif (isset($this->request->post['selected']))
        {
            $order_ids = (array) $this->request->post['selected'];
        }
        try
        {
            if (count($order_ids))
            {
                $this->model_extension_module_onecode_shopflix_order->accept($order_ids);
                $this->session->data['success'] = count($order_ids) == 1
                    ? $this->language->get('success_order_accepted')
                    : $this->language->get('success_orders_accepted');
            }
        }
        catch (\LogicException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }

        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ], true
            ));
    }

    public function decline()
    {
        $order_ids = null;
        if (isset($this->request->get['order_id']))
        {
            $order_ids = $this->request->get['order_id'];
        }
        elseif (isset($this->request->post['order_id']))
        {
            $order_ids = $this->request->post['order_id'];
        }
        elseif (isset($this->request->post['selected']))
        {
            $order_ids = $this->request->post['selected'];
        }
        if (! is_null($order_ids))
        {
            $order_ids = (! is_array($order_ids)) ? [$order_ids] : $order_ids;
            if (count($order_ids))
            {
                $this->model_extension_module_onecode_shopflix_order->decline($order_ids);
            }
        }
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ], true
            ));
    }

    public function manual_sync()
    {
        try
        {
            $this->syncOrders();
            $this->session->data['success'] = $this->language->get('text_success_sync');
        }
        catch (\LogicException $exception)
        {
            $this->session->data['errors'] = [$this->language->get('error_on_sync')];
        }
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ], true
            ));
    }

    public function syncShipments()
    {
        $order_id = $this->request->get['order_id'];
        $order = $this->model_extension_module_onecode_shopflix_order->getOrderById($order_id);
        if (count($order) == 0)
        {
            return false;
        }
        $shipments = $this->connector->getShipment($order['reference_id']);
        $to_save = [];
        array_walk($shipments, function ($row) use (&$to_save, $order_id) {
            $shipment_data = $row['shipment'];
            $tracks_data = $row['tracks'];
            $items_row = $row['items'];

            //print_r($shipment_data);
            $o_s = [
                'order_id' => $order_id,
                'reference_id' => $shipment_data['increment_id'],
                'status' => $shipment_data['shipment_status'],
                'created_at' => gmdate("Y-m-d\TH:i:s\Z", $shipment_data['created_at']),
            ];
            $o_s['track'] = [
                'number' => $tracks_data['track_number'] != '' ? $tracks_data['track_number'] : "unknown number",
                'url' => $tracks_data['tracking_url'],
            ];
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
            $stored = $this->model_extension_module_onecode_shopflix_shipment->save($shipment);
            if (is_null($stored))
            {
                throw new LogicException('Error during shipment save');
            }
        }
        return true;
    }

    public function syncOrders()
    {
        $orders = $this->connector->getNewOrders();
        $to_save = [];
        array_walk($orders, function ($row) use (&$to_save) {
            $orders_data = $row['order'];
            $addresses_data = $row['addresses'];
            $items_row = $row['items'];

            $o_d = [
                'reference_id' => $orders_data['shopflix_order_id'],
                'state' => $orders_data['state'],
                'status' => $orders_data['status'],
                'sub_total' => $orders_data['subtotal'],
                'total_paid' => $orders_data['total_paid'],
                'discount_amount' => $orders_data['discount_amount'],
                'customer_email' => $orders_data['customer_email'],
                'customer_firstname' => $orders_data['customer_firstname'],
                'customer_lastname' => $orders_data['customer_lastname'],
                'customer_remote_ip' => $orders_data['customer_remote_ip'],
                'customer_note' => $orders_data['customer_note'],
            ];
            array_walk($addresses_data, function ($address_row) use (&$o_d) {
                $o_d['address'][] = [
                    'firstname' => $address_row['firstname'],
                    'lastname' => $address_row['lastname'],
                    'postcode' => $address_row['postcode'],
                    'telephone' => $address_row['telephone'],
                    'street' => $address_row['street'],
                    'type' => $address_row['address_type'],
                    'email' => $address_row['email'],
                    'city' => $address_row['city'],
                    'country_id' => $address_row['country_id'],
                ];
            });
            array_walk($items_row, function ($item_row) use (&$o_d) {
                $o_d['items'][] = [
                    "sku" => $item_row['sku'],
                    "price" => $item_row['price'],
                    "quantity" => $item_row['qty'],
                ];
            });
            $to_save[] = $o_d;
        });

        foreach ($to_save as $order)
        {
            $order_stored = $this->model_extension_module_onecode_shopflix_order->save($order);
            if (is_null($order_stored))
            {
                throw new LogicException('Error during order save');
            }
        }
        return true;
    }

    protected function getList()
    {
        $filter_reference_id = (isset($this->request->get['filter_reference_id'])) ? $this->request->get['filter_reference_id'] : '';
        $filter_sub_total = (isset($this->request->get['filter_sub_total'])) ? $this->request->get['filter_sub_total'] : '';
        $filter_total_paid = (isset($this->request->get['filter_total_paid'])) ? $this->request->get['filter_total_paid'] : '';
        $filter_customer_email = (isset($this->request->get['filter_customer_email'])) ? $this->request->get['filter_customer_email'] : '';
        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'o.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = (isset($this->request->get['page'])) ? (int) $this->request->get['page'] : 1;
        $user_token = $this->session->data['user_token'];
        $url = '';
        if ($filter_reference_id != '')
        {
            $url .= '&filter_reference_id=' . urlencode(html_entity_decode($filter_reference_id, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_sub_total != '')
        {
            $url .= '&filter_sub_total=' . floatval($filter_sub_total);
        }
        if ($filter_total_paid != '')
        {
            $url .= '&filter_total_paid=' . floatval($filter_total_paid);
        }
        if ($filter_customer_email != '')
        {
            $url .= '&filter_customer_email=' . urlencode(html_entity_decode($filter_customer_email, ENT_QUOTES, 'UTF-8'));
        }

        $url .= '&order=' . $order;
        $url .= '&page=' . $page;

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(Helper\BasicHelper::getMainLink(), 'user_token=' . $user_token, true),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_orders'),
            'href' => $this->url->link($this->getLink(), 'user_token=' . $user_token . $url, true),
        ];
        $data['manual_sync'] = $this->url->link($this->getLink() . '/manual_sync', 'user_token=' . $user_token . $url, true);
        $data['accept'] = ($this->model_extension_module_onecode_shopflix_config->convertOrders())
            ? $this->url->link($this->getLink() . '/accept', 'user_token=' . $user_token . $url, true)
            : false;

        $data['orders'] = [];

        $filter_data = [
            'filter_reference_id' => $filter_reference_id,
            'filter_sub_total' => $filter_sub_total,
            'filter_total_paid' => $filter_total_paid,
            'filter_customer_email' => $filter_customer_email,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin'),
        ];

        $order_total = $this->model_extension_module_onecode_shopflix_order->getTotalOrders($filter_data);
        $results = $this->model_extension_module_onecode_shopflix_order->getAllOrders($filter_data);

        foreach ($results as $result)
        {
            $data['orders'][] = [
                'order_id' => $result['id'],
                'reference_id' => $result['reference_id'],
                'status' => $result['status'],
                'status_string' => $this->language->get('text_status_' . $result['status']),
                'sub_total' => floatval($result['sub_total']),
                'discount_amount' => floatval($result['discount_amount']),
                'total_paid' => floatval($result['total_paid']),
                'customer_email' => $result['customer_email'],
                'customer_firstname' => $result['customer_firstname'],
                'customer_lastname' => $result['customer_lastname'],
                'customer_remote_ip' => $result['customer_remote_ip'],
                'view' => $this->url->link($this->getLink() . '/view', 'user_token=' .
                    $user_token . '&order_id=' . $result['id'] . $url, true),
                'accept' => ($result['status'] == 'pending_acceptance')
                    ? $this->url->link($this->getLink() . '/accept', 'user_token=' .
                        $user_token . '&order_id=' . $result['id'] . $url, true)
                    : false,
                'decline' => ($result['status'] == 'pending_acceptance')
                    ? $this->url->link($this->getLink() . '/decline', 'user_token=' .
                        $user_token . '&order_id=' . $result['id'] . $url, true)
                    : false,
            ];
        }

        $data['user_token'] = $user_token;
        $data['error_warning'] = (isset($this->error['warning'])) ? $this->error['warning'] : '';

        if (isset($this->session->data['success']))
        {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        else
        {
            $data['success'] = '';
        }
        if (isset($this->session->data['errors']))
        {
            $data['error_warning'] = $this->session->data['errors'][0];
            unset($this->session->data['errors']);
        }
        $data['selected'] = isset($this->request->post['selected']) ? (array) $this->request->post['selected'] : [];

        if (isset($this->request->get['page']))
        {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_id'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.id' . $url, true);
        $data['sort_reference_id'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.reference_id' .
            $url, true);
        $data['sort_sub_total'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.sub_total' .
            $url, true);
        $data['sort_discount_amount'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.discount_amount' .
            $url, true);
        $data['sort_total_paid'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.total_paid' .
            $url, true);
        $data['sort_status'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.status' .
            $url, true);
        $data['sort_customer_email'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.customer_email' . $url, true);

        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link($this->getLink(), 'user_token=' . $user_token . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) *
                $filter_data['limit']) + 1 : 0, ((($page - 1) * $filter_data['limit']) > ($order_total -
                $filter_data['limit'])) ? $order_total : ((($page - 1) * $filter_data['limit']) +
            $filter_data['limit']), $order_total, ceil($order_total / $filter_data['limit']));

        $data['filter_reference_id'] = $filter_reference_id;
        $data['filter_sub_total'] = $filter_sub_total;
        $data['filter_total_paid'] = $filter_total_paid;
        $data['filter_customer_email'] = $filter_customer_email;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(Helper\BasicHelper::getPath() . '/order_list', $data));
    }

    public function autocomplete()
    {
        $json = [];

        if (isset($this->request->get['filter_reference_id']) || isset($this->request->get['filter_customer_email']))
        {
            $filter_reference_id = (isset($this->request->get['filter_reference_id']))
                ? $this->request->get['filter_reference_id']
                : '';
            $filter_customer_email = (isset($this->request->get['filter_customer_email']))
                ? $this->request->get['filter_customer_email']
                : "";

            $limit = (isset($this->request->get['limit'])) ? (int) $this->request->get['limit'] : 5;

            $filter_data = [
                'filter_reference_id' => $filter_reference_id,
                'filter_customer_email' => $filter_customer_email,
                'start' => 0,
                'limit' => $limit,
            ];

            $results = $this->model_extension_module_onecode_shopflix_order->getAllOrders($filter_data);

            foreach ($results as $result)
            {
                $json[] = [
                    'order_id' => $result['id'],
                    'reference_id' => strip_tags(html_entity_decode($result['reference_id'], ENT_QUOTES, 'UTF-8')),
                    'customer_email' => strip_tags(html_entity_decode($result['customer_email'], ENT_QUOTES, 'UTF-8')),
                ];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}