<?php
namespace Onecode\Shopflix\Helper\Model\Config;

use Onecode\Shopflix\Helper;

/**
 * @property-read \Loader $load
 * @property-read \ModelSettingSetting $model_setting_setting
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingModule $model_setting_module
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \Onecode\Shopflix\Helper\ConfigHelper $configHelper
 */
class Xml extends \Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->load->model('setting/module');
        $this->load->helper('onecode/shopflix/BasicHelper');
        $this->basicHelper = new Helper\BasicHelper($registry);
        $this->load->helper('onecode/shopflix/ConfigHelper');
        $this->configHelper = new Helper\ConfigHelper($registry);
    }

    public function loadData(): array
    {
        return $this->configHelper->loadData();
    }

    private function loadDataValue(string $key)
    {
        $data = $this->loadData();
        return isset($data[$key]) ? $data[$key] : null;
    }

    public function isEnabled(): bool
    {
        return $this->loadDataValue('xml_status') == '1';
    }

    public function exportCategories(): bool
    {
        return $this->loadDataValue('xml_export_category_tree') == '1';
    }

    public function mpnAttribute(): string
    {
        $value = $this->loadDataValue('xml_mpn_attr');
        return $value == 'other' ? $this->loadDataValue('xml_mpn_attr') : $value;
    }

    public function eanAttribute(): string
    {
        $value = $this->loadDataValue('xml_ean_attr');
        return $value == 'other' ? $this->loadDataValue('xml_ean_attr_other') : $value;
    }

    public function nameAttribute(): string
    {
        $value = $this->loadDataValue('xml_name_attr');
        return $value == 'other' ? $this->loadDataValue('xml_name_attr_other') : $value;
    }

    public function descriptionAttribute(): string
    {
        $value = $this->loadDataValue('xml_description_attr');
        return $value == 'other' ? $this->loadDataValue('xml_description_attr_other') : $value;
    }

    public function brandAttribute(): string
    {
        $value = $this->loadDataValue('xml_brand_attr');
        return $value == 'other' ? $this->loadDataValue('xml_brand_attr_other') : $value;
    }

    public function weightAttribute(): string
    {
        $value = $this->loadDataValue('xml_weight_attr');
        return $value == 'other' ? $this->loadDataValue('xml_weight_attr_other') : $value;
    }

    public function listPriceAttr(): string
    {
        return $this->loadDataValue('xml_shipping_time_attr');
    }

    public function shippingTimeAttr(): ?string
    {
        return $this->loadDataValue('xml_shipping_time_attr');
    }

    public function offerFromAttr(): ?string
    {
        return $this->loadDataValue('xml_offer_from_attr');
    }

    public function offerToAttr(): ?string
    {
        return $this->loadDataValue('xml_offer_to_attr');
    }

    public function offerPriceAttr(): ?string
    {
        return $this->loadDataValue('xml_offer_price_attr');
    }

    public function offerQuantityAttr(): ?string
    {
        return $this->loadDataValue('xml_offer_quantity_attr');
    }

    public function token(): ?string
    {
        return $this->loadDataValue('xml_token');
    }
}