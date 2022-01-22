<?php
namespace Onecode\Shopflix\Helper;

class Basic
{
    public static function getModuleId(): string
    {
        return 'onecode_shopflix';
    }

    public static function getMainLink(): string
    {
        return 'extension/module/onecode_shopflix';
    }

    public static function getPath(): string
    {
        return 'extension/module/onecode/shopflix';
    }

    public static function getRoute(): string
    {
        return 'extension/module/onecode/shopflix/onecode_shopflix';
    }

    /**
     * @param \ModelSettingModule|\Proxy $setting
     *
     * @return array
     */
    public static function getCurrentModule(\Proxy $setting): array
    {
        $module_id = self::getModuleId();
        $modules = $setting->getModulesByCode($module_id);
        if (! count($modules))
        {
            $setting->addModule($module_id, ['name' => $module_id]);
            $modules = $setting->getModulesByCode($module_id);
        }
        return current($modules);
    }
}