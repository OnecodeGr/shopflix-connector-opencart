<?php

use Onecode\Shopflix\Helper;

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Response $response
 * @property-read \Loader $load
 * @property-read \Language $language
 * @property-read \Url $url
 * @property-read \Cart\User $user
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $model_extension_module_onecode_shopflix_order
 */
class ControllerExtensionModuleOnecodeShopflixOrder extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('localisation/language');
        $this->load->model('setting/store');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Helper\BasicHelper($registry);
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

    public function sync()
    {
        //execute sync
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
        $data['accept'] = $this->url->link($this->getLink() . '/accept', 'user_token=' . $user_token . $url, true);
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