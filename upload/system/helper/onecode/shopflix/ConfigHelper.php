<?php
namespace Onecode\Shopflix\Helper;

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 * @property-read \ModelSettingSetting $model_setting_setting
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingModule $model_setting_module
 */
class ConfigHelper extends \Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->load->model('setting/module');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->load->helper('onecode/shopflix/BasicHelper');
    }

    public function loadData(): array
    {
        $data = current($this->getModuleList());
        $data = $data['setting'] ?? [];
        return json_decode($data, true);
    }

    protected function getModuleList(): array
    {
        return (array) $this->getModulesByCode(BasicHelper::getModuleId());
    }

    public function getModuleId(): string
    {
        $list = $this->getModuleList();
        return key_exists('module_id', $list) ? $list['module_id'] : '';
    }

    public function getCurrentModule(): array
    {
        $module_id = BasicHelper::getModuleId();
        $modules = $this->getModulesByCode($module_id);
        if (count($modules) == 0)
        {
            $this->model_setting_module->addModule($module_id, ['name' => $module_id]);
            $modules = $this->getModulesByCode($module_id);
        }
        return current($modules);
    }

    protected function getModulesByCode($code) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "module` WHERE `code` = '" . $this->db->escape($code) . "' ORDER BY `name`");

        return $query->rows;
    }
}