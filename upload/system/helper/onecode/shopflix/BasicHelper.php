<?php
namespace Onecode\Shopflix\Helper;

/**
 * @property-read \ModelSettingModule $model_setting_module
 */
class BasicHelper extends \Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/module');
    }

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
}