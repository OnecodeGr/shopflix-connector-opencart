<?php

use Onecode\Shopflix\Helper;
use Onecode\ShopFlixConnector\Library\Connector;
use Onecode\ShopFlixConnector\Library\Interfaces\AddressInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ItemInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ReturnItemInterface;
use Onecode\ShopFlixConnector\Library\Interfaces\ReturnOrderInterface;

require_once DIR_SYSTEM . 'library/onecode/vendor/autoload.php';

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
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \ModelExtensionModuleOnecodeShopflixReturnOrder $model_extension_module_onecode_shopflix_return_order
 * @property-read \ModelExtensionModuleOnecodeShopflixReturnOrder $return_order_model
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $model_extension_module_onecode_shopflix_order
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $order_model
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 */
class ControllerExtensionModuleOnecodeShopflixReturnOrder extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('localisation/language');
        $this->load->model('setting/store');
        $this->load->model('extension/module/onecode/shopflix/order');
        $this->load->model('extension/module/onecode/shopflix/return_order');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->return_order_model = new ModelExtensionModuleOnecodeShopflixReturnOrder($registry);
        $this->order_model = new ModelExtensionModuleOnecodeShopflixOrder($registry);
        $this->basicHelper = new Helper\BasicHelper($registry);
        $this->load->language('extension/module/onecode_shopflix_return_order');
    }

    protected function getLink(): string
    {
        return 'extension/module/onecode/shopflix/return_order';
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
        $db_order = $this->return_order_model->getOrderById($filter_order_id);

        if (count($db_order) == 0) {
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

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['errors'])) {
            $data['error_warning'] = $this->session->data['errors'][0];
            unset($this->session->data['errors']);
        }

        $data['order'] = $db_order;
        $data['order']['status_string'] = $this->language->get('text_status_' . $db_order['status']);
        $data['order']['total_paid'] = number_format($data['order']['total_paid'], 2);
        $data['order']['sub_total'] = number_format($data['order']['sub_total'], 2);
        $data['addresses'] = $this->return_order_model->getOrderAddress($filter_order_id);
        $data['addresses'] = array_map(function ($item) {
            $item['name'] = ($item['type'] == ModelExtensionModuleOnecodeShopflixReturnOrder::ADDRESS_TYPE_BILLING)
                ? $this->language->get('text_billing')
                : $this->language->get('text_shipping');
            return $item;
        }, $data['addresses']);
        $data['items'] = $this->return_order_model->getOrderItems($filter_order_id);
        $data['items'] = array_map(function ($item) {
            $item['price'] = number_format($item['price'], 2);
            $catalog_product = $this->model_extension_module_onecode_shopflix_product->getCatalogProductBySku(
                $item['sku']
            );
            $item['name'] = (isset($catalog_product['description']) && isset
                (
                    $catalog_product['description'][$this->config->get('config_language_id')]
                ))
                ? $catalog_product['description'][$this->config->get('config_language_id')]['name']
                : $this->language->get('text_unknown_product');
            return $item;
        }, $data['items']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(Helper\BasicHelper::getPath() . '/return_order_view', $data));
    }

    public function accept()
    {
        $order_ids = [];
        if (isset($this->request->get['order_id'])) {
            $order_ids = (array)$this->request->get['order_id'];
        } elseif (isset($this->request->post['order_id'])) {
            $order_ids = (array)$this->request->post['order_id'];
        } elseif (isset($this->request->post['selected'])) {
            $order_ids = (array)$this->request->post['selected'];
        }
        try {
            if (count($order_ids)) {
                $this->return_order_model->accept($order_ids);
                $this->session->data['success'] = count($order_ids) == 1
                    ? $this->language->get('success_order_accepted')
                    : $this->language->get('success_orders_accepted');
            }
        } catch (LogicException $exception) {
            $this->session->data['errors'] = [$exception->getMessage()];
        } catch (RuntimeException $exception) {
            $this->session->data['errors'] = [$exception->getMessage()];
        }

        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ],
                true
            )
        );
    }

    public function decline()
    {
        try {
            $order_ids = null;
            $reason = trim($this->request->get['reason'] ?? $this->request->post['reason']);
            if ($reason == '') {
                throw new LogicException($this->language->get('error_on_reject_reason_missing'));
            }
            if (isset($this->request->get['order_id'])) {
                $order_ids = $this->request->get['order_id'];
            } elseif (isset($this->request->post['order_id'])) {
                $order_ids = $this->request->post['order_id'];
            } elseif (isset($this->request->post['selected'])) {
                $order_ids = $this->request->post['selected'];
            }
            if (! is_null($order_ids)) {
                $order_ids = (! is_array($order_ids)) ? [$order_ids] : $order_ids;
                if (count($order_ids)) {
                    $this->return_order_model->decline($order_ids, $reason);
                }
            }
        } catch (LogicException $e) {
            $this->session->data['errors'] = [$e->getMessage()];
        }

        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ],
                true
            )
        );
    }

    public function manual_sync()
    {
        try {
            $this->getNewOrders();
            $this->getOnTheWay();
            $this->getDeliveredToStore();
            $this->getApproved();
            $this->getDecline();
            $this->session->data['success'] = $this->language->get('text_success_sync');
        } catch (LogicException $exception) {
            $this->session->data['errors'] = [$exception->getMessage()];
        } catch (Exception $exception) {
            $this->session->data['errors'] = [$exception->getMessage()];
        }
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ],
                true
            )
        );
    }

    private function buildOrderItems(array $orders): array
    {
        $returnArray = [];
        array_walk($orders, function ($row) use (&$returnArray) {
            $orders_data = $row['return_order'] ?? [];
            $addresses_data = $row['addresses'] ?? [];
            $items_row = $row['items'] ?? [];

            $o_d = [
                'reference_id' => $orders_data[ReturnOrderInterface::SHOPFLIX_ORDER_ID] ?? '',
                'vendor_parent_id' => $orders_data[ReturnOrderInterface::SHOPFLIX_PARENT_ORDER_ID] ?? '',
                'state' => $orders_data[ReturnOrderInterface::STATE] ?? '',
                'status' => $orders_data[ReturnOrderInterface::STATUS] ?? '',
                'sub_total' => $orders_data[ReturnOrderInterface::SUBTOTAL] ?? '',
                'total_paid' => $orders_data[ReturnOrderInterface::TOTAL_PAID] ?? '',
                'customer_email' => $orders_data[ReturnOrderInterface::CUSTOMER_EMAIL] ?? '',
                'customer_firstname' => $orders_data[ReturnOrderInterface::CUSTOMER_FIRSTNAME] ?? '',
                'customer_lastname' => $orders_data[ReturnOrderInterface::CUSTOMER_LASTNAME] ?? '',
                'customer_remote_ip' => $orders_data[ReturnOrderInterface::CUSTOMER_REMOTE_IP] ?? '',
                'customer_note' => $orders_data[ReturnOrderInterface::CUSTOMER_NOTE] ?? '',
            ];
            array_walk($addresses_data, function ($address_row) use (&$o_d) {
                $o_d['address'][] = [
                    'firstname' => $address_row[AddressInterface::FIRSTNAME] ?? '',
                    'lastname' => $address_row[AddressInterface::LASTNAME] ?? '',
                    'postcode' => $address_row[AddressInterface::POSTCODE] ?? '',
                    'telephone' => $address_row[AddressInterface::TELEPHONE] ?? '',
                    'street' => $address_row[AddressInterface::STREET] ?? '',
                    'type' => $address_row[AddressInterface::ADDRESS_TYPE] ?? '',
                    'email' => $address_row[AddressInterface::EMAIL] ?? '',
                    'city' => $address_row[AddressInterface::CITY] ?? '',
                    'country_id' => $address_row[AddressInterface::COUNTRY_ID] ?? '',
                ];
            });
            array_walk($items_row, function ($item_row) use (&$o_d) {
                $catalog_item = $this->model_extension_module_onecode_shopflix_product->getCatalogProductBySku(
                    $item_row[ItemInterface::SKU]
                );
                if (count($catalog_item) == 0) {
                    throw new LogicException(
                        sprintf(
                            $this->language->get('error_order_reference_contains_invalid_sku'),
                            $o_d['reference_id'],
                            $item_row[ItemInterface::SKU]
                        )
                    );
                }
                $o_d['items'][] = [
                    "sku" => $item_row[ReturnItemInterface::SKU] ?? '',
                    "price" => $item_row[ReturnItemInterface::PRICE] ?? '',
                    "quantity" => $item_row[ReturnItemInterface::QTY] ?? '',
                    "reason" => $item_row[ReturnItemInterface::RETURN_REASON] ?? '',
                ];
            });
            $returnArray[] = $o_d;
        });
        return $returnArray;
    }

    public function getNewOrders(): bool
    {
        $orders = $this->return_order_model->connector->getNewReturnedOrders();
        if (count($orders) == 0) {
            return false;
        }
        $to_save = $this->buildOrderItems($orders);
        if (count($to_save)) {
            foreach ($to_save as $order) {
                $order_stored = $this->return_order_model->addOrder($order);
                if (is_null($order_stored)) {
                    throw new LogicException(
                        sprintf(
                            $this->language->get('error_unable_to_save_order_with_reference'),
                            $order['reference_id']
                        )
                    );
                }
            }
        }
        return true;
    }

    public function getOnTheWay(): bool
    {
        $orders = $this->return_order_model->connector->getOnTheWayToStoreReturnedOrders();
        if (count($orders) == 0) {
            return false;
        }
        $to_save = $this->buildOrderItems($orders);
        if (count($to_save)) {
            foreach ($to_save as $order) {
                $order_stored = $this->return_order_model->addOrder(
                    $order,
                    ReturnOrderInterface::STATUS_ON_THE_WAY_TO_THE_STORE
                );
                if (is_null($order_stored)) {
                    throw new LogicException(
                        sprintf(
                            $this->language->get('error_unable_to_save_order_with_reference'),
                            $order['reference_id']
                        )
                    );
                }
            }
        }
        return true;
    }

    public function getDeliveredToStore(): bool
    {
        $orders = $this->return_order_model->connector->getDeliveredToStoreReturnedOrders();
        if (count($orders) == 0) {
            return false;
        }
        $to_save = $this->buildOrderItems($orders);
        if (count($to_save)) {
            foreach ($to_save as $order) {
                $order_stored = $this->return_order_model->addOrder(
                    $order,
                    ReturnOrderInterface::STATUS_DELIVERED_TO_THE_STORE
                );
                if (is_null($order_stored)) {
                    throw new LogicException(
                        sprintf(
                            $this->language->get
                            ('error_unable_to_save_order_with_reference'),
                            $order['reference_id']
                        )
                    );
                }
            }
        }
        return true;
    }

    public function getApproved(): bool
    {
        $orders = $this->return_order_model->connector->getApprovedReturnOrders();
        if (count($orders) == 0) {
            return false;
        }
        $to_save = $this->buildOrderItems($orders);
        if (count($to_save)) {
            foreach ($to_save as $order) {
                $order_stored = $this->return_order_model->addOrder(
                    $order,
                    ReturnOrderInterface::STATUS_RETURN_APPROVED
                );
                if (is_null($order_stored)) {
                    throw new LogicException(
                        sprintf(
                            $this->language->get
                            ('error_unable_to_save_order_with_reference'),
                            $order['reference_id']
                        )
                    );
                }
            }
        }
        return true;
    }

    public function getDecline(): bool
    {
        $orders = $this->return_order_model->connector->getDeclinedReturnedOrders();
        if (count($orders) == 0) {
            return false;
        }
        $to_save = $this->buildOrderItems($orders);
        if (count($to_save)) {
            foreach ($to_save as $order) {
                $order_stored = $this->return_order_model->addOrder(
                    $order,
                    ReturnOrderInterface::STATUS_RETURN_DECLINED
                );
                if (is_null($order_stored)) {
                    throw new LogicException(
                        sprintf(
                            $this->language->get
                            ('error_unable_to_save_order_with_reference'),
                            $order['reference_id']
                        )
                    );
                }
            }
        }
        return true;
    }

    protected function getList()
    {
        $per_page = $this->config->get('config_limit_admin');
        $filter_reference_id = (isset($this->request->get['filter_reference_id'])) ? $this->request->get['filter_reference_id'] : '';
        $filter_related_order = (isset($this->request->get['filter_related_order'])) ? $this->request->get['filter_related_order'] : '';
        $filter_sub_total = (isset($this->request->get['filter_sub_total'])) ? $this->request->get['filter_sub_total'] : '';
        $filter_total_paid = (isset($this->request->get['filter_total_paid'])) ? $this->request->get['filter_total_paid'] : '';
        $filter_customer_email = (isset($this->request->get['filter_customer_email'])) ? $this->request->get['filter_customer_email'] : '';
        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'o.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = max((isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1, 1);
        $user_token = $this->session->data['user_token'];
        $url_params = [];
        $url_params['user_token'] = $user_token;
        $url_params['order'] = $order;
        $url_params['page'] = $page;

        if ($filter_reference_id != '') {
            $url_params['filter_reference_id'] = urlencode(
                html_entity_decode($filter_reference_id, ENT_QUOTES, 'UTF-8')
            );
        }
        if ($filter_related_order != '') {
            $url_params['filter_related_order'] = urlencode(
                html_entity_decode($filter_related_order, ENT_QUOTES, 'UTF-8')
            );
        }
        if ($filter_sub_total != '') {
            $url_params['filter_sub_total'] = floatval($filter_sub_total);
        }
        if ($filter_total_paid != '') {
            $url_params['filter_total_paid'] = floatval($filter_total_paid);
        }
        if ($filter_customer_email != '') {
            $url_params['filter_customer_email'] = urlencode(
                html_entity_decode($filter_customer_email, ENT_QUOTES, 'UTF-8')
            );
        }

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(Helper\BasicHelper::getMainLink(), 'user_token=' . $user_token, true),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_orders'),
            'href' => $this->url->link($this->getLink(), http_build_query($url_params), true),
        ];
        $data['manual_sync'] = $this->url->link($this->getLink() . '/manual_sync', http_build_query($url_params), true);
        $data['accept'] = ($this->model_extension_module_onecode_shopflix_config->convertOrders())
            ? $this->url->link($this->getLink() . '/accept', http_build_query($url_params), true)
            : false;

        $data['orders'] = [];

        $filter_data = [
            'filter_reference_id' => $filter_reference_id,
            'filter_related_order' => $filter_related_order,
            'filter_sub_total' => $filter_sub_total,
            'filter_total_paid' => $filter_total_paid,
            'filter_customer_email' => $filter_customer_email,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $per_page,
            'limit' => $per_page,
        ];

        $order_total = $this->return_order_model->getTotalOrders($filter_data);
        $results = $this->return_order_model->getAllOrders($filter_data);

        foreach ($results as $result) {
            $order_url_params = http_build_query(array_merge($url_params, ['order_id' => $result['id']]));
            $data['orders'][] = [
                'order_id' => $result['id'],
                'reference_id' => $result['reference_id'],
                'related_order' => $result['vendor_parent_id'],
                'related_order_id' => $this->order_model->getOrderByReferenceId($result['vendor_parent_id'])['id'] ?? 0,
                'status' => $result['status'],
                'status_string' => $this->language->get('text_status_' . $result['status']),
                'sub_total' => floatval($result['sub_total']),
                'total_paid' => floatval($result['total_paid']),
                'customer_email' => $result['customer_email'],
                'customer_firstname' => $result['customer_firstname'],
                'customer_lastname' => $result['customer_lastname'],
                'customer_remote_ip' => $result['customer_remote_ip'],
                'view' => $this->url->link($this->getLink() . '/view', $order_url_params, true),
                'accept' => ($result['status'] == ReturnOrderInterface::STATUS_DELIVERED_TO_THE_STORE)
                    ? $this->url->link($this->getLink() . '/accept', $order_url_params, true)
                    : false,
                'decline' => ($result['status'] == ReturnOrderInterface::STATUS_DELIVERED_TO_THE_STORE)
                    ? $this->url->link($this->getLink() . '/decline', $order_url_params, true)
                    : false,
            ];
        }

        $data['user_token'] = $user_token;
        $data['error_warning'] = (isset($this->error['warning'])) ? $this->error['warning'] : '';

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        if (isset($this->session->data['errors'])) {
            $data['error_warning'] = $this->session->data['errors'][0];
            unset($this->session->data['errors']);
        }
        $data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : [];

        $data['sort_id'] = $this->url->link(
            $this->getLink(),
            http_build_query(array_merge($url_params, ['sort=' => 'o.id'])),
            true
        );
        $data['sort_reference_id'] = $this->url->link(
            $this->getLink(),
            http_build_query(array_merge($url_params, ['sort=' => 'o.reference_id'])),
            true
        );
        $data['sort_related_order'] = $this->url->link(
            $this->getLink(),
            http_build_query(array_merge($url_params, ['sort=' => 'o.vendor_parent_id'])),
            true
        );
        $data['sort_sub_total'] = $this->url->link(
            $this->getLink(),
            http_build_query(array_merge($url_params, ['sort=' => 'o.sub_total'])),
            true
        );
        $data['sort_total_paid'] = $this->url->link(
            $this->getLink(),
            http_build_query(array_merge($url_params, ['sort=' => 'o.total_paid'])),
            true
        );
        $data['sort_status'] = $this->url->link(
            $this->getLink(),
            http_build_query(array_merge($url_params, ['sort=' => 'o.status'])),
            true
        );
        $data['sort_customer_email'] = $this->url->link(
            $this->getLink(),
            http_build_query(array_merge($url_params, ['sort=' => 'o.customer_email'])),
            true
        );

        $pagination_params = array_merge($url_params, ['page' => '{page}']);
        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = $per_page;
        $pagination->url = $this->url->link($this->getLink(), http_build_query($pagination_params), true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($order_total) ? (($page - 1) *
                    $per_page) + 1 : 0,
            ((($page - 1) * $per_page) > ($order_total -
                    $per_page)) ? $order_total : ((($page - 1) * $per_page) +
                $per_page),
            $order_total,
            ceil($order_total / $per_page)
        );

        $data['filter_reference_id'] = $filter_reference_id;
        $data['filter_related_order'] = $filter_related_order;
        $data['filter_sub_total'] = $filter_sub_total;
        $data['filter_total_paid'] = $filter_total_paid;
        $data['filter_customer_email'] = $filter_customer_email;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(Helper\BasicHelper::getPath() . '/return_order_list', $data));
    }

    public function autocomplete()
    {
        $json = [];

        if (isset($this->request->get['filter_reference_id'])
            || isset($this->request->get['filter_related_order'])
            || isset($this->request->get['filter_customer_email'])
        ) {
            $filter_reference_id = (isset($this->request->get['filter_reference_id']))
                ? $this->request->get['filter_reference_id']
                : '';
            $filter_related_order = (isset($this->request->get['filter_related_order']))
                ? $this->request->get['filter_related_order']
                : '';
            $filter_customer_email = (isset($this->request->get['filter_customer_email']))
                ? $this->request->get['filter_customer_email']
                : "";

            $limit = (isset($this->request->get['limit'])) ? (int)$this->request->get['limit'] : 5;

            $filter_data = [
                'filter_reference_id' => $filter_reference_id,
                'filter_related_order' => $filter_related_order,
                'filter_customer_email' => $filter_customer_email,
                'start' => 0,
                'limit' => $limit,
            ];

            $results = $this->return_order_model->getAllOrders($filter_data);

            foreach ($results as $result) {
                $json[] = [
                    'order_id' => $result['id'],
                    'related_order' => strip_tags(html_entity_decode($result['vendor_parent_id'], ENT_QUOTES, 'UTF-8')),
                    'reference_id' => strip_tags(html_entity_decode($result['reference_id'], ENT_QUOTES, 'UTF-8')),
                    'customer_email' => strip_tags(html_entity_decode($result['customer_email'], ENT_QUOTES, 'UTF-8')),
                ];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}