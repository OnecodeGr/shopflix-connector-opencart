<?php

/**
 * @property-read \Config $config
 * @property-read \Url $url
 * @property-read \Loader $load
 * @property-read \Language $language
 */
class ModelExtensionModuleOnecodeShopflixProduct extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('catalog/attribute_group');
        $this->load->model('catalog/attribute');
    }

    public function getShopflixAttributes($product_id){

    }
}