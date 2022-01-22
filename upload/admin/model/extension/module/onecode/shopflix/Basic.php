<?php

use Onecode\Shopflix\Helper;

/**
 * @property-read \Loader $load
 * @property-read \ModelSettingModule $model_setting_module
 */
class ModelExtensionModuleOnecodeShopflixBasic extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/module');
        $this->load->helper('onecode/shopflix/Helper');
    }

    public function uninstall(): ?array
    {
        return [];
    }

    public function install(): ?array
    {
        $module = $this->load();
        if (count($module))
        {
            return $module;
        }
        $this->model_setting_module->addModule(
            Helper\Basic::getModuleId(),
            ['name' => Helper\Basic::getModuleId()]
        );
        return $this->load();
    }

    public function load(): array
    {
        $data = current($this->getModuleList());
        $data = $data['setting'] ?? [];
        return json_decode($data, true);
    }

    public function getModuleList(): array
    {
        return (array) $this->model_setting_module->getModulesByCode(Helper\Basic::getModuleId());
    }
}