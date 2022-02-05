<?php
namespace Onecode\Shopflix\Helper\Model;

use Onecode\Shopflix\Helper;

/**
 * @property-read \Loader $load
 * @property-read \ModelSettingSetting $model_setting_setting
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingModule $model_setting_module
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \Onecode\Shopflix\Helper\ConfigHelper $configHelper
 */
class Configuration extends \Model
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
        return $this->loadData()['status'] == '1';
    }

    public function convertOrders(): bool
    {
        return $this->isEnabled() && $this->loadData()['convert_to_order'] == '1';
    }

    public function autoAcceptOrder(): bool
    {
        return $this->isEnabled() && $this->loadData()['auto_accept_order'] == '1';
    }

    public function apiUrl(): string
    {
        return $this->loadData()['api_url'];
    }

    public function apiUsername(): string
    {
        return $this->loadData()['api_username'];
    }

    public function apiPassword(): string
    {
        return $this->loadData()['api_password'];
    }

    public function customerGroup(): int
    {
        return (int) $this->loadData()['customer_group'] ?? 1;
    }

    public function shippingMethod(): string
    {
        $rs = $this->loadData()['shipping_method'] ?? '';
        if (count(explode('.', $rs)) < 2)
        {
            $rs = implode('.', [$rs, $rs]);
        }
        return $rs;
    }

    public function paymentMethod(): string
    {
        return $this->loadData()['payment_method'] ?? '';
    }
}