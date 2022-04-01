<?php
namespace Onecode\Shopflix\Helper\Model;

use Model;
use Onecode\Shopflix\Helper;

/**
 * @property-read \Loader $load
 * @property-read \ModelSettingSetting $model_setting_setting
 * @property-read \ModelSettingExtension $model_setting_extension
 * @property-read \ModelSettingModule $model_setting_module
 * @property-read \Onecode\Shopflix\Helper\BasicHelper $basicHelper
 * @property-read \Onecode\Shopflix\Helper\ConfigHelper $configHelper
 */
class Configuration extends Model
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
        $data = $this->loadData();
        return isset($data['status']) && $data['status'] == '1';
    }

    public function convertOrders(): bool
    {
        if (!$this->isEnabled()){
            return false;
        }
        $data = $this->loadData();
        return (isset($data['convert_to_order']) && $data['convert_to_order'] == '1');
    }

    public function autoAcceptOrder(): bool
    {
        if (!$this->isEnabled()){
            return false;
        }
        $data = $this->loadData();
        return isset($data['auto_accept_order']) && $data['auto_accept_order'] == '1';
    }

    public function apiUrl(): string
    {
        if (!$this->isEnabled()){
            return '';
        }
        $data = $this->loadData();
        return $data['api_url'] ?? '';
    }

    public function apiUsername(): string
    {
        if (!$this->isEnabled()){
            return '';
        }
        $data = $this->loadData();
        return $data['api_username'] ?? '';
    }

    public function apiPassword(): string
    {
        if (!$this->isEnabled()){
            return '';
        }
        $data = $this->loadData();
        return $data['api_password'] ?? '';
    }

    public function customerGroup(): int
    {
        if (!$this->isEnabled()){
            return 1;
        }
        $data = $this->loadData();
        return (int) ($data['customer_group'] ?? 1);
    }

    public function shippingMethod(): string
    {
        if (!$this->isEnabled()){
            return '';
        }
        $data = $this->loadData();
        $rs = $data['shipping_method'] ?? '';
        if (count(explode('.', $rs)) < 2)
        {
            $rs = implode('.', [$rs, $rs]);
        }
        return $rs;
    }

    public function paymentMethod(): string
    {
        if (!$this->isEnabled()){
            return '';
        }
        $data = $this->loadData();
        return $data['payment_method'] ?? '';
    }
}