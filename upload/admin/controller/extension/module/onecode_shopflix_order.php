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
 * @property-read \Cart\User $user
 * @property-read Connector $connector
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $model_extension_module_onecode_shopflix_order
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
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
        $this->load->model('extension/module/onecode/shopflix/config');
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

    public function index()
    {
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getList();
    }

    public function view()
    {
        $this->document->setTitle($this->language->get('heading_title'));

        $filter_order_id = (isset($this->request->get['order_id'])) ? $this->request->get['order_id'] : '';
        $user_token = $this->session->data['user_token'];
        $url = '';
        $db_order = $this->model_extension_module_onecode_shopflix_order->getOrderById($filter_order_id);
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(Helper\BasicHelper::getMainLink(), 'user_token=' . $user_token, true),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_order'),
            'href' => $this->url->link($this->getLink(), 'user_token=' . $user_token . $url, true),
        ];
        $url .= '&order_id=' . $filter_order_id;
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
        $data['selected'] = isset($this->request->post['selected']) ? (array) $this->request->post['selected'] : [];
        $data['accept'] = $this->url->link($this->getLink() . '/accept', 'user_token=' . $user_token . $url, true);
        $data['decline'] = $this->url->link($this->getLink() . '/decline', 'user_token=' . $user_token . $url, true);

        $data['order_data'] = $db_order;

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

        if (count($order_ids))
        {
            $this->model_extension_module_onecode_shopflix_order->accept($order_ids);
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

        if (count($order_ids))
        {
            $this->model_extension_module_onecode_shopflix_order->decline($order_ids);
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
            $this->sync();
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

    public function sync()
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
            'text' => $this->language->get('heading_order'),
            'href' => $this->url->link($this->getLink(), 'user_token=' . $user_token . $url, true),
        ];
        $data['manual_sync'] = $this->url->link($this->getLink() . '/manual_sync', 'user_token=' . $user_token . $url, true);
        $data['accept'] = ($this->model_extension_module_onecode_shopflix_config->convertOrders())
            ? $this->url->link($this->getLink() . '/accept', 'user_token=' . $user_token . $url, true)
            : false;

        $data['decline'] = $this->url->link($this->getLink() . '/decline', 'user_token=' . $user_token . $url, true);

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
                'sub_total' => floatval($result['sub_total']),
                'discount_amount' => floatval($result['discount_amount']),
                'total_paid' => floatval($result['total_paid']),
                'customer_email' => $result['customer_email'],
                'customer_firstname' => $result['customer_firstname'],
                'customer_lastname' => $result['customer_lastname'],
                'customer_remote_ip' => $result['customer_remote_ip'],
                'view' => $this->url->link($this->getLink() . '/view', 'user_token=' .
                    $user_token . '&product_id=' . $result['id'] . $url, true),
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
            $data['warning'] = $this->session->data['errors'][0];
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
}