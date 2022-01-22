<?php

use Onecode\Library\EventGroup;
use Onecode\Library\EventRow;
use Onecode\Shopflix\Helper;

/**
 * @property-read \Document $document
 * @property-read \Request $request
 * @property-read \Session $session
 * @property-read \Response $response
 * @property-read \Loader $load
 * @property-read \Language $language
 * @property-read \Url $url
 * @property-read \ModelSettingSetting $model_setting_setting
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingEvent $model_setting_event
 * @property-read \ModelSettingModule $model_setting_module
 * @property-read \ModelExtensionModuleOnecodeShopflixBasic $model_extension_module_onecode_shopflix_Basic
 */
class ControllerExtensionModuleOnecodeShopflix extends Controller
{
    private $error = [];

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->model('setting/module');
        $this->load->model('setting/event');
        $this->load->model('setting/extension');
        $this->load->model('extension/module/onecode/shopflix/Basic');
        $this->load->helper('onecode/shopflix/Helper');
        $this->load->language(Helper\Basic::getMainLink());
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
            $module = Helper\Basic::getCurrentModule($this->model_setting_module);
            $this->response->redirect(
                $this->url->link(
                    Helper\Basic::getMainLink(),
                    [
                        'user_token' => $this->session->data['user_token'],
                        'module_id' => $module['module_id'],
                    ], true
                ));
        }
    }

    public function install()
    {
        $this->model_extension_module_onecode_shopflix_Basic->install();
        $this->model_setting_setting->editSetting(Helper\Basic::getModuleId(), [
            Helper\Basic::getModuleId() . '_status' => 1,
        ]);
        $this->model_setting_extension->install('module', Helper\Basic::getModuleId());
        foreach ($this->getEventList()->get() as $item)
        {
            $this->model_setting_event->addEvent(
                $item->code,
                $item->trigger,
                $item->action,
                $item->status ?? 1,
                $item->order ?? 0
            );
        }
    }

    public function uninstall()
    {
        $this->model_extension_module_onecode_shopflix_Basic->uninstall();
        $this->model_setting_setting->deleteSetting(Helper\Basic::getModuleId());
        foreach ($this->getEventList()->get() as $item)
        {
            $this->model_setting_event->deleteEventByCode($item->code);
        }
        $this->model_setting_extension->uninstall('module', Helper\Basic::getModuleId());
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
            $postData = $this->request->post;
            //if (isset($postData['shopflix']) && !empty($postData['shopflix']['createToken'])) {
            //    $postData['s1']['token'] = $this->SoftoneApiLibrary->createS1AuthToken($postData['s1']['username'], $postData['s1']['password']);
            //    unset($postData['s1']['createToken']);
            //}
            //$this->model_extension_module_onecode_shopflix_basic->save($postData, $moduleId);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link(Helper\Basic::getMainLink(), [
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
        $this->response->setOutput($this->load->view(Helper\Basic::getPath().'/config', $data));
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
                    'href' => $this->url->link(Helper\Basic::getMainLink(), $args, true),
                ],
            ],
        ];
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
        return array_merge([
            'action' => $this->url->link(Helper\Basic::getMainLink(), [
                'user_token' => $user_token,
                'module_id' => $moduleId,
            ], true),
            'user_token' => $user_token,
        ], $this->model_extension_module_onecode_shopflix_Basic->load());
    }

    protected function getEventList(): EventGroup
    {
        $group = new EventGroup();
        $group->add('admin_menu_item', new EventRow(
            'onecode_shopflix_menu_admin_item',
            'admin/view/common/column_left/before',
            'extension/module/onecode_shopflix/eventInjectAdminMenuItem',
            1
        ));
        return $group;
    }
}