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
 * @property-read \Config $config
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $model_extension_module_onecode_shopflix_order
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $model_extension_module_onecode_shopflix_shipment
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $shipment_model
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $order_model
 */
class ControllerExtensionModuleOnecodeShopflixShipment extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('localisation/language');
        $this->load->model('setting/store');
        $this->load->model('extension/module/onecode/shopflix/order');
        $this->load->model('extension/module/onecode/shopflix/shipment');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Helper\BasicHelper($registry);
        $this->shipment_model = new ModelExtensionModuleOnecodeShopflixShipment($registry);
        $this->order_model = new ModelExtensionModuleOnecodeShopflixOrder($registry);

        $this->load->language($this->getLink());
    }

    protected function getLink()
    {
        return Helper\BasicHelper::getMainLink() . '_shipment';
    }

    public function index()
    {
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getList();
    }

    protected function getList()
    {
        $filter_reference_id = (isset($this->request->get['filter_reference_id'])) ? $this->request->get['filter_reference_id'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_order = (isset($this->request->get['filter_order'])) ? $this->request->get['filter_order'] : '';
        $filter_order_oc = (isset($this->request->get['filter_order_oc'])) ? $this->request->get['filter_order_oc'] : '';

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'o.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = (isset($this->request->get['page'])) ? (int) $this->request->get['page'] : 1;
        $user_token = $this->session->data['user_token'];
        $url = '';

        if ($filter_reference_id != '')
        {
            $url .= '&filter_reference_id=' . urlencode(html_entity_decode($filter_reference_id, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_status != '')
        {
            $url .= '&filter_status=' . intval($filter_status);
        }
        if ($filter_order != '')
        {
            $url .= '&filter_order=' . floatval($filter_order);
        }
        if ($filter_order_oc != '')
        {
            $url .= '&filter_order_oc=' . intval($filter_order_oc);
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

        $data['print'] = $this->url->link($this->getLink() . '/print_voucher', 'user_token=' . $user_token . $url, true);
        $data['manifest'] = $this->url->link($this->getLink() . '/print_manifest', 'user_token=' . $user_token . $url, true);
        $data['shipments'] = [];

        $filter_data = [
            'filter_reference_id' => $filter_reference_id,
            'filter_status' => $filter_status,
            'filter_order' => $filter_order,
            'filter_order_oc' => $filter_order_oc,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin'),
        ];

        $order_total = $this->shipment_model->getTotalOrders($filter_data);
        $results = $this->shipment_model->getAllOrders($filter_data);

        foreach ($results as $result)
        {
            $order_link_data = $this->order_model->getLinkedOrder($result['order_id']);
            $order_data = $this->order_model->getOrderById($result['order_id']);
            $tracking = $this->shipment_model->getTrackByShipment($result['id']);
            $data['shipments'][] = [
                'id' => $result['id'],
                'reference_id' => $result['reference_id'],
                'order_id' => $result['order_id'],
                'order_reference' => $order_data['reference_id'],
                'order_id_oc' => $order_link_data['oc_id'],
                'status' => $result['status'],
                'status_string' => $this->language->get('text_shipment_status_' . $result['status']),
                'print' => count($tracking) ? $this->url->link($this->getLink() . '/print_voucher', 'user_token=' .
                    $user_token . '&shipment_id=' . $result['id'] . $url, true) : false,
                'manifest' => count($tracking) ? $this->url->link($this->getLink() . '/print_manifest', 'user_token=' .
                    $user_token . '&shipment_id=' . $result['id'] . $url, true) : false,
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
        $data['sort_status'] = $this->url->link($this->getLink(), 'user_token=' . $user_token . '&sort=o.status' .
            $url, true);

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

        $data['filter_status'] = $filter_status;
        $data['filter_order_oc'] = $filter_order_oc;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(Helper\BasicHelper::getPath() . '/shipment_list', $data));
    }

    public function create_voucher()
    {
        $ids = [];
        if (isset($this->request->get['shipment_id']))
        {
            $ids = (array) $this->request->get['shipment_id'];
        }
        elseif (isset($this->request->post['shipment_id']))
        {
            $ids = (array) $this->request->post['shipment_id'];
        }
        elseif (isset($this->request->post['selected']))
        {
            $ids = (array) $this->request->post['selected'];
        }
        try
        {
            if (count($ids))
            {
                $this->shipment_model->createVoucher($ids);
                $this->session->data['success'] = count($ids) == 1
                    ? $this->language->get('success_order_accepted')
                    : $this->language->get('success_orders_accepted');
            }
        }
        catch (\LogicException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\RuntimeException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\Exception $exception)
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

    public function print_voucher()
    {
        $ids = [];
        if (isset($this->request->get['shipment_id']))
        {
            $ids = (array) $this->request->get['shipment_id'];
        }
        elseif (isset($this->request->post['shipment_id']))
        {
            $ids = (array) $this->request->post['shipment_id'];
        }
        elseif (isset($this->request->post['selected']))
        {
            $ids = (array) $this->request->post['selected'];
        }
        try
        {
            if (count($ids) == 0)
            {
                throw new \LogicException($this->language->get('error_no_available_shipments'));
            }
            $this->download_voucher_pdf($ids);
            return;
        }
        catch (\LogicException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\RuntimeException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\Exception $exception)
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

    public function print_manifest()
    {
        $ids = [];
        if (isset($this->request->get['shipment_id']))
        {
            $ids = (array) $this->request->get['shipment_id'];
        }
        elseif (isset($this->request->post['shipment_id']))
        {
            $ids = (array) $this->request->post['shipment_id'];
        }
        elseif (isset($this->request->post['selected']))
        {
            $ids = (array) $this->request->post['selected'];
        }
        try
        {
            if (count($ids) == 0)
            {
                throw new \LogicException('No shipment available for manifest');
            }
            $contents = $this->shipment_model->printManifest($ids);
            //print_r(['manifest' => $contents]);
            if ($contents == null)
            {
                throw new \LogicException('No voucher Content');
            }
            $this->response->addHeader('Content-Type: application/pdf');
            $this->response->addHeader('Expires: 0');
            $this->response->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            $this->response->addHeader('Pragma: public');
            $this->response->addHeader('Content-Length: ' . strlen($contents));
            $this->response->addHeader('Content-Disposition: attachment; filename="voucher.pdf"');
            $this->response->setOutput($contents);
            return;
        }
        catch (\LogicException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\RuntimeException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\Exception $exception)
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

    public function print_voucher_order()
    {
        $ids = [];
        $order_ids = [];
        if (isset($this->request->get['order_id']))
        {
            $order_ids = (array) $this->request->get['order_id'];
        }
        if (count($order_ids))
        {
            foreach ($order_ids as $id)
            {
                $shipments = $this->shipment_model->getByOrderId($id);
                $ids = array_merge($ids, array_column($shipments, 'id'));
                $this->download_voucher_pdf($ids);
                return;
            }
        }
        try
        {
            if (count($ids) == 0)
            {
                throw new \LogicException($this->language->get('error_no_available_shipments'));
            }
        }
        catch (\LogicException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\RuntimeException $exception)
        {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        catch (\Exception $exception)
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

    protected function download_voucher_pdf($ids):void
    {
        $contents = $this->shipment_model->printVoucherByShipments($ids);
        if ($contents == null)
        {
            throw new \LogicException($this->language->get('error_no_manifest_contents'));
        }
        $this->response->addHeader('Content-Type: application/pdf');
        $this->response->addHeader('Expires: 0');
        $this->response->addHeader('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        $this->response->addHeader('Pragma: public');
        $this->response->addHeader('Content-Length: ' . strlen($contents));
        $this->response->addHeader('Content-Disposition: attachment; filename="voucher.pdf"');
        $this->response->setOutput($contents);
    }
}