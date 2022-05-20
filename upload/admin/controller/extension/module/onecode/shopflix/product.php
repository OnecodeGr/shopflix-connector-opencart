<?php

use Onecode\Shopflix\Helper;

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Config $config
 * @property-read \Response $response
 * @property-read \Loader $load
 * @property-read \Language $language
 * @property-read \Url $url
 * @property-read \Cart\User $user
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \ModelLocalisationLanguage $model_localisation_language
 * @property-read \ModelCatalogProduct $model_catalog_product
 * @property-read \ModelCatalogOption $model_catalog_option
 * @property-read \ModelToolImage $model_tool_image
 * @property-read \ModelCatalogFilter $model_catalog_filter
 * @property-read \ModelSettingStore $model_setting_store
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $product_model
 */
class ControllerExtensionModuleOnecodeShopflixProduct extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('tool/image');
        $this->load->model('catalog/product');
        $this->load->model('catalog/option');
        $this->load->model('localisation/language');
        $this->load->model('setting/store');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('catalog/filter');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Helper\BasicHelper($registry);
        $this->product_model = new ModelExtensionModuleOnecodeShopflixProduct($registry);
        $this->load->language('extension/module/onecode_shopflix_product');
    }

    protected function getLink(): string
    {
        return 'extension/module/onecode/shopflix/product';
    }

    public function validate(): bool
    {
        if (! $this->user->hasPermission('modify', 'extension/module/onecode/shopflix/product'))
        {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return ! $this->error;
    }

    public function index()
    {
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getList();
    }

    public function enableAll()
    {
        $filter_manufacturer = (isset($this->request->get['filter_manufacturer'])) ? $this->request->get['filter_manufacturer'] : '';
        $filter_category = (isset($this->request->get['filter_category'])) ? $this->request->get['filter_category'] : '';
        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : '';
        $filter_model = (isset($this->request->get['filter_model'])) ? $this->request->get['filter_model'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_enabled = (isset($this->request->get['filter_enabled'])) ? $this->request->get['filter_enabled'] : '';

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'p.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = max((isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1, 1);

        $user_token = $this->session->data['user_token'];
        $url_params = [];
        $url_params['user_token'] = $user_token;
        $url_params['order'] = $order;
        $url_params['page'] = $page;

        if ($filter_name != '') {
            $url_params['filter_name'] = urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_status != '') {
            $url_params['filter_status'] = $filter_status;
        }
        if ($filter_manufacturer != '') {
            $url_params['filter_manufacturer'] = $filter_manufacturer;
        }
        if ($filter_category != '') {
            $url_params['filter_category'] = $filter_category;
        }
        if ($filter_model != '') {
            $url_params['filter_model'] = urlencode(html_entity_decode($filter_model, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_enabled != '') {
            $url_params['filter_enabled'] = $filter_enabled;
        }

        $filter_data = [
            'filter_name' => $filter_name,
            'filter_model' => $filter_model,
            'filter_manufacturer' => $filter_manufacturer,
            'filter_category' => $filter_category,
            'filter_status' => 1,
            'sort' => $sort,
            'order' => $order,
        ];

        $results = $this->product_model->getAllProducts($filter_data);

        $ids = [];
        if (count($results)) {
            $ids = array_column($results, 'product_id');
        }
        $this->model_extension_module_onecode_shopflix_product->clear($ids);
        $this->model_extension_module_onecode_shopflix_product->enable($ids);
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                http_build_query($url_params),
                true
            )
        );
    }

    public function syncAll()
    {
        $filter_manufacturer = (isset($this->request->get['filter_manufacturer'])) ? $this->request->get['filter_manufacturer'] : '';
        $filter_category = (isset($this->request->get['filter_category'])) ? $this->request->get['filter_category'] : '';
        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : '';
        $filter_model = (isset($this->request->get['filter_model'])) ? $this->request->get['filter_model'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_enabled = (isset($this->request->get['filter_enabled'])) ? $this->request->get['filter_enabled'] : '';

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'p.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = max((isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1, 1);

        $user_token = $this->session->data['user_token'];
        $url_params = [];
        $url_params['user_token'] = $user_token;
        $url_params['order'] = $order;
        $url_params['page'] = $page;

        if ($filter_name != '') {
            $url_params['filter_name'] = urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_status != '') {
            $url_params['filter_status'] = $filter_status;
        }
        if ($filter_manufacturer != '') {
            $url_params['filter_manufacturer'] = $filter_manufacturer;
        }
        if ($filter_category != '') {
            $url_params['filter_category'] = $filter_category;
        }
        if ($filter_model != '') {
            $url_params['filter_model'] = urlencode(html_entity_decode($filter_model, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_enabled != '') {
            $url_params['filter_enabled'] = $filter_enabled;
        }

        $this->model_extension_module_onecode_shopflix_product->sync();
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                http_build_query($url_params),
                true
            )
        );
    }

    public function enable()
    {
        $filter_manufacturer = (isset($this->request->get['filter_manufacturer'])) ? $this->request->get['filter_manufacturer'] : '';
        $filter_category = (isset($this->request->get['filter_category'])) ? $this->request->get['filter_category'] : '';
        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : '';
        $filter_model = (isset($this->request->get['filter_model'])) ? $this->request->get['filter_model'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_enabled = (isset($this->request->get['filter_enabled'])) ? $this->request->get['filter_enabled'] : '';

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'p.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = max((isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1, 1);

        $user_token = $this->session->data['user_token'];
        $url_params = [];
        $url_params['user_token'] = $user_token;
        $url_params['order'] = $order;
        $url_params['page'] = $page;

        if ($filter_name != '') {
            $url_params['filter_name'] = urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_status != '') {
            $url_params['filter_status'] = $filter_status;
        }
        if ($filter_manufacturer != '') {
            $url_params['filter_manufacturer'] = $filter_manufacturer;
        }
        if ($filter_category != '') {
            $url_params['filter_category'] = $filter_category;
        }
        if ($filter_model != '') {
            $url_params['filter_model'] = urlencode(html_entity_decode($filter_model, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_enabled != '') {
            $url_params['filter_enabled'] = $filter_enabled;
        }

        if (isset($this->request->post['selected']))
        {
            $id_list = $this->request->post['selected'];
            $this->model_extension_module_onecode_shopflix_product->enable($id_list);
        }
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                http_build_query($url_params),
                true
            )
        );
    }

    public function disableAll()
    {
        $filter_manufacturer = (isset($this->request->get['filter_manufacturer'])) ? $this->request->get['filter_manufacturer'] : '';
        $filter_category = (isset($this->request->get['filter_category'])) ? $this->request->get['filter_category'] : '';
        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : '';
        $filter_model = (isset($this->request->get['filter_model'])) ? $this->request->get['filter_model'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_enabled = (isset($this->request->get['filter_enabled'])) ? $this->request->get['filter_enabled'] : '';

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'p.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = max((isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1, 1);

        $user_token = $this->session->data['user_token'];
        $url_params = [];
        $url_params['user_token'] = $user_token;
        $url_params['order'] = $order;
        $url_params['page'] = $page;

        if ($filter_name != '') {
            $url_params['filter_name'] = urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_status != '') {
            $url_params['filter_status'] = $filter_status;
        }
        if ($filter_manufacturer != '') {
            $url_params['filter_manufacturer'] = $filter_manufacturer;
        }
        if ($filter_category != '') {
            $url_params['filter_category'] = $filter_category;
        }
        if ($filter_model != '') {
            $url_params['filter_model'] = urlencode(html_entity_decode($filter_model, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_enabled != '') {
            $url_params['filter_enabled'] = $filter_enabled;
        }

        $filter_data = [
            'filter_name' => $filter_name,
            'filter_model' => $filter_model,
            'filter_manufacturer' => $filter_manufacturer,
            'filter_category' => $filter_category,
            'filter_status' => 1,
            'sort' => $sort,
            'order' => $order,
        ];

        $results = $this->product_model->getAllProducts($filter_data);
        $ids = [];
        if (count($results)) {
            $ids = array_column($results, 'product_id');
        }
        $this->model_extension_module_onecode_shopflix_product->clear($ids);
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                http_build_query($url_params),
                true
            )
        );
    }

    public function disable()
    {
        $filter_manufacturer = (isset($this->request->get['filter_manufacturer'])) ? $this->request->get['filter_manufacturer'] : '';
        $filter_category = (isset($this->request->get['filter_category'])) ? $this->request->get['filter_category'] : '';
        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : '';
        $filter_model = (isset($this->request->get['filter_model'])) ? $this->request->get['filter_model'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_enabled = (isset($this->request->get['filter_enabled'])) ? $this->request->get['filter_enabled'] : '';

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'p.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = max((isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1, 1);

        $user_token = $this->session->data['user_token'];
        $url_params = [];
        $url_params['user_token'] = $user_token;
        $url_params['order'] = $order;
        $url_params['page'] = $page;

        if ($filter_name != '') {
            $url_params['filter_name'] = urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_status != '') {
            $url_params['filter_status'] = $filter_status;
        }
        if ($filter_manufacturer != '') {
            $url_params['filter_manufacturer'] = $filter_manufacturer;
        }
        if ($filter_category != '') {
            $url_params['filter_category'] = $filter_category;
        }
        if ($filter_model != '') {
            $url_params['filter_model'] = urlencode(html_entity_decode($filter_model, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_enabled != '') {
            $url_params['filter_enabled'] = $filter_enabled;
        }

        $id_list = key_exists('selected', $this->request->post) ? $this->request->post['selected'] : [];
        if (count($id_list))
        {
            $this->model_extension_module_onecode_shopflix_product->disable($id_list);
        }
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                http_build_query($url_params),
                true
            )
        );
    }

    protected function getList()
    {
        $per_page = $this->config->get('config_limit_admin');
        $filter_manufacturer = (isset($this->request->get['filter_manufacturer'])) ? $this->request->get['filter_manufacturer'] : '';
        $filter_category = (isset($this->request->get['filter_category'])) ? $this->request->get['filter_category'] : '';
        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : '';
        $filter_model = (isset($this->request->get['filter_model'])) ? $this->request->get['filter_model'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_enabled = (isset($this->request->get['filter_enabled'])) ? $this->request->get['filter_enabled'] : '';

        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'p.id';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
        $page = max((isset($this->request->get['page'])) ? (int) $this->request->get['page'] : 1, 1);

        $user_token = $this->session->data['user_token'];
        $url_params = [];
        $url_params['user_token'] = $user_token;
        $url_params['order'] = $order;
        $url_params['page'] = $page;

        if ($filter_name != '')
        {
            $url_params['filter_name'] = urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_status != '')
        {
            $url_params['filter_status'] = $filter_status;
        }
        if ($filter_manufacturer != '')
        {
            $url_params['filter_manufacturer'] = $filter_manufacturer;
        }
        if ($filter_category != '')
        {
            $url_params['filter_category'] = $filter_category;
        }
        if ($filter_model != '')
        {
            $url_params['filter_model'] = urlencode(html_entity_decode($filter_model, ENT_QUOTES, 'UTF-8'));
        }
        if ($filter_enabled != '')
        {
            $url_params['filter_enabled'] = $filter_enabled;
        }


        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(Helper\BasicHelper::getMainLink(), 'user_token=' . $user_token, true),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_product'),
            'href' => $this->url->link($this->getLink(), http_build_query($url_params), true),
        ];

        $data['enable_all'] = $this->url->link($this->getLink() . '/enableAll', http_build_query($url_params), true);
        $data['enable'] = $this->url->link($this->getLink() . '/enable', http_build_query($url_params), true);
        $data['disable_all'] = $this->url->link($this->getLink() . '/disableAll', http_build_query($url_params), true);
        $data['disable'] = $this->url->link($this->getLink() . '/disable', http_build_query($url_params), true);
        $data['sync'] = $this->url->link($this->getLink() . '/syncAll', http_build_query($url_params), true);

        $data['products'] = [];

        $filter_data = [
            'filter_name' => $filter_name,
            'filter_model' => $filter_model,
            'filter_manufacturer' => $filter_manufacturer,
            'filter_category' => $filter_category,
            'filter_status' => $filter_status,
            'filter_enabled' => $filter_enabled,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $per_page,
            'limit' => $per_page,
        ];

        $product_total = $this->product_model->getTotalProducts($filter_data);
        $results = $this->product_model->getAllProducts($filter_data);

        foreach ($results as $result)
        {
            $image = (is_file(DIR_IMAGE . $result['image']))
                ? $this->model_tool_image->resize($result['image'], 40, 40)
                : $this->model_tool_image->resize('no_image.png', 40, 40);

            $categories = $this->product_model->getProductCategoriesName($result['product_id']);
            $data['products'][] = [
                'product_id' => $result['product_id'],
                'image' => $image,
                'name' => $result['name'],
                'manufacturer' => $result['manufacturer'],
                'categories' => $categories,
                'model' => $result['model'],
                'status' => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'enabled' => $result['enabled'] ? $this->language->get('text_enabled') : $this->language->get(
                    'text_disabled'
                ),
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

        $data['sort_name'] = $this->url->link($this->getLink(), http_build_query(array_merge($url_params, ['sort=' => 'pd.name'])), true);
        $data['sort_model'] = $this->url->link($this->getLink(), http_build_query(array_merge($url_params, ['sort=' => 'p.model'])), true);
        $data['sort_status'] = $this->url->link($this->getLink(), http_build_query(array_merge($url_params, ['sort=' => 'p.status'])), true);
        $data['sort_enabled'] = $this->url->link($this->getLink(), http_build_query(array_merge($url_params, ['sort=' => 'enabled'])), true);

        $pagination_params = array_merge($url_params, ['page'=>'{page}']);
        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $per_page;
        $pagination->url = $this->url->link($this->getLink(), http_build_query($pagination_params), true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) *
                $per_page) + 1 : 0, ((($page - 1) * $per_page) > ($product_total -
                $per_page)) ? $product_total : ((($page - 1) * $per_page) +
            $per_page), $product_total, ceil($product_total / $per_page));

        $data['filter_name'] = $filter_name;
        $data['filter_manufacturer'] = $filter_manufacturer;
        $data['filter_category'] = $filter_category;
        $data['filter_model'] = $filter_model;
        $data['filter_status'] = $filter_status;
        $data['filter_enabled'] = $filter_enabled;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view(Helper\BasicHelper::getPath() . '/product_list', $data));
    }

    public function autocomplete()
    {
        $json = [];

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model']))
        {
            if (isset($this->request->get['filter_name']))
            {
                $filter_name = $this->request->get['filter_name'];
            }
            else
            {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model']))
            {
                $filter_model = $this->request->get['filter_model'];
            }
            else
            {
                $filter_model = '';
            }

            if (isset($this->request->get['limit']))
            {
                $limit = (int) $this->request->get['limit'];
            }
            else
            {
                $limit = 5;
            }

            $filter_data = [
                'filter_name' => $filter_name,
                'filter_model' => $filter_model,
                'start' => 0,
                'limit' => $limit,
            ];

            $results = $this->model_catalog_product->getProducts($filter_data);

            foreach ($results as $result)
            {
                $option_data = [];

                $product_options = $this->model_catalog_product->getProductOptions($result['product_id']);

                foreach ($product_options as $product_option)
                {
                    $option_info = $this->model_catalog_option->getOption($product_option['option_id']);

                    if ($option_info)
                    {
                        $product_option_value_data = [];

                        foreach ($product_option['product_option_value'] as $product_option_value)
                        {
                            $option_value_info = $this->model_catalog_option->getOptionValue($product_option_value['option_value_id']);

                            if ($option_value_info)
                            {
                                $product_option_value_data[] = [
                                    'product_option_value_id' => $product_option_value['product_option_value_id'],
                                    'option_value_id' => $product_option_value['option_value_id'],
                                    'name' => $option_value_info['name'],
                                    'price' => (float) $product_option_value['price'] ? $this->currency->format($product_option_value['price'], $this->config->get('config_currency')) : false,
                                    'price_prefix' => $product_option_value['price_prefix'],
                                ];
                            }
                        }

                        $option_data[] = [
                            'product_option_id' => $product_option['product_option_id'],
                            'product_option_value' => $product_option_value_data,
                            'option_id' => $product_option['option_id'],
                            'name' => $option_info['name'],
                            'type' => $option_info['type'],
                            'value' => $product_option['value'],
                            'required' => $product_option['required'],
                        ];
                    }
                }

                $json[] = [
                    'product_id' => $result['product_id'],
                    'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model' => $result['model'],
                    'option' => $option_data,
                    'price' => $result['price'],
                ];
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}