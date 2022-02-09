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
 * @property-read \ModelToolImage $model_tool_image
 * @property-read \ModelCatalogFilter $model_catalog_filter
 * @property-read \ModelSettingStore $model_setting_store
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 */
class ControllerExtensionModuleOnecodeShopflixProduct extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('tool/image');
        $this->load->model('catalog/product');
        $this->load->model('localisation/language');
        $this->load->model('setting/store');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('catalog/filter');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Helper\BasicHelper($registry);
        $this->load->language('extension/module/onecode_shopflix_product');
    }

    protected function getLink()
    {
        return 'extension/module/onecode/shopflix/product';
    }

    public function validate()
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
        //clear data from table
        $this->model_extension_module_onecode_shopflix_product->clearAll();
        //fetch all products
        $products = $this->model_catalog_product->getProducts(['filter_status' => 1]);
        //store all product to shopflix table
        $this->model_extension_module_onecode_shopflix_product->enable(array_column($products, 'product_id'));
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ], true
            ));
    }

    public function enable()
    {
        if (isset($this->request->post['selected']))
        {
            $id_list = $this->request->post['selected'];
            $this->model_extension_module_onecode_shopflix_product->enable($id_list);
        }
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ], true
            ));
    }

    public function disableAll()
    {
        $this->model_extension_module_onecode_shopflix_product->clearAll();
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ], true
            ));
    }

    public function disable()
    {
        $id_list = key_exists('selected', $this->request->post) ? $this->request->post['selected'] : [];
        if (count($id_list))
        {
            $this->model_extension_module_onecode_shopflix_product->disable($id_list);
        }
        $this->response->redirect(
            $this->url->link(
                $this->getLink(),
                [
                    'user_token' => $this->session->data['user_token'],
                ], true
            ));
    }

    protected function getList()
    {
        $filter_name = (isset($this->request->get['filter_name'])) ? $this->request->get['filter_name'] : '';
        $filter_model = (isset($this->request->get['filter_model'])) ? $this->request->get['filter_model'] : '';
        $filter_status = (isset($this->request->get['filter_status'])) ? $this->request->get['filter_status'] : '';
        $filter_enabled = (isset($this->request->get['filter_enabled'])) ? $this->request->get['filter_enabled'] : '';
        $sort = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'pd.name';
        $order = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'ASC';
        $page = (isset($this->request->get['page'])) ? (int) $this->request->get['page'] : 1;

        $url = '';

        if (isset($this->request->get['filter_name']))
        {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model']))
        {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status']))
        {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_enabled']))
        {
            $url .= '&filter_enabled=' . $this->request->get['filter_enabled'];
        }

        if (isset($this->request->get['order']))
        {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page']))
        {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(Helper\BasicHelper::getMainLink(), 'user_token=' . $this->session->data['user_token'], true),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_product'),
            'href' => $this->url->link($this->getLink(), 'user_token=' . $this->session->data['user_token'] .
                $url, true),
        ];

        $data['enable_all'] = $this->url->link(
            $this->getLink() . '/enableAll',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );
        $data['enable'] = $this->url->link(
            $this->getLink() . '/enable',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );
        $data['disable'] = $this->url->link(
            $this->getLink() . '/disable',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );
        $data['disable_all'] = $this->url->link(
            $this->getLink() . '/disableAll',
            'user_token=' . $this->session->data['user_token'] . $url,
            true
        );

        $data['products'] = [];

        $filter_data = [
            'filter_name' => $filter_name,
            'filter_model' => $filter_model,
            'filter_status' => $filter_status,
            'filter_enabled' => $filter_enabled,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin'),
        ];

        $product_total = $this->model_extension_module_onecode_shopflix_product->getTotalProducts($filter_data);
        $results = $this->model_extension_module_onecode_shopflix_product->getAllProducts($filter_data);

        foreach ($results as $result)
        {
            $image = (is_file(DIR_IMAGE . $result['image']))
                ? $this->model_tool_image->resize($result['image'], 40, 40)
                : $this->model_tool_image->resize('no_image.png', 40, 40);

            $data['products'][] = [
                'product_id' => $result['product_id'],
                'image' => $image,
                'name' => $result['name'],
                'model' => $result['model'],
                'status' => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'enabled' => $result['enabled'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit' => $this->url->link($this->getLink() . '/edit', 'user_token=' .
                    $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
            ];
        }

        $data['user_token'] = $this->session->data['user_token'];
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
        $url = '';

        if (isset($this->request->get['filter_name']))
        {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model']))
        {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status']))
        {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_enabled']))
        {
            $url .= '&filter_enabled=' . $this->request->get['filter_enabled'];
        }

        if ($order == 'ASC')
        {
            $url .= '&order=DESC';
        }
        else
        {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page']))
        {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link($this->getLink(), 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name'
            . $url, true);
        $data['sort_model'] = $this->url->link($this->getLink(), 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, true);
        $data['sort_status'] = $this->url->link($this->getLink(), 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
        $data['sort_enabled'] = $this->url->link($this->getLink(), 'user_token=' . $this->session->data['user_token'] .
            '&sort=enabled' . $url, true);
        $data['sort_order'] = $this->url->link($this->getLink(), 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_name']))
        {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model']))
        {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status']))
        {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_enabled']))
        {
            $url .= '&filter_enabled=' . $this->request->get['filter_enabled'];
        }

        if (isset($this->request->get['sort']))
        {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order']))
        {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link($this->getLink(), 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
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
}