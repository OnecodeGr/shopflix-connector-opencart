<?php

use Onecode\Shopflix\Helper;

require_once DIR_SYSTEM . 'library/onecode/EventGroup.php';

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Response $response
 * @property-read \Loader $load
 * @property-read \Language $language
 * @property-read \Url $url
 * @property-read \Cart\User $user
 * @property-read \ModelExtensionModuleOnecodeShopflixOrder $model_extension_module_onecode_shopflix_order
 * @property-read \ModelExtensionModuleOnecodeShopflixProductAttributes
 * $model_extension_module_onecode_shopflix_product_attributes
 * @property-read \ModelExtensionModuleOnecodeShopflixProduct $model_extension_module_onecode_shopflix_product
 * @property-read \ModelExtensionModuleOnecodeShopflixShipment $model_extension_module_onecode_shopflix_shipment
 * @property-read \ModelExtensionModuleOnecodeShopflixEvent $model_extension_module_onecode_shopflix_event
 * @property-read \ModelExtensionModuleOnecodeShopflixConfig $model_extension_module_onecode_shopflix_config
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \Onecode\Shopflix\Helper\ConfigHelper $configHelper
 */
class ControllerExtensionModuleOnecodeShopflix extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('extension/module/onecode/shopflix/order');
        $this->load->model('extension/module/onecode/shopflix/product');
        $this->load->model('extension/module/onecode/shopflix/product_attributes');
        $this->load->model('extension/module/onecode/shopflix/shipment');
        $this->load->model('extension/module/onecode/shopflix/event');
        $this->load->model('extension/module/onecode/shopflix/config');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Onecode\Shopflix\Helper\BasicHelper($registry);
        $this->load->helper('onecode/shopflix/ConfigHelper');
        $this->configHelper = new Onecode\Shopflix\Helper\ConfigHelper($registry);
        $this->load->language(Helper\BasicHelper::getMainLink());
    }

    public function index()
    {
        $moduleId = $this->request->get['module_id'] ?? null;
        if ($moduleId)
        {
            $this->moduleConfigure($moduleId);
        }
        else
        {
            $module = $this->configHelper->getCurrentModule();
            $this->response->redirect(
                $this->url->link(
                    Helper\BasicHelper::getMainLink(),
                    [
                        'user_token' => $this->session->data['user_token'],
                        'module_id' => $module['module_id'],
                    ], true
                ));
        }
    }

    public function install()
    {
        $this->model_extension_module_onecode_shopflix_product_attributes->install();
        $this->model_extension_module_onecode_shopflix_config->install();
        $this->model_extension_module_onecode_shopflix_event->install();
        $this->model_extension_module_onecode_shopflix_product->install();
        $this->model_extension_module_onecode_shopflix_order->install();
        $this->model_extension_module_onecode_shopflix_shipment->install();
    }

    public function uninstall()
    {
        $this->model_extension_module_onecode_shopflix_product_attributes->install();
        $this->model_extension_module_onecode_shopflix_config->uninstall();
        $this->model_extension_module_onecode_shopflix_event->uninstall();
        $this->model_extension_module_onecode_shopflix_shipment->uninstall();
        $this->model_extension_module_onecode_shopflix_product->uninstall();
        $this->model_extension_module_onecode_shopflix_order->uninstall();
    }

    public function validate()
    {
        if (! $this->user->hasPermission('modify', 'extension/module/onecode/shopflix/onecode_shopflix'))
        {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        //if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
        //    $this->error['name'] = $this->language->get('error_name');
        //}
        return ! $this->error;
    }

    protected function moduleConfigure($moduleId)
    {
        $this->document->setTitle($this->language->get('heading_title_main'));
        if ($this->request->server['REQUEST_METHOD'] == 'POST')
        {
            $this->model_extension_module_onecode_shopflix_config->save($this->request->post, $moduleId);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link(Helper\BasicHelper::getMainLink(), [
                    'user_token' => $this->session->data['user_token'],
                    'module_id' => $moduleId,
                ], true));
        }

        $data = array_merge([], $this->formBreadcrumbs());
        $data = array_merge($data, $this->formErrors());
        $data = array_merge($data, $this->formData($moduleId));

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $data['column_left'] = $this->load->controller('common/column_left');
        $this->response->setOutput($this->load->view(Helper\BasicHelper::getPath().'/config', $data));
    }

    /**
     * @return array
     */
    protected function formErrors(): array
    {
        return [
            'error_warning' => $this->error['warning'] ?? '',
            'error_title' => $this->error['title'] ?? '',
            'error_description' => $this->error['description'] ?? '',
            'error_keyword' => $this->error['error_keyword'] ?? '',
            'error_status' => $this->error['error_status'] ?? '',
            'error_convert_to_order' => $this->error['error_convert_to_order'] ?? '',
            'error_api_url' => $this->error['error_api_url'] ?? '',
            'error_api_username' => $this->error['error_api_username'] ?? '',
            'error_api_password' => $this->error['error_api_password'] ?? '',
            'error_xml_status' => $this->error['error_xml_status'] ?? '',
            'error_xml_export_category_tree' => $this->error['error_xml_export_category_tree'] ?? '',
            'error_xml_mpn_attr' => $this->error['error_xml_mpn_attr'] ?? '',
            'error_xml_ean_attr' => $this->error['error_xml_ean_attr'] ?? '',
            'error_xml_title_attr' => $this->error['error_xml_title_attr'] ?? '',
            'error_auto_accept_order' => $this->error['error_auto_accept_order'] ?? '',
            'error_xml_description_attr' => $this->error['error_xml_description_attr'] ?? '',
            'error_xml_brand_attr' => $this->error['error_xml_brand_attr'] ?? '',
            'error_xml_weight_attr' => $this->error['error_xml_weight_attr'] ?? '',
        ];
    }

    /**
     * @param string $moduleId
     *
     * @return array
     */
    protected function formData(string $moduleId): array
    {
        $user_token = $this->session->data['user_token'];
        $attributes = $this->model_extension_module_onecode_shopflix_product_attributes
            ->getProductAttributes();
        $lang_id = $this->config->get('config_language_id');
        array_map(function ($attr) use ($lang_id) {
            $name = (key_exists($lang_id, $attr['description']))
                ? $attr['description'][$lang_id]['name']
                : 'undefined';
            return [
                'attribute_id' => $attr['attribute_id'],
                'name' => $name,
            ];
        }, $attributes);
        return array_merge([
            'action' => $this->url->link(Helper\BasicHelper::getMainLink(), [
                'user_token' => $user_token,
                'module_id' => $moduleId,
            ], true),
            'user_token' => $user_token,
            'product_attributes' => $attributes,
        ], $this->model_extension_module_onecode_shopflix_config->loadData());
    }

    protected function formBreadcrumbs(): array
    {
        $args = [
            'user_token' => $this->session->data['user_token'],
        ];

        return [
            'breadcrumbs' => [
                [
                    'text' => $this->language->get('text_home'),
                    'href' => $this->url->link('common/dashboard', $args, true),
                ],
                [
                    'text' => $this->language->get('heading_title_main'),
                    'href' => $this->url->link(Helper\BasicHelper::getMainLink(), $args, true),
                ],
            ],
        ];
    }

    public function eventInjectAdminMenuItem($eventRoute, &$data)
    {
        $shopflix_menu = [];
        $module = $this->configHelper->getCurrentModule();
        $module_id = key_exists('module_id', $module) ? $module['module_id'] : 0;
        $url_params = [
            'user_token' => $this->session->data['user_token'],
            'module_id' => $module_id
        ];
        if ($this->user->hasPermission('access', Helper\BasicHelper::getMainLink()))
        {
            $shopflix_menu[] = [
                'name' => $this->language->get('text_Configuration'),
                'href' => $this->url->link(
                    Helper\BasicHelper::getMainLink(),
                    $url_params,
                    true
                )
            ];
            $shopflix_menu[] = [
                'name' => $this->language->get('text_Products'),
                'href' => $this->url->link(
                    Helper\BasicHelper::getMainLink().'_product',
                    $url_params,
                    true
                )
            ];
            $shopflix_menu[] = [
                'name' => $this->language->get('text_Orders'),
                'href' => $this->url->link(
                    Helper\BasicHelper::getMainLink().'_order',
                    $url_params,
                    true
                )
            ];
        }
        if (count($shopflix_menu))
        {
            $exists = array_filter($data['menus'], function ($menu) {
                return $menu['id'] === 'menu-onecdoe';
            });

            if (count($exists) == 0)
            {
                $data['menus'][] = [
                    'id' => 'menu-onecdoe',
                    'icon' => 'fa-braille',
                    'name' => 'OneCode',
                    'href' => '',
                    'children' => [],
                ];
            }

            foreach ($data['menus'] as &$menu)
            {
                if ($menu['id'] === 'menu-onecdoe')
                {
                    $menu['children'][] = [
                        'id' => 'menu-onecdoe-shopflix',
                        'icon' => 'fa-cog',
                        'name' => 'ShopFlix',
                        'href' => '',
                        'children' => $shopflix_menu,
                    ];
                }
            }
        }
    }
}