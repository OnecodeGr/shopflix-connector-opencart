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

    public function isEnabled(): bool
    {
        return $this->loadData()['xml_status'] == '1';
    }

    public function exportCategories(): bool
    {
        return $this->loadData()['xml_export_category_tree'] == '1';
    }

    public function mpnAttribute(): bool
    {
        $data = $this->loadData();
        return $data['xml_mpn_attr'] == 'other' ? $data['xml_mpn_attr_other'] : $data['xml_mpn_attr'];
    }

    public function eanAttribute(): bool
    {
        $data = $this->loadData();
        return $data['xml_ean_attr'] == 'other' ? $data['xml_ean_attr_other'] : $data['xml_ean_attr'];
    }

    public function titleAttribute(): bool
    {
        $data = $this->loadData();
        return $data['xml_title_attr'] == 'other' ? $data['xml_ean_title_other'] : $data['xml_title_attr'];
    }

    public function descriptionAttribute(): bool
    {
        $data = $this->loadData();
        return $data['xml_description_attr'] == 'other' ? $data['xml_description_attr_other'] : $data['xml_description_attr'];
    }

    public function brandAttribute(): bool
    {
        $data = $this->loadData();
        return $data['xml_brand_attr'] == 'other' ? $data['xml_brand_attr_other'] : $data['xml_brand_attr'];
    }

    public function weightAttribute(): bool
    {
        $data = $this->loadData();
        return $data['xml_weight_attr'] == 'other' ? $data['xml_weight_attr_other'] : $data['xml_weight_attr'];
    }

    public function listPriceAttr(): bool
    {
        $data = $this->loadData();
        return $data['xml_shipping_time_attr'] ?? '';
    }

    public function shippingTimeAttr(): bool
    {
        $data = $this->loadData();
        return $data['xml_shipping_time_attr'] ?? '';
    }

    public function offerFromAttr(): bool
    {
        $data = $this->loadData();
        return $data['xml_offer_from'] ?? '';
    }

    public function offerToAttr(): bool
    {
        $data = $this->loadData();
        return $data['xml_offer_to'] ?? '';
    }

    public function offerPriceAttr(): bool
    {
        $data = $this->loadData();
        return $data['xml_offer_price_attr'] ?? '';
    }

    public function offerQuantityAttr(): ?string
    {
        $data = $this->loadData();
        return $data['xml_offer_quantity'] ?? '';
    }

    public function token(): string
    {
        $data = $this->loadData();
        return $data['xml_token'];
    }
}