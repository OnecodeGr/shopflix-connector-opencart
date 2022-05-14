<?php

use Onecode\Shopflix\Helper;

require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Configuration.php';

class ModelExtensionModuleOnecodeShopflixConfig extends Helper\Model\Configuration
{
    public function install()
    {
        $this->uninstall();
        $this->model_setting_extension->install('module', Helper\BasicHelper::getModuleId());
        $this->model_setting_setting->editSetting(Helper\BasicHelper::getModuleId(), [
            Helper\BasicHelper::getModuleId() . '_status' => 1,
            'status' => 1,
        ]);
    }

    public function uninstall()
    {
        $this->model_setting_extension->uninstall('module', Helper\BasicHelper::getModuleId());
        $this->model_setting_setting->deleteSetting(Helper\BasicHelper::getModuleId());
    }

    public function save($data, $moduleId): void
    {
        $data[Helper\BasicHelper::getModuleId() . '_status'] = $data['status'];
        $data['name'] = Helper\BasicHelper::getModuleId();
        $this->model_setting_module->editModule($moduleId, $data);
    }
}